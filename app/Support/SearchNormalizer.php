<?php

namespace App\Support;

/**
 * Normalises free-text health searches so "CBC", "complete blood count" and
 * "full blood count" all hit the same records. Ported from the original
 * CareSenda client so web and mobile searches behave identically.
 */
class SearchNormalizer
{
    private const ALIASES = [
        'psa' => 'prostate specific antigen', 'ngs' => 'next generation sequencing', 'esr' => 'erythrocyte sedimentation rate',
        'hbsag' => 'hepatitis b', 'e2' => 'estradiol', 'fbc' => 'full blood count', 'cbc' => 'full blood count',
        'lft' => 'liver function', 'lfts' => 'liver function', 'rft' => 'kidney function', 'rfts' => 'kidney function',
        'kft' => 'kidney function', 'tft' => 'thyroid function', 'tfts' => 'thyroid function', 'fbs' => 'blood sugar',
        'rbs' => 'blood sugar', 'fbg' => 'blood sugar', 'rbg' => 'blood sugar', 'mp' => 'malaria', 'widal' => 'typhoid',
        'cardiology' => 'cardiologist', 'dermatology' => 'dermatologist', 'neurology' => 'neurologist',
        'gynaecology' => 'gynaecologist', 'gynecology' => 'gynaecologist', 'gynecologist' => 'gynaecologist',
        'gynecologists' => 'gynaecologist', 'gynaecologists' => 'gynaecologist', 'paediatrics' => 'paediatrician',
        'pediatrics' => 'paediatrician', 'pediatrician' => 'paediatrician', 'pediatricians' => 'paediatrician',
        'ophthalmology' => 'ophthalmologist', 'orthopedics' => 'orthopaedic', 'orthopaedics' => 'orthopaedic',
        'mammography' => 'mammogram', 'diagnostics' => 'diagnostic', 'hospitals' => 'hospital', 'clinics' => 'clinic',
        'centres' => 'centre', 'centers' => 'centre', 'center' => 'centre', 'tests' => 'test', 'scans' => 'scan',
        'specialists' => 'specialist', 'dentists' => 'dentist', 'dental' => 'dentist',
    ];

    private const REWRITES = [
        '/x[ -]?ray/' => 'xray',
        '/haemoglobin/' => 'hemoglobin',
        '/blood group\b/' => 'blood grouping',
        '/syphilis/' => 'tpha',
        '/pre marital/' => 'pre-marital',
        '/complete blood count/' => 'full blood count',
        '/renal function/' => 'kidney function',
        '/(?:fasting|random) (?:blood )?(?:sugar|glucose)/' => 'blood sugar',
        '/blood glucose/' => 'blood sugar',
        '/urine (?:routine(?: examination)?|analysis|test)/' => 'urinalysis',
        '/stool (?:routine(?: examination)?|test)/' => 'stool examination',
        '/malaria parasite/' => 'malaria',
        '/hba1c|hb a1c/' => 'hba1c',
    ];

    private const STOP_WORDS = ['a', 'an', 'the', 'in', 'at', 'near', 'around', 'find', 'me', 'please', 'for', 'of', 'and', 'test', 'testing'];

    public static function normalise(?string $value): string
    {
        $value = mb_strtolower((string) $value);
        $value = preg_replace(array_keys(self::REWRITES), array_values(self::REWRITES), $value);
        $value = trim(preg_replace('/[^a-z0-9]+/', ' ', $value));

        if ($value === '') {
            return '';
        }

        return implode(' ', array_map(fn ($w) => self::ALIASES[$w] ?? $w, explode(' ', $value)));
    }

    /** @return list<string> */
    public static function words(?string $query): array
    {
        $words = array_filter(
            explode(' ', self::normalise($query)),
            fn ($w) => $w !== '' && ! in_array($w, self::STOP_WORDS, true),
        );

        return array_values(array_unique($words));
    }
}
