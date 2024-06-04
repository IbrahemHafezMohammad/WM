<?php

namespace App\Services\Providers\UGProvider\Encryption;

class AesCbcEncryptor
{
    const CIPHER_AES_256_CBC = 'aes-256-cbc';
    protected $apiKey = '';
    protected $iv = '';

    public function __construct($apiKey, $operatorId)
    {
        // Ensure the API key is exactly 32 characters (UTF-8)
        $this->apiKey = $apiKey;

        // Process the operatorId to be 16 characters lowercase (UTF-8)
        $operatorId = strtolower($operatorId);
        if (strlen($operatorId) < 16) {
            $operatorId = str_pad($operatorId, 16, "0", STR_PAD_LEFT);
        } elseif (strlen($operatorId) > 16) {
            $operatorId = substr($operatorId, -16);
        }
        $this->iv = $operatorId;
    }

    public function encrypt($timestamp)
    {
        // Combine and process the apiKey and operatorId
        $inputString = strtolower($this->apiKey . $this->iv);
        
        // Create md5 hash of the processed input string
        $md5Input = md5($inputString);

        // Append the timestamp to the md5 hash
        $plainText = $md5Input . $timestamp;

        // Encrypt the data using AES-256-CBC
        $encrypted = openssl_encrypt($plainText, self::CIPHER_AES_256_CBC, $this->apiKey, OPENSSL_RAW_DATA, $this->iv);

        // Return the encrypted data in base64 format
        return base64_encode($encrypted);
    }

    public function decrypt($encryptedData)
    {
        // Decrypt the data using the same settings as for encryption
        $decrypted = openssl_decrypt($encryptedData, self::CIPHER_AES_256_CBC, $this->apiKey, 0, $this->iv);
        return $decrypted;
    }

    public function validate($decryptedData, $toleranceInSeconds = 300)
    {
        // Extract the timestamp from the end of the decrypted data
        $timestamp = substr($decryptedData, -10); // Assuming timestamp is always 10 digits
        $md5HashFromDecrypted = substr($decryptedData, 0, -10); // Remove the last 10 digits to get the MD5 hash

        // Reconstruct the MD5 hash from the apiKey and operatorId to validate
        $inputString = strtolower($this->apiKey . $this->iv);
        $expectedMd5Hash = md5($inputString);

        // Check if the MD5 hash matches
        if ($md5HashFromDecrypted !== $expectedMd5Hash) {
            return false; // MD5 hash does not match, data is invalid
        }

        // Validate the timestamp

        if (!is_numeric($timestamp) || (int) $timestamp > time() || (int) $timestamp < 0) {
            return false; // Invalid timestamp in the token string
        }

        $currentTime = time();
        if ($currentTime - $timestamp > $toleranceInSeconds) {
            return false; // Timestamp is out of tolerance, data is considered expired
        }

        // If both checks pass, the data is valid
        return true;
    }

}
