<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Cache les adresses e-mail et les numéros de téléphone aux robots collecteurs.
 *
 * Toutes les pages publiques passent ici, y compris les textes encodés dans
 * l'administration : aucune adresse ni aucun numéro n'apparaît en clair dans le
 * code HTML envoyé. Un petit script les remet en place dans le navigateur, si
 * bien que le visiteur voit et clique exactement comme avant.
 *
 * - liens mailto: et tel: → href chiffré dans data-cx, remis par le script ;
 * - adresses et numéros dans le texte → <span data-cx-t> contenant le texte
 *   à l'envers, affiché à l'endroit par CSS (lisible même sans JavaScript),
 *   puis remplacé par le vrai texte par le script ;
 * - dans <head> (titre, description, Open Graph…) → remplacés par une mention neutre.
 */
class ProtectContacts
{
    /** Clé de brouillage (pas un secret : il s'agit seulement de dérouter les robots). */
    public const KEY = 'Boverie';

    private const EMAIL = '/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i';

    /** Numéros belges : GSM (04xx xx xx xx) et fixes (04 xxx xx xx, 02 xxx xx xx…), avec ou sans +32. */
    private const PHONE = '/(?<![\d\/.,])(?:\+32[\s.\/]?|0032[\s.\/]?|0)(?:4[5-9]\d(?:[\s.\/]?\d{2}){3}|\d{1,2}(?:[\s.\/]?\d{2,3})(?:[\s.\/]?\d{2}){2})(?![\d\/,]|\.\d)/';

    private const SKIP = ['admin', 'admin/*', 'livewire*', 'filament/*', 'installation', 'installation/*'];

    private bool $changed = false;

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! $response instanceof Response
            || $request->is(...self::SKIP)
            || ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '') {
            return $response;
        }

        $response->setContent($this->protect($html));

        return $response;
    }

    public function protect(string $html): string
    {
        $this->changed = false;

        // On ne touche jamais au contenu des scripts, styles et zones de saisie.
        $parts = preg_split('#(<head\b.*?</head>|<script\b.*?</script>|<style\b.*?</style>|<textarea\b.*?</textarea>)#is', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $parts[$i] = $this->protectBody($part);
            } elseif (stripos($part, '<head') === 0) {
                $parts[$i] = $this->scrubHead($part);
            }
        }

        $html = implode('', $parts);

        if ($this->changed) {
            $html = str_ireplace('</body>', $this->script().'</body>', $html);
        }

        return $html;
    }

    public static function encode(string $value): string
    {
        return base64_encode(self::xor($value));
    }

    public static function decode(string $value): string
    {
        return self::xor((string) base64_decode($value, true));
    }

    private static function xor(string $raw): string
    {
        $key = self::KEY;
        $out = '';
        for ($i = 0, $n = strlen($raw); $i < $n; $i++) {
            $out .= $raw[$i] ^ $key[$i % strlen($key)];
        }

        return $out;
    }

    private function protectBody(string $html): string
    {
        $chunks = preg_split('/(<[^>]*>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);

        foreach ($chunks as $i => $chunk) {
            if ($chunk === '') {
                continue;
            }
            $chunks[$i] = $i % 2 === 1 ? $this->protectTag($chunk) : $this->protectText($chunk);
        }

        return implode('', $chunks);
    }

    /** Liens mailto: / tel: : l'adresse quitte l'attribut href. */
    private function protectTag(string $tag): string
    {
        if (! preg_match('/^<a\b/i', $tag)) {
            return $tag;
        }

        return preg_replace_callback('/\bhref\s*=\s*(["\'])\s*((?:mailto|tel):[^"\']*)\1/i', function ($m) {
            $href = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // « mailto:?subject=… » (bouton de partage) ne contient aucune adresse.
            if (stripos($href, 'mailto:') === 0 && ! str_contains($href, '@')) {
                return $m[0];
            }
            $this->changed = true;

            return 'href="'.e(route('page', 'contact')).'" data-cx="'.self::encode($href).'" rel="nofollow"';
        }, $tag);
    }

    /** Adresses et numéros écrits dans le texte. */
    private function protectText(string $text): string
    {
        if (! str_contains($text, '@') && ! preg_match('/\d/', $text)) {
            return $text;
        }

        $wrap = function (array $m) {
            $this->changed = true;
            $value = html_entity_decode($m[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $reversed = implode('', array_reverse(mb_str_split($value)));

            return '<span data-cx-t="'.self::encode($value).'" style="unicode-bidi:bidi-override;direction:rtl">'.e($reversed).'</span>';
        };

        $text = preg_replace_callback(self::EMAIL, $wrap, $text);

        return preg_replace_callback(self::PHONE, $wrap, $text);
    }

    private function scrubHead(string $head): string
    {
        $head = preg_replace(self::EMAIL, 'adresse sur notre site', $head);

        return preg_replace(self::PHONE, 'numéro sur notre site', $head);
    }

    private function script(): string
    {
        $key = json_encode(array_map('ord', str_split(self::KEY)));

        return <<<HTML
<script>(function(){var k={$key};function d(s){var b=atob(s),u=new Uint8Array(b.length);for(var i=0;i<b.length;i++)u[i]=b.charCodeAt(i)^k[i%k.length];return new TextDecoder().decode(u)}
document.querySelectorAll('[data-cx]').forEach(function(a){a.setAttribute('href',d(a.getAttribute('data-cx')));a.removeAttribute('data-cx');a.removeAttribute('rel')});
document.querySelectorAll('[data-cx-t]').forEach(function(s){s.textContent=d(s.getAttribute('data-cx-t'));s.removeAttribute('data-cx-t');s.removeAttribute('style')});})();</script>

HTML;
    }
}
