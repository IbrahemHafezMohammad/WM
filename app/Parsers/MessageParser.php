<?php

namespace App\Parsers;

use App\Parsers\MessageParseInterface;

class MessageParser
{
    protected $message = null;
    protected $bank_code;

    function __construct($message, $bank_code)
    {
        $this->message = $message;
        $this->bank_code = $bank_code;
    }


    public function getParserObject(): ?MessageParseInterface
    {
        if ($this->bank_code == "BIDVBANK") {
            return new BIDVMessageParser($this->message);
        } else if ($this->bank_code == "VIETCOMBANK") {
            return new VCBMessageParser($this->message);
        } else if ($this->bank_code == "ACBBANK") {
            return new ACBMessageParser($this->message);
        } else if ($this->bank_code == "TPBANK") {
            return new TPBankMessageParser($this->message);
        } else if ($this->bank_code == "TECHCOMBANK") {
            return new TechcomBankParser($this->message);
        }
        return null;
    }

}