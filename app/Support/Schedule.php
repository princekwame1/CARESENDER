<?php

namespace App\Support;

use App\Services\SearchFilters;

/** Turns structured times into plain language, e.g. "Mon to Fri, 8am to 5pm · Sat, 9am to 1pm". */
class Schedule
{
    /**
     * @param  list<array{day: string, start: string, end: string}>  $slots
     */
    public static function summarise(array $slots): ?string
    {
        if (! $slots) {
            return null;
        }

        $order = array_flip(SearchFilters::DAYS);
        usort($slots, fn ($a, $b) => $order[$a['day']] <=> $order[$b['day']]);

        // Group days that share the same hours, keeping week order.
        $groups = [];
        foreach ($slots as $s) {
            $groups[$s['start'].'|'.$s['end']][] = $s['day'];
        }

        $parts = [];
        foreach ($groups as $range => $days) {
            [$start, $end] = explode('|', $range);
            $parts[] = self::days($days, $order).', '.self::time($start).' to '.self::time($end);
        }

        return implode(' · ', $parts);
    }

    /** Days known but no times yet, e.g. "Mon, Thu · times not confirmed yet". */
    public static function daysOnly(array $days): ?string
    {
        if (! $days) {
            return null;
        }
        $order = array_flip(SearchFilters::DAYS);
        usort($days, fn ($a, $b) => $order[$a] <=> $order[$b]);

        return self::days($days, $order).' · times not confirmed yet';
    }

    /** Opening hours stored as {"Monday": {"open": "08:00", "close": "17:00"}} → summary text. */
    public static function openingHours(?array $hours): ?string
    {
        $slots = [];
        foreach ($hours ?? [] as $day => $h) {
            if (! empty($h['open']) && ! empty($h['close'])) {
                $slots[] = ['day' => $day, 'start' => $h['open'], 'end' => $h['close']];
            }
        }

        return self::summarise($slots);
    }

    public static function time(string $hhmm): string
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));
        $suffix = $h >= 12 ? 'pm' : 'am';
        $h12 = $h % 12 ?: 12;

        return $h12.($m ? ':'.str_pad((string) $m, 2, '0', STR_PAD_LEFT) : '').$suffix;
    }

    /** ["Monday","Tuesday","Wednesday","Friday"] → "Mon to Wed, Fri" */
    private static function days(array $days, array $order): string
    {
        $idx = array_map(fn ($d) => $order[$d], $days);
        $runs = [];
        foreach ($idx as $i) {
            if ($runs && end($runs)[1] === $i - 1) {
                $runs[array_key_last($runs)][1] = $i;
            } else {
                $runs[] = [$i, $i];
            }
        }

        return implode(', ', array_map(function ($r) {
            $a = substr(SearchFilters::DAYS[$r[0]], 0, 3);
            $b = substr(SearchFilters::DAYS[$r[1]], 0, 3);

            return match ($r[1] - $r[0]) {
                0 => $a,
                1 => "$a, $b",
                default => "$a to $b",
            };
        }, $runs));
    }
}
