<?php

namespace App\Services;

use App\Enums\Brand;
use App\Models\Event;
use App\Support\HtmlCleaner;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Fabrique l'image Instagram d'un concert, aux couleurs du site (extension GD, présente chez OVH).
 *
 *  - « post »  : 1080 × 1350 (format portrait du fil Instagram et de Facebook) ;
 *  - « story » : 1080 × 1920 (stories et reels), avec les marges que l'interface d'Instagram recouvre.
 */
class InstagramImage
{
    public const FORMATS = [
        'post' => [1080, 1350],
        'story' => [1080, 1920],
    ];

    private const CREAM = [246, 241, 234];
    private const INK = [38, 32, 30];
    private const GREY = [107, 98, 92];
    private const LIGHT = [243, 227, 211];
    private const GOLD = [242, 184, 75];

    private GdImage $img;
    private int $w;
    private int $h;
    private array $tone;

    public function render(Event $event, string $format = 'post'): string
    {
        [$this->w, $this->h] = self::FORMATS[$format] ?? self::FORMATS['post'];
        $story = $format === 'story';
        $this->tone = $event->brand === Brand::FoliaMusica ? [180, 83, 9] : [139, 30, 45];

        $this->img = imagecreatetruecolor($this->w, $this->h);
        imagealphablending($this->img, true);
        imagefilledrectangle($this->img, 0, 0, $this->w, $this->h, $this->color(self::CREAM));

        $pad = 80;
        // Les stories ont la barre du compte en haut et le champ « Envoyer un message » en bas.
        $top = $story ? 200 : 0;
        $bottomSafe = $story ? 260 : 0;

        // ── Bandeau de la marque ────────────────────────────────────────────────
        $bandH = $top + 230;
        imagefilledrectangle($this->img, 0, 0, $this->w, $bandH, $this->color($this->tone));
        $x = $pad;
        if ($event->brand === Brand::Acdm && is_file($emblem = public_path('icon-512.png'))) {
            $this->paste($emblem, $x, $top + 50, 130, 130, false);
            $x += 160;
        }
        $this->text($event->brand->getLabel(), 'serif-italic', 76, $x, $top + 125, [255, 255, 255]);
        $sub = $event->brand === Brand::Acdm ? 'Les Amis des Concerts du Dimanche Matin · Liège' : 'Concerts à Liège et alentours';
        $this->text($sub, 'sans', 25, $x + 2, $top + 172, self::LIGHT);

        // ── Affiche (si elle existe) ────────────────────────────────────────────
        $poster = $event->poster ? Storage::disk('public')->path($event->poster) : null;
        $poster = $poster && is_file($poster) ? $poster : null;

        $footerY = $this->h - $bottomSafe - 150;
        $areaTop = $bandH;
        $areaBottom = $footerY - ($event->venue ? 70 : 20);
        $textX = $pad;
        $textW = $this->w - 2 * $pad;

        if ($poster && $story) {
            // Story : l'affiche au centre, le texte dessous. On réserve d'abord une hauteur minimale
            // pour l'affiche ; sa taille définitive dépend de la place que laisse le texte.
            $areaTop = $bandH + 50 + 380;
        } elseif ($poster) {
            // Publication : l'affiche en vignette à droite, le texte à gauche.
            $this->paste($poster, $this->w - $pad - 340, $bandH + 70, 340, 500, true, alignRight: true);
            $textW = $this->w - 2 * $pad - 340 - 50;
        }

        // On compose d'abord le bloc de texte, puis on le centre verticalement dans l'espace libre.
        $ops = [];
        $y = 0;
        $when = mb_strtoupper($event->starts_at->locale('fr')->isoFormat('dddd D MMMM YYYY'))."\u{00A0}·\u{00A0}".mb_strtoupper($event->time_label);
        foreach ($this->wrap($when, 'sans-bold', 32, $textW, 2) as $i => $line) {
            $y += $i ? 46 : 32;
            $ops[] = [$line, 'sans-bold', 32, $y, $this->tone, 2];
        }
        if ($banner = $event->status->publicBanner()) {
            $y += 54;
            $ops[] = [mb_strtoupper($banner), 'sans-bold', 32, $y, [200, 30, 30], 0];
        }
        $y += 34;

        $title = $event->title ? "«\u{00A0}".$event->title."\u{00A0}»" : $event->display_title;
        [$size, $lines] = $this->fit($title, 'serif-italic', $story ? ($poster ? 78 : 100) : 92, 56, $textW, $poster && ! $story ? 4 : 3);
        foreach ($lines as $line) {
            $y += (int) round($size * 1.04);
            $ops[] = [$line, 'serif-italic', $size, $y, self::INK, 0];
        }
        if ($event->title && $event->ensemble) {
            [$size, $lines] = $this->fit($event->ensemble, 'serif', 52, 36, $textW, 2);
            $y += 14;
            foreach ($lines as $line) {
                $y += (int) round($size * 1.15);
                $ops[] = [$line, 'serif', $size, $y, self::INK, 0];
            }
        }

        $people = collect($event->performerList());
        $lines = $people->isNotEmpty()
            ? $people->map(fn ($p) => $p['name'].($p['role'] ? ', '.$p['role'] : ''))->all()
            : array_values(array_filter(array_map('trim', explode("\n", HtmlCleaner::toText($event->performers ?? '', 400)))));
        $room = max(0, intdiv($areaBottom - $areaTop - 120 - $y - 40, 50));
        // Trop peu de place pour une liste lisible : on laisse l'affiche et la légende parler.
        if (count($lines) > $room && $room < 3) {
            $room = 0;
        }
        if ($lines && $room > 0) {
            $y += 40;
            $shown = array_slice($lines, 0, $room);
            if (count($lines) > count($shown)) {
                $shown[count($shown) - 1] = '… et '.(count($lines) - count($shown) + 1).' autres musiciens';
            }
            foreach ($shown as $line) {
                $y += 50;
                $ops[] = [$this->wrap($line, 'sans', 33, $textW, 1)[0], 'sans', 33, $y, self::INK, 0];
            }
        }

        if ($poster && $story) {
            $ph = min(720, $areaBottom - ($bandH + 50) - $y - 120);
            [, $ph] = $this->paste($poster, 0, $bandH + 50, $this->w - 2 * $pad, $ph, true, center: true);
            $areaTop = $bandH + 50 + $ph + 30;
        }

        $free = $areaBottom - $areaTop - $y;
        $offset = $areaTop + max(70, (int) ($free / 2));
        if ($poster && ! $story) {
            $offset = min($offset, $bandH + 110);
        }
        foreach ($ops as [$text, $font, $px, $oy, $rgb, $tracking]) {
            $this->text($text, $font, $px, $textX, $offset + $oy, $rgb, $tracking);
        }

        // ── Lieu et pied ────────────────────────────────────────────────────────
        if ($event->venue) {
            $venue = $event->venue->name.($event->venue->city ? ' · '.$event->venue->city : '');
            $this->text($this->wrap($venue, 'sans', 28, $this->w - 2 * $pad, 1)[0], 'sans', 28, $pad, $footerY - 30, self::GREY);
        }
        imagefilledrectangle($this->img, 0, $footerY, $this->w, $this->h, $this->color($this->tone));
        $site = preg_replace('/^www\./', '', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'acdm.eu');
        $price = $this->priceLabel($event);
        $this->text('Programme et réservations', 'sans', 26, $pad, $footerY + 62, self::LIGHT);
        $this->text($site, 'sans-bold', 40, $pad, $footerY + 112, [255, 255, 255]);
        if ($price) {
            $bw = $this->width($price, 'sans-bold', 30);
            $this->text($price, 'sans-bold', 30, $this->w - $pad - $bw, $footerY + 105, self::GOLD);
        }

        ob_start();
        imagepng($this->img, null, 6);
        imagedestroy($this->img);

        return (string) ob_get_clean();
    }

