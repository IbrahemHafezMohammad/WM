<?php

namespace App\Parsers;

use App\Parsers\MessageParseInterface;

class VCBMessageParser implements MessageParseInterface
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
            $withdrawal_regex = '/-\d[\d,.]* VND/';
            $deposit_regex = '/\+\d[\d,.]* VND/';

            $is_deposit = preg_match($deposit_regex, $this->message);
            $is_withdrawal = preg_match($withdrawal_regex, $this->message);
            if ($is_deposit) {
                $this->isWithdraw = false;
            } else if ($is_withdrawal) {
                $this->isWithdraw = true;
            }

            //GET THE ACCOUNT NUMBER
            $regex = '/(\d{10,})/';
            preg_match($regex, $this->message, $matches);
            if (isset($matches[1])) {
                $this->account_number = $matches[1];
            }

            //GET THE AMOUNT
            $regex = "/\b\d{1,3}(?:,\d{3})*(?:\.\d+)?(?=\sVND\b)/";

            preg_match($regex, $this->message, $matches);
            if (isset($matches[0])) {
                $amount = str_replace(',', '', $matches[0]);
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