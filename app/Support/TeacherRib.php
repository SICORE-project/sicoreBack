<?php

namespace App\Support;

final class TeacherRib
{
    public static function key(string $bank, string $branch, string $account): ?string
    {
        if (! preg_match('/\ASN[0-9]{3}\z/', $bank)
            || ! preg_match('/\A[0-9]{5}\z/', $branch)
            || ! preg_match('/\A[0-9]{12}\z/', $account)) {
            return null;
        }

        $sum = 17 * (int) ('25'.substr($bank, 2)) + 53 * (int) $branch
            + 81 * (int) substr($account, 0, 6) + 3 * (int) substr($account, 6, 6);

        return str_pad((string) (97 - $sum % 97), 2, '0', STR_PAD_LEFT);
    }

    public static function prepare(mixed $account): mixed
    {
        if (! is_array($account)) {
            return $account;
        }
        if (is_string($account['code_banque'] ?? null)) {
            $account['code_banque'] = strtoupper(trim($account['code_banque']));
        }
        $fields = ['code_banque', 'code_guichet', 'numero_compte'];
        foreach ($fields as $field) {
            if (! is_string($account[$field] ?? null)) {
                return $account;
            }
        }
        $key = self::key($account['code_banque'], $account['code_guichet'], $account['numero_compte']);
        if ($key !== null) {
            $account['cle_rib'] = $key;
            $account['rib'] = $account['code_banque'].$account['code_guichet'].$account['numero_compte'].$key;
        }

        return $account;
    }
}
