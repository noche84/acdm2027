<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Nettoie le HTML saisi (ancienne base, copier-coller Word, éditeur riche)
 * pour ne garder qu'un sous-ensemble sûr et sobre de balises.
 */
class HtmlCleaner
{
    /** Balises conservées, avec leurs attributs autorisés. */
    private const ALLOWED = [
        'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [],
        'sup' => [], 'sub' => [], 'small' => [],
        'h2' => [], 'h3' => [], 'h4' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [], 'hr' => [],
        'a' => ['href'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
    ];

    /** Balises renommées vers leur équivalent sémantique. */
    private const RENAME = ['b' => 'strong', 'i' => 'em', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4', 'strike' => 's', 'del' => 's'];

    /** Balises supprimées avec tout leur contenu. */
    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'head', 'title', 'meta', 'link', 'xml'];

    public static function clean(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        $html = self::preFix($html);

        if (trim(strip_tags($html)) === '' && ! str_contains($html, '<hr')) {
            return null;
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('__root');
        if (! $root) {
            return trim(strip_tags($html)) ?: null;
        }

        self::walk($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }

        return self::postFix($out);
    }

    /** Texte brut, pour les extraits, la recherche et les métadonnées. */
    public static function toText(?string $html, int $limit = 0): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br />', '<br/>', '</p>', '</li>'], ' ', (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return $limit > 0 ? \Illuminate\Support\Str::limit($text, $limit) : $text;
    }

    private static function preFix(string $html): string
    {
        // Commentaires conditionnels de Word et commentaires HTML.
        $html = preg_replace('/<!--\[if.*?<!\[endif\]-->/is', '', $html);
        $html = preg_replace('/<!--.*?-->/s', '', $html);
        // Balises Office (<o:p>, <w:…>, <v:…>).
        $html = preg_replace('#</?(o|w|v|m):[^>]*>#i', '', $html);
        // Coquilles rencontrées dans l'ancienne base : « <br /< ».
        $html = preg_replace('#<br\s*/?<(?![a-z/])#i', '<br />', $html);
        $html = str_replace(["\r\n", "\r"], "\n", $html);
        // Séparateurs de ligne Unicode (copier-coller depuis Mail / Pages).
        $html = str_replace(["\u{2028}", "\u{2029}"], '<br>', $html);

        return $html;
    }

    private static function postFix(string $html): ?string
    {
        $html = str_replace(["\n", "\t"], ' ', $html);
        $html = preg_replace('/ {2,}/', ' ', $html);
        $html = str_replace('&nbsp;', ' ', $html);
        // Paragraphes vides et <br> en trop.
        $html = preg_replace('#<p>(\s|<br\s*/?>)*</p>#i', '', $html);
        $html = preg_replace('#(<br\s*/?>\s*){3,}#i', '<br><br>', $html);
        $html = preg_replace('#^(\s|<br\s*/?>)+|(\s|<br\s*/?>)+$#i', '', $html);
        $html = preg_replace('#\s*<br>\s*#', '<br>', $html);

        $html = trim($html);

        return $html === '' ? null : $html;
    }

    private static function walk(DOMNode $node): void
    {
        // Copie de la liste : on modifie l'arbre pendant le parcours.
        $children = iterator_to_array($node->childNodes);

        foreach ($children as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $node->removeChild($child);

                continue;
            }

            if (isset(self::RENAME[$tag])) {
                $child = self::rename($child, self::RENAME[$tag]);
                $tag = self::RENAME[$tag];
            }

            // <span style="font-weight:bold"> de Word → <strong>
            if ($tag === 'span') {
                $style = strtolower($child->getAttribute('style'));
                if (preg_match('/font-weight\s*:\s*(bold|[6-9]00)/', $style)) {
                    $child = self::rename($child, 'strong');
                    $tag = 'strong';
                } elseif (str_contains($style, 'font-style:italic') || str_contains($style, 'font-style: italic')) {
                    $child = self::rename($child, 'em');
                    $tag = 'em';
                }
            }

            self::walk($child);

            if (! array_key_exists($tag, self::ALLOWED)) {
                self::unwrap($child);

                continue;
            }

            foreach (iterator_to_array($child->attributes) as $attr) {
                if (! in_array($attr->name, self::ALLOWED[$tag], true)) {
                    $child->removeAttribute($attr->name);
                }
            }

            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                if (! preg_match('#^(https?:|mailto:|tel:|/)#i', $href)) {
                    self::unwrap($child);

                    continue;
                }
                if (preg_match('#^https?:#i', $href)) {
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener');
                }
            }
        }
    }

    private static function rename(DOMElement $el, string $newTag): DOMElement
    {
        $new = $el->ownerDocument->createElement($newTag);
        foreach (iterator_to_array($el->attributes) as $attr) {
            $new->setAttribute($attr->name, $attr->value);
        }
        while ($el->firstChild) {
            $new->appendChild($el->firstChild);
        }
        $el->parentNode->replaceChild($new, $el);

        return $new;
    }

    private static function unwrap(DOMElement $el): void
    {
        $parent = $el->parentNode;
        while ($el->firstChild) {
            $parent->insertBefore($el->firstChild, $el);
        }
        $parent->removeChild($el);
    }
}
