<?php
namespace App\Services\Providers;

interface TransferProviderInterface
{
    public function getBalance(): ?string;
    public function depositPoints($points): ?string;
    public function withdrawPoints($points): ?string;

}
?>