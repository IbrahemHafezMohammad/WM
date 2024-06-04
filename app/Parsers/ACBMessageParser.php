<?php

namespace App\Parsers;

use App\Parsers\MessageParseInterface;

class ACBMessageParser implements MessageParseInterface
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
            //CHECK IF IT IS WITHDRAWAL OR DEPOSIT
            $deposit_regex = '/\+ [\d,]+/';
            $withdrawal_regex = '/- [\d,]+/';

            $is_deposit = preg_match($deposit_regex, $this->message);
            $is_withdrawal = preg_match($withdrawal_regex, $this->message);
            if ($is_deposit) {
                $this->isWithdraw = false;
            } else if ($is_withdrawal) {
                $this->isWithdraw = true;
            }

            //GET THE ACCOUNT NUMBER
            $regex = '/Acc (\d+)/';
            preg_match($regex, $this->message, $matches);
            if (isset($matches[1])) {
                $accountNumber = $matches[1];
                $this->account_number = $accountNumber;
            }

            //GET THE AMOUNT
            $regex = '/[+-] ([\d,]+)/';
            preg_match($regex, $this->message, $matches);
            if (isset($matches[1])) {
                $amount = str_replace(',', '', $matches[1]);
                $this->amount = $amount;
            }

            //GET NOTE
            $regex = '/\b0\d{9}\b/';
            preg_match($regex, $this->message, $matches);

            if (isset($matches[0])) {
                $phoneNumber = $matches[0];
                $this->note = $phoneNumber;
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