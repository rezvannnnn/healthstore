<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TwoFactorService
{
    public function generateSecret(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        foreach (str_split(random_bytes(32)) as $byte) {
            $secret .= $alphabet[ord($byte) & 31];
        }

        return $secret;
    }

    public function code(string $secret, int $timestamp, int $digits = 6): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper($secret)) as $char) {
            $index = strpos($alphabet, $char);
            if ($index === false) {
                throw new \InvalidArgumentException('Invalid TOTP secret');
            } $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }
        $key = '';
        for ($i = 0; $i + 8 <= strlen($bits); $i += 8) {
            $key .= chr((int) bindec(substr($bits, $i, 8)));
        }
        $counter = intdiv($timestamp, 30);
        $hash = hash_hmac('sha1', pack('N2', intdiv($counter, 4294967296), $counter % 4294967296), $key, true);
        $offset = ord($hash[19]) & 15;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);

        return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
    }

    public function matchingCounter(string $secret, string $code): ?int
    {
        if (! preg_match('/^[0-9]{6}$/D', $code)) {
            return null;
        }
        $counter = intdiv(now()->getTimestamp(), 30);
        foreach ([$counter, $counter - 1, $counter + 1] as $candidate) {
            if (hash_equals($this->code($secret, $candidate * 30), $code)) {
                return $candidate;
            }
        }

        return null;
    }

    public function verify(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code) {
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (! $locked->two_factor_confirmed_at || ! $locked->two_factor_secret) {
                return false;
            }
            $counter = $this->matchingCounter($locked->two_factor_secret, $code);
            if ($counter !== null && ($locked->two_factor_last_counter === null || $counter > $locked->two_factor_last_counter)) {
                $locked->two_factor_last_counter = $counter;
                $locked->save();

                return true;
            }
            $codes = $locked->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($codes[$index]);
                    $locked->two_factor_recovery_codes = array_values($codes);
                    $locked->save();

                    return true;
                }
            }

            return false;
        });
    }
}