    /** Légende prête à copier dans Instagram (les liens n'y sont pas cliquables). */
    public function caption(Event $event): string
    {
        $title = $event->title ? '« '.$event->title.' »' : $event->display_title;
        if ($event->title && $event->ensemble) {
            $title .= ' – '.$event->ensemble;
        }
        $people = collect($event->performerList())->map(fn ($p) => $p['name'].($p['role'] ? ' ('.$p['role'].')' : ''));
        $site = preg_replace('/^www\./', '', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'acdm.eu');

        $parts = [
            $title,
            '🗓 '.ucfirst($event->date_label).' à '.$event->time_label
                .($event->venue ? "\n📍 ".$event->venue->name.($event->venue->city ? ', '.$event->venue->city : '') : ''),
        ];
        if ($people->isNotEmpty()) {
            $parts[] = '🎶 Avec '.$people->implode(', ');
        }
        if ($price = $this->priceLabel($event)) {
            $parts[] = '🎟 '.$price;
        }
        $parts[] = 'Programme et réservations : '.$site;
        $tags = ['#Liège', '#MusiqueClassique', '#Concert', '#MusiqueDeChambre', $event->brand === Brand::Acdm ? '#ACDM' : '#FoliaMusica'];
        if ($event->venue && str_contains(mb_strtolower($event->venue->name), 'boverie')) {
            $tags[] = '#LaBoverie';
        }
        $parts[] = implode(' ', $tags);

        return implode("\n\n", $parts);
    }

    private function priceLabel(Event $event): ?string
    {
        $value = $event->priceValue();
        if ($value === 0.0) {
            return 'Entrée gratuite';
        }
        if ($event->price_info && mb_strlen($event->price_info) <= 28) {
            return $event->price_info;
        }

        return $value ? 'Entrée '.rtrim(rtrim(number_format($value, 2, ',', ''), '0'), ',').' €' : null;
    }

