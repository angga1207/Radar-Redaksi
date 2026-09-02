<?php

namespace App\Support;

use DOMDocument;
use DOMElement;

final class HtmlSanitizer
{
    /** @var array<int, string> */
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'blockquote', 'h2', 'h3', 'a', 'figure', 'figcaption', 'img'];

    public static function clean(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $body = $document->getElementsByTagName('body')->item(0);
        if (! $body) {
            return '';
        }
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }
            if (! in_array($element->tagName, self::ALLOWED_TAGS, true)) {
                $element->replaceWith(...iterator_to_array($element->childNodes));

                continue;
            }
            if ($element->tagName === 'img') {
                $src = $element->getAttribute('src');
                $path = (string) parse_url($src, PHP_URL_PATH);
                if (! str_starts_with($path, '/storage/article-body/')) {
                    $element->remove();

                    continue;
                }
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $allowedAttribute = ($element->tagName === 'a' && $attribute->name === 'href')
                    || ($element->tagName === 'img' && in_array($attribute->name, ['src', 'alt', 'width', 'height'], true));
                if (! $allowedAttribute) {
                    $element->removeAttribute($attribute->name);
                }
            }
            if ($element->tagName === 'a') {
                $href = $element->getAttribute('href');
                if ($href !== '' && ! str_starts_with($href, '/') && ! filter_var($href, FILTER_VALIDATE_URL)) {
                    $element->removeAttribute('href');
                }
                $element->setAttribute('rel', 'nofollow noopener');
            }
        }

        return collect(iterator_to_array($body->childNodes))->map(fn ($node): string => $document->saveHTML($node))->implode('');
    }
}
