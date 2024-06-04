<?php

namespace App\Parsers;

use App\Parsers\MessageParseInterface;

class TPBankMessageParser implements MessageParseInterface
{

    protected $message = null;
    protected $account_number = "";
    protected $amount = "";
    protected $isWithdraw = null;
    protected $note = "";


    function __construct($message)
    {
        $this->message = $message;
        $this->parseMessage();
    }


    protected function parseMessage()
    {
        if ($this->message) {
            //GET THE ACCOUNT NUMBER
            $accountRegex = '/TK: x{4}(\d+)/';
            preg_match($accountRegex, $this->message, $accountMatches);
            if (isset($accountMatches[1])) {
                $accountNumber = $accountMatches[1];
                $this->account_number = $accountNumber;
            }


            // Define regular expressions for matching the amount and transaction type
            $amountRegex = '/PS:([+-])(\d{1,3}(?:\.\d{3})+)VND/';
            $transactionTypeRegex = '/PS:[+-]/';

            // Match the amount in the message
            preg_match($amountRegex, $this->message, $amountMatches);
            preg_match($transactionTypeRegex, $this->message, $transactionTypeMatches);
            if (isset($amountMatches[1]) && isset($amountMatches[2]) && isset($transactionTypeMatches[0])) {
                $sign = $amountMatches[1];
                $amount = str_replace('.', '', $amountMatches[2]);
                $isWithdraw = ($sign == '+') ? false : true;
                if ($amount) {
                    $this->amount = $amount;
                }
                if ($isWithdraw) {
                    $this->isWithdraw = true;
                } else {
                    $this->isWithdraw = false;
                }
            }
        }
    }

    public function getCompanyAccountNumber(): string
    {
        return $this->account_number;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }


    public function getIsWithdraw(): ?bool
    {
        return $this->isWithdraw;
    }


    public function getNote(): string
    {
        return $this->note;
    }


}