    // ── Outils de dessin ──────────────────────────────────────────────────────

    private function font(string $name): string
    {
        return resource_path('fonts/'.[
            'serif' => 'CormorantGaramond-SemiBold.ttf',
            'serif-italic' => 'CormorantGaramond-SemiBoldItalic.ttf',
            'sans' => 'Inter-Medium.otf',
            'sans-bold' => 'Inter-SemiBold.otf',
        ][$name]);
    }

    private function color(array $rgb): int
    {
        return imagecolorallocate($this->img, ...$rgb);
    }

    /** GD attend des points ; on raisonne en pixels (72 dpi → facteur 0,75). */
    private function pt(int $px): float
    {
        return $px * 0.75;
    }

    private function text(string $text, string $font, int $px, int $x, int $y, array $rgb, int $tracking = 0): void
    {
        // Les textes collés depuis Word contiennent parfois des accents « décomposés » (e + ` ).
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }
        if ($tracking === 0) {
            imagettftext($this->img, $this->pt($px), 0, $x, $y, $this->color($rgb), $this->font($font), $text);

            return;
        }
        foreach (mb_str_split($text) as $char) {
            imagettftext($this->img, $this->pt($px), 0, $x, $y, $this->color($rgb), $this->font($font), $char);
            $x += $this->width($char, $font, $px) + $tracking;
        }
    }

    private function width(string $text, string $font, int $px): int
    {
        $box = imagettfbbox($this->pt($px), 0, $this->font($font), $text);

        return (int) abs($box[2] - $box[0]);
    }

    /** @return list<string> */
    private function wrap(string $text, string $font, int $px, int $maxWidth, int $maxLines): array
    {
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_C) ?: $text;
        }
        // On coupe sur les espaces ordinaires seulement : l'espace insécable colle « » au mot voisin.
        $words = preg_split('/[ \t\r\n]+/', trim($text)) ?: [];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $try = $current === '' ? $word : $current.' '.$word;
            if ($current !== '' && $this->width($try, $font, $px) > $maxWidth) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $try;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, 0, $maxLines);
            $last = $lines[$maxLines - 1];
            while ($last !== '' && $this->width($last.' …', $font, $px) > $maxWidth) {
                $last = mb_substr($last, 0, -1);
            }
            $lines[$maxLines - 1] = rtrim($last, ' ,;.').' …';
        }
        foreach ($lines as $i => $line) {
            while (mb_strlen($line) > 1 && $this->width($line, $font, $px) > $maxWidth) {
                $line = mb_substr($line, 0, -2).'…';
            }
            $lines[$i] = $line;
        }

        return $lines ?: [''];
    }

    /** Réduit la taille jusqu'à ce que le texte tienne sur $maxLines lignes. */
    private function fit(string $text, string $font, int $max, int $min, int $width, int $maxLines): array
    {
        $tokens = preg_split('/[ \t\r\n]+/', trim($text)) ?: [];
        for ($px = $max; $px >= $min; $px -= 4) {
            // Un mot plus long que la colonne serait coupé : on réduit la taille.
            foreach ($tokens as $token) {
                if ($this->width($token, $font, $px) > $width) {
                    continue 2;
                }
            }
            $lines = $this->wrap($text, $font, $px, $width, 99);
            if (count($lines) <= $maxLines) {
                return [$px, $lines];
            }
        }

        return [$min, $this->wrap($text, $font, $min, $width, $maxLines)];
    }

    /** Colle une image (contenue dans la boîte si $contain), avec une fine ombre. Retourne [largeur, hauteur]. */
    private function paste(string $file, int $x, int $y, int $boxW, int $boxH, bool $contain, bool $center = false, bool $alignRight = false): array
    {
        $src = @imagecreatefromstring((string) file_get_contents($file));
        if (! $src) {
            return [0, 0];
        }
        $sw = imagesx($src);
        $sh = imagesy($src);
        $scale = $contain ? min($boxW / $sw, $boxH / $sh) : max($boxW / $sw, $boxH / $sh);
        $dw = (int) round($sw * $scale);
        $dh = (int) round($sh * $scale);
        if ($center) {
            $x = intdiv($this->w - $dw, 2);
        } elseif ($alignRight) {
            $x = $x + $boxW - $dw;
        }
        if ($contain) {
            $shadow = imagecolorallocatealpha($this->img, 0, 0, 0, 105);
            imagefilledrectangle($this->img, $x + 6, $y + 8, $x + $dw + 6, $y + $dh + 8, $shadow);
        }
        imagecopyresampled($this->img, $src, $x, $y, 0, 0, $dw, $dh, $sw, $sh);
        imagedestroy($src);

        return [$dw, $dh];
    }
}
