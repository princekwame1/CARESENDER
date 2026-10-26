<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Support\HtmlString;

/**
 * Cleans HTML from the Quill editor down to a small safe set of tags, and renders
 * stored values (plain text or HTML) for the website.
 */
class RichText
{
    private const ALLOWED = ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li'];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'template', 'noscript'];

    public static function sanitize(?string $html): ?string
    {
        $html = trim((string) $html);
        if ($html === '' || self::isEmpty($html)) {
            return null;
        }
        if (! str_contains($html, '<')) {
            return $html; // plain text stays plain
        }

        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('root') ?? $doc->documentElement;
        self::clean($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        $out = trim(str_replace('&nbsp;', ' ', $out));
        // Drop empty paragraphs Quill leaves at the start or end.
        $out = preg_replace('#^(?:<p>(?:<br>)?</p>)+|(?:<p>(?:<br>)?</p>)+$#', '', $out);

        return self::isEmpty($out) ? null : $out;
    }

    /** Render a stored value: HTML as-is (it was cleaned on save), plain text as paragraphs. */
    public static function render(?string $value): HtmlString
    {
        $value = trim((string) $value);
        if ($value === '') {
            return new HtmlString('');
        }
        if (str_contains($value, '<')) {
            return new HtmlString(self::sanitize($value) ?? '');
        }
        $paras = preg_split('/\R\s*\R/', $value);

        return new HtmlString(implode('', array_map(fn ($p) => '<p>'.nl2br(e(trim($p))).'</p>', $paras)));
    }

    private static function clean(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                if ($child->nodeType === XML_COMMENT_NODE) {
                    $node->removeChild($child);
                }

                continue;
            }
            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }
            self::clean($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                // Unknown tag (e.g. Quill's <span class="ql-ui">): keep its text, drop the tag.
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            $keep = [];
            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                if (preg_match('#^(https?://|mailto:|tel:|/)#i', $href)) {
                    $keep = ['href' => $href];
                    if (preg_match('#^https?://#i', $href)) {
                        $keep += ['target' => '_blank', 'rel' => 'noopener'];
                    }
                }
            }
            if ($tag === 'li' && in_array($child->getAttribute('data-list'), ['bullet', 'ordered'], true)) {
                $keep = ['data-list' => $child->getAttribute('data-list')];
            }
            foreach (iterator_to_array($child->attributes) as $attr) {
                $child->removeAttribute($attr->nodeName);
            }
            foreach ($keep as $k => $v) {
                $child->setAttribute($k, $v);
            }
        }
    }

    private static function isEmpty(string $html): bool
    {
        return trim(strip_tags(str_replace('&nbsp;', ' ', $html))) === '';
    }
}
