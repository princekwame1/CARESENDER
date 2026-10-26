<?php

use App\Support\Cms;
use App\Support\RichText;
use Illuminate\Support\HtmlString;

if (! function_exists('cms')) {
    /** Editable website text managed from the dashboard (see config/cms.php). */
    function cms(string $key, ?string $fallback = null): string
    {
        return Cms::get($key, $fallback);
    }
}

if (! function_exists('cms_html')) {
    /** Formatted website text (from the rich text editor) ready to output. */
    function cms_html(string $key): HtmlString
    {
        return RichText::render(Cms::get($key));
    }
}
