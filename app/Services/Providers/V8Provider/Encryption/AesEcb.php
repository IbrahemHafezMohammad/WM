<?php

namespace App\Services\Providers\V8Provider\Encryption;

class AesEcb
{
    protected $cipher = 'aes-128-ecb';
    protected $secretKey = '';

    public function setKey($key)
    {
        $this->secretKey = $key;
    }

    public function encrypt($str)
    {
        $paddedStr = $this->pkcs5Pad($str, 16);
        $encryptedStr = openssl_encrypt($paddedStr, $this->cipher, $this->secretKey, OPENSSL_RAW_DATA);
        return base64_encode($encryptedStr);
    }

    public function decrypt($str)
    {
        $decryptedText = openssl_decrypt(base64_decode($str), $this->cipher, $this->secretKey, OPENSSL_RAW_DATA);
        return $this->pkcs5Unpad($decryptedText);
    }

    protected function pkcs5Pad($text, $blockSize)
    {
        $pad = $blockSize - (strlen($text) % $blockSize);
        return $text . str_repeat(chr($pad), $pad);
    }

    protected function pkcs5Unpad($text)
    {
        $pad = ord($text[strlen($text) - 1]);
        if ($pad > strlen($text))
            return false;
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad)
            return false;
        return substr($text, 0, -1 * $pad);
    }

    public function getMillisecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return $t2 . ceil(($t1 * 1000));
    }

    public function getOrderId($agent)
    {
        list($usec, $sec) = explode(" ", microtime());
        $msec = round($usec * 1000);
        return $agent . date("YmdHis") . $msec;
    }
}