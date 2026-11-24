<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\InstagramImage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Images Instagram d'un concert : réservées aux personnes connectées à l'administration. */
class InstagramController extends Controller
{
    public function image(Request $request, Event $event, string $format, InstagramImage $images)
    {
        abort_unless($request->user()?->is_active, 403);

        $name = 'instagram-'.$event->starts_at->format('Y-m-d').'-'.Str::slug(Str::limit($event->display_title, 40, '')).'-'.$format.'.png';

        return response($images->render($event, $format), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'Content-Disposition' => ($request->boolean('telecharger') ? 'attachment' : 'inline').'; filename="'.$name.'"',
        ]);
    }
}
