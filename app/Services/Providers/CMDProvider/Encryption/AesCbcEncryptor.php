<?php

namespace App\Services\Providers\CMDProvider\Encryption;

class AesCbcEncryptor
{
    const CIPHER_AES_128_CBC = 'aes-128-cbc';
    protected $key = '';
    protected $iv = '';

    public function __construct(string $key)
    {
        // Ensure the API key nad iv is exactly 16 characters (UTF-8)
        $this->key = strlen($key) === 16 ? $key : '';
        $iv = strrev($key);
        $this->iv = strlen($iv) === 16 ? $iv : '';
    }

    public function encrypt($plainText)
    {
        $encrypted = openssl_encrypt($plainText, self::CIPHER_AES_128_CBC, $this->key, OPENSSL_RAW_DATA, $this->iv);
        return $encrypted ? base64_encode($encrypted) : '';
    }

    public function decrypt($encryptedData)
    {
        $decrypted = openssl_decrypt(base64_decode($encryptedData), self::CIPHER_AES_128_CBC, $this->key, OPENSSL_RAW_DATA, $this->iv);
        return $decrypted ? $decrypted : '';
    }

}
