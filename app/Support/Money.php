<?php

namespace App\Support;

use InvalidArgumentException;

final class Money
{
    public const MAX = 999999999999;

    public static function minor(string|int $value): int
    {
        $s = (string) $value;
        if (! preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $s)) {
            throw new InvalidArgumentException('Invalid monetary amount.');
        }
        [$whole,$fraction] = array_pad(explode('.', $s, 2), 2, '');
        $n = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        if ($n > self::MAX) {
            throw new InvalidArgumentException('Amount exceeds the supported limit.');
        }

        return $n;
    }

    public static function decimal(int $n): string
    {
        return ($n < 0 ? '-' : '').intdiv(abs($n), 100).'.'.str_pad((string) (abs($n) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function format(string|int $n, string $currency = 'TZS'): string
    {
        $minor = is_int($n) ? $n : self::minor($n);

        return $currency.' '.($minor < 0 ? '-' : '').number_format(intdiv(abs($minor), 100), 0, '.', ',').'.'.str_pad((string) (abs($minor) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function percent(int $minor, string|int $percent): int
    {
        $bps = self::minor($percent);
        if ($bps > 10000) {
            throw new InvalidArgumentException('Percentage must be between 0 and 100.');
        }

        return intdiv($minor * $bps + 5000, 10000);
    }

    public static function convert(int $minor, string $from, string $to, ?string $rate): int
    {
        if ($from === $to) {
            return $minor;
        }
        if (! $rate || ! preg_match('/^\d{1,8}(?:\.\d{1,6})?$/D', $rate) || bccomp($rate, '0', 6) <= 0) {
            throw new InvalidArgumentException('An exchange rate must be configured for this currency.');
        }
        $n = $from === 'USD' && $to === 'TZS' ? bcmul((string) $minor, $rate, 6) : ($from === 'TZS' && $to === 'USD' ? bcdiv((string) $minor, $rate, 6) : throw new InvalidArgumentException('Unsupported currency.'));
        $result = (int) bcadd($n, '0.5', 0);
        if ($result > self::MAX) {
            throw new InvalidArgumentException('Converted amount exceeds the limit.');
        }

return $result;
    }
}
