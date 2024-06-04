<?php

namespace App\Services\Providers\PinnacleProvider\Encryption;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AesCbcEncryptor
{
    const CIPHER_AES_128_CBC = 'aes-128-cbc';
    const INIT_VECTOR = 'RandomInitVector';
    protected $agent_code;
    protected $agent_key;
    protected $secret_key;
    protected $timestamp;

    public function __construct($agent_code, $agent_key, $secret_key)
    {
        $this->agent_code = $agent_code;
        $this->agent_key = $agent_key;
        $this->secret_key = $secret_key;
        $this->timestamp = (int) now()->getPreciseTimestamp(3);
    }

    public function encode(): ?array
    {
        $hashed_token = md5($this->agent_code . $this->timestamp . $this->agent_key);
        // Log::info('hashed_token: ' . $hashed_token);
        $token_payload = $this->agent_code . '|' . $this->timestamp . '|' . $hashed_token;

        $token = $this->encrypt($token_payload);
        return [
            'token_payload' => $token_payload,
            'token' => $token,
        ];
    }

    public function encrypt($plainText)
    {
        $iv = substr(self::INIT_VECTOR, 0, openssl_cipher_iv_length(self::CIPHER_AES_128_CBC));
        $encrypted = openssl_encrypt($plainText, self::CIPHER_AES_128_CBC, $this->secret_key, OPENSSL_RAW_DATA, self::INIT_VECTOR);
        return base64_encode($encrypted);
    }

    public function decode(string $token): ?array
    {
        $token_payload = $this->decrypt($token);
        if (!$token_payload) {
            return null;
        }
        
        $parts = explode('|', $token_payload);

        if (in_array(null, $parts, true) || count($parts) !== 3) {
            return null;
        }

        return [
            'agentCode' => $parts[0],
            'timestamp' => $parts[1],
            'hashToken' => $parts[2],
        ];
    }

    public function decrypt($encryptedData)
    {
        $iv = substr(self::INIT_VECTOR, 0, openssl_cipher_iv_length(self::CIPHER_AES_128_CBC));
        $decrypted = openssl_decrypt(base64_decode($encryptedData), self::CIPHER_AES_128_CBC, $this->secret_key, OPENSSL_RAW_DATA, self::INIT_VECTOR);
        return $decrypted;
    }
}
