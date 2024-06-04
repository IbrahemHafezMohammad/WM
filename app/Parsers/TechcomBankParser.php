<?php

namespace App\Parsers;

use App\Parsers\MessageParseInterface;

class TechcomBankParser implements MessageParseInterface
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

            // Define regular expression for matching the account number
            $regex = '/Account: (\d+)/';
            preg_match($regex, $this->message, $matches);
            if (isset($matches[1])) {
                $accountNumber = $matches[1];
                $this->account_number = $accountNumber;
            }

            //GET THE AMOUNT and set deposit or withdraw
            $regex = '/([+-]) VND (\d+(?:,\d+)*)/';
            preg_match($regex, $this->message, $matches);
            if (isset($matches[1]) && isset($matches[2])) {
                $amount = str_replace(',', '', $matches[2]);
                if ($matches[1] == '+') {
                    $this->isWithdraw = false;
                } else if ($matches[1] == '-') {
                    $this->isWithdraw = true;
                }

                if ($amount) {
                    $this->amount = $amount;
                }
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