<?php

namespace App\Support;

/** Country calling codes for the phone picker, and turning "024 123 4567" + Ghana into "+233241234567". */
class PhoneNumber
{
    public const DEFAULT_COUNTRY = 'GH';

    /** Countries that keep a leading 0 after the country code. */
    private const KEEPS_LEADING_ZERO = ['IT', 'SM', 'VA'];

    /** @var array<string, array{0: string, 1: string}> ISO code => [name, calling code] */
    public const COUNTRIES = [
        'AF' => ['Afghanistan', '93'], 'AL' => ['Albania', '355'], 'DZ' => ['Algeria', '213'], 'AD' => ['Andorra', '376'],
        'AO' => ['Angola', '244'], 'AG' => ['Antigua and Barbuda', '1'], 'AR' => ['Argentina', '54'], 'AM' => ['Armenia', '374'],
        'AU' => ['Australia', '61'], 'AT' => ['Austria', '43'], 'AZ' => ['Azerbaijan', '994'], 'BS' => ['Bahamas', '1'],
        'BH' => ['Bahrain', '973'], 'BD' => ['Bangladesh', '880'], 'BB' => ['Barbados', '1'], 'BY' => ['Belarus', '375'],
        'BE' => ['Belgium', '32'], 'BZ' => ['Belize', '501'], 'BJ' => ['Benin', '229'], 'BT' => ['Bhutan', '975'],
        'BO' => ['Bolivia', '591'], 'BA' => ['Bosnia and Herzegovina', '387'], 'BW' => ['Botswana', '267'], 'BR' => ['Brazil', '55'],
        'BN' => ['Brunei', '673'], 'BG' => ['Bulgaria', '359'], 'BF' => ['Burkina Faso', '226'], 'BI' => ['Burundi', '257'],
        'CV' => ['Cabo Verde', '238'], 'KH' => ['Cambodia', '855'], 'CM' => ['Cameroon', '237'], 'CA' => ['Canada', '1'],
        'CF' => ['Central African Republic', '236'], 'TD' => ['Chad', '235'], 'CL' => ['Chile', '56'], 'CN' => ['China', '86'],
        'CO' => ['Colombia', '57'], 'KM' => ['Comoros', '269'], 'CG' => ['Congo', '242'], 'CD' => ['Congo (DRC)', '243'],
        'CR' => ['Costa Rica', '506'], 'CI' => ['Côte d’Ivoire', '225'], 'HR' => ['Croatia', '385'], 'CU' => ['Cuba', '53'],
        'CY' => ['Cyprus', '357'], 'CZ' => ['Czechia', '420'], 'DK' => ['Denmark', '45'], 'DJ' => ['Djibouti', '253'],
        'DM' => ['Dominica', '1'], 'DO' => ['Dominican Republic', '1'], 'EC' => ['Ecuador', '593'], 'EG' => ['Egypt', '20'],
        'SV' => ['El Salvador', '503'], 'GQ' => ['Equatorial Guinea', '240'], 'ER' => ['Eritrea', '291'], 'EE' => ['Estonia', '372'],
        'SZ' => ['Eswatini', '268'], 'ET' => ['Ethiopia', '251'], 'FJ' => ['Fiji', '679'], 'FI' => ['Finland', '358'],
        'FR' => ['France', '33'], 'GA' => ['Gabon', '241'], 'GM' => ['Gambia', '220'], 'GE' => ['Georgia', '995'],
        'DE' => ['Germany', '49'], 'GH' => ['Ghana', '233'], 'GR' => ['Greece', '30'], 'GD' => ['Grenada', '1'],
        'GT' => ['Guatemala', '502'], 'GN' => ['Guinea', '224'], 'GW' => ['Guinea-Bissau', '245'], 'GY' => ['Guyana', '592'],
        'HT' => ['Haiti', '509'], 'HN' => ['Honduras', '504'], 'HK' => ['Hong Kong', '852'], 'HU' => ['Hungary', '36'],
        'IS' => ['Iceland', '354'], 'IN' => ['India', '91'], 'ID' => ['Indonesia', '62'], 'IR' => ['Iran', '98'],
        'IQ' => ['Iraq', '964'], 'IE' => ['Ireland', '353'], 'IL' => ['Israel', '972'], 'IT' => ['Italy', '39'],
        'JM' => ['Jamaica', '1'], 'JP' => ['Japan', '81'], 'JO' => ['Jordan', '962'], 'KZ' => ['Kazakhstan', '7'],
        'KE' => ['Kenya', '254'], 'KI' => ['Kiribati', '686'], 'KW' => ['Kuwait', '965'], 'KG' => ['Kyrgyzstan', '996'],
        'LA' => ['Laos', '856'], 'LV' => ['Latvia', '371'], 'LB' => ['Lebanon', '961'], 'LS' => ['Lesotho', '266'],
        'LR' => ['Liberia', '231'], 'LY' => ['Libya', '218'], 'LI' => ['Liechtenstein', '423'], 'LT' => ['Lithuania', '370'],
        'LU' => ['Luxembourg', '352'], 'MO' => ['Macao', '853'], 'MG' => ['Madagascar', '261'], 'MW' => ['Malawi', '265'],
        'MY' => ['Malaysia', '60'], 'MV' => ['Maldives', '960'], 'ML' => ['Mali', '223'], 'MT' => ['Malta', '356'],
        'MH' => ['Marshall Islands', '692'], 'MR' => ['Mauritania', '222'], 'MU' => ['Mauritius', '230'], 'MX' => ['Mexico', '52'],
        'FM' => ['Micronesia', '691'], 'MD' => ['Moldova', '373'], 'MC' => ['Monaco', '377'], 'MN' => ['Mongolia', '976'],
        'ME' => ['Montenegro', '382'], 'MA' => ['Morocco', '212'], 'MZ' => ['Mozambique', '258'], 'MM' => ['Myanmar', '95'],
        'NA' => ['Namibia', '264'], 'NR' => ['Nauru', '674'], 'NP' => ['Nepal', '977'], 'NL' => ['Netherlands', '31'],
        'NZ' => ['New Zealand', '64'], 'NI' => ['Nicaragua', '505'], 'NE' => ['Niger', '227'], 'NG' => ['Nigeria', '234'],
        'KP' => ['North Korea', '850'], 'MK' => ['North Macedonia', '389'], 'NO' => ['Norway', '47'], 'OM' => ['Oman', '968'],
        'PK' => ['Pakistan', '92'], 'PW' => ['Palau', '680'], 'PS' => ['Palestine', '970'], 'PA' => ['Panama', '507'],
        'PG' => ['Papua New Guinea', '675'], 'PY' => ['Paraguay', '595'], 'PE' => ['Peru', '51'], 'PH' => ['Philippines', '63'],
        'PL' => ['Poland', '48'], 'PT' => ['Portugal', '351'], 'PR' => ['Puerto Rico', '1'], 'QA' => ['Qatar', '974'],
        'RO' => ['Romania', '40'], 'RU' => ['Russia', '7'], 'RW' => ['Rwanda', '250'], 'KN' => ['Saint Kitts and Nevis', '1'],
        'LC' => ['Saint Lucia', '1'], 'VC' => ['Saint Vincent and the Grenadines', '1'], 'WS' => ['Samoa', '685'], 'SM' => ['San Marino', '378'],
        'ST' => ['São Tomé and Príncipe', '239'], 'SA' => ['Saudi Arabia', '966'], 'SN' => ['Senegal', '221'], 'RS' => ['Serbia', '381'],
        'SC' => ['Seychelles', '248'], 'SL' => ['Sierra Leone', '232'], 'SG' => ['Singapore', '65'], 'SK' => ['Slovakia', '421'],
        'SI' => ['Slovenia', '386'], 'SB' => ['Solomon Islands', '677'], 'SO' => ['Somalia', '252'], 'ZA' => ['South Africa', '27'],
        'KR' => ['South Korea', '82'], 'SS' => ['South Sudan', '211'], 'ES' => ['Spain', '34'], 'LK' => ['Sri Lanka', '94'],
        'SD' => ['Sudan', '249'], 'SR' => ['Suriname', '597'], 'SE' => ['Sweden', '46'], 'CH' => ['Switzerland', '41'],
        'SY' => ['Syria', '963'], 'TW' => ['Taiwan', '886'], 'TJ' => ['Tajikistan', '992'], 'TZ' => ['Tanzania', '255'],
        'TH' => ['Thailand', '66'], 'TL' => ['Timor-Leste', '670'], 'TG' => ['Togo', '228'], 'TO' => ['Tonga', '676'],
        'TT' => ['Trinidad and Tobago', '1'], 'TN' => ['Tunisia', '216'], 'TR' => ['Türkiye', '90'], 'TM' => ['Turkmenistan', '993'],
        'TV' => ['Tuvalu', '688'], 'UG' => ['Uganda', '256'], 'UA' => ['Ukraine', '380'], 'AE' => ['United Arab Emirates', '971'],
        'GB' => ['United Kingdom', '44'], 'US' => ['United States', '1'], 'UY' => ['Uruguay', '598'], 'UZ' => ['Uzbekistan', '998'],
        'VU' => ['Vanuatu', '678'], 'VA' => ['Vatican City', '39'], 'VE' => ['Venezuela', '58'], 'VN' => ['Vietnam', '84'],
        'YE' => ['Yemen', '967'], 'ZM' => ['Zambia', '260'], 'ZW' => ['Zimbabwe', '263'],
    ];

    /**
     * Picker options: Ghana first, then every other country A to Z.
     *
     * @return array<string, string> ISO code => "Ghana +233"
     */
    public static function options(): array
    {
        $countries = self::COUNTRIES;
        uasort($countries, fn ($a, $b) => strcoll($a[0], $b[0]));
        $countries = [self::DEFAULT_COUNTRY => self::COUNTRIES[self::DEFAULT_COUNTRY]] + $countries;

        return array_map(fn ($country) => "{$country[0]} +{$country[1]}", $countries);
    }

    /**
     * "+233241234567" from a country and what the person typed. A number typed with its own
     * "+code" is kept as typed. Null when it doesn't look like a phone number (6–15 digits).
     */
    public static function format(string $country, string $typed): ?string
    {
        $digits = preg_replace('/\D/', '', $typed);
        if (! str_starts_with(trim($typed), '+')) {
            if (! in_array($country, self::KEEPS_LEADING_ZERO, true)) {
                $digits = preg_replace('/^0/', '', $digits);
            }
            $digits = self::COUNTRIES[$country][1].$digits;
        }

        return strlen($digits) >= 6 && strlen($digits) <= 15 ? '+'.$digits : null;
    }
}
