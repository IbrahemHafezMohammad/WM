<?php
namespace App\Parsers;

interface MessageParseInterface
{
    public function getCompanyAccountNumber(): string;
    public function getAmount(): string;
    public function getIsWithdraw(): ?bool;
    public function getNote(): string;
}
?>