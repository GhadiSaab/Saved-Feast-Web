<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class PickupCodeService
{
    /**
     * Generate a numeric pickup code
     */
    public function generate(?int $length = null): string
    {
        $length = $length ?? config('sf_orders.pickup_code.length', 6);

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }

        return $code;
    }

    /**
     * Encrypt a pickup code for storage
     */
    public function encrypt(string $code): string
    {
        return Crypt::encryptString($code);
    }

    /**
     * Verify a pickup code against the encrypted version
     */
    public function verify(string $encrypted, string $candidate): bool
    {
        try {
            $decrypted = Crypt::decryptString($encrypted);

            return $decrypted === $candidate;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Mask a pickup code for display (e.g., "12••••89")
     */
    public function mask(string $code): string
    {
        $length = strlen($code);
        if ($length <= 2) {
            return str_repeat('•', $length);
        }

        $masked = substr($code, 0, 2).str_repeat('•', $length - 4).substr($code, -2);

        return $masked;
    }

    /**
     * Decrypt and return the actual code
     */
    public function decrypt(string $encrypted): ?string
    {
        try {
            return Crypt::decryptString($encrypted);
        } catch (\Exception $e) {
            return null;
        }
    }
}
