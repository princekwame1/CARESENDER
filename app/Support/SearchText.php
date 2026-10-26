<?php

namespace App\Support;

use App\Models\Facility;
use App\Models\Service;

/** Builds the normalised search_text columns used by the search engine. */
class SearchText
{
    public static function facility(Facility $f): string
    {
        return SearchNormalizer::normalise(implode(' ', [
            $f->name, $f->facility_type, $f->area, $f->address, $f->district, $f->city,
            implode(' ', array_map(fn ($c) => config("caresenda.diagnostic_categories.$c", $c), $f->diagnostic_categories ?? [])),
        ]));
    }

    public static function service(Service $s, Facility $f): string
    {
        return SearchNormalizer::normalise(implode(' ', [
            $s->service, $s->lab_category, $s->specialist_name, $f->name, $f->area, $f->city, $s->type === 'specialist' ? 'specialist' : '',
        ]));
    }
}
