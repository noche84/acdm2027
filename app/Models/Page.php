<?php

namespace App\Models;

use App\Support\HtmlCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Page extends Model
{
    protected $fillable = ['slug', 'title', 'menu_title', 'content', 'meta_description', 'is_system', 'is_published'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            $page->slug = $page->slug ?: Str::slug($page->title);
            $page->content = HtmlCleaner::clean($page->content);
            if (Auth::check()) {
                $page->updated_by = Auth::id();
            }
        });

        static::deleting(fn (Page $page) => ! $page->is_system);
    }

    public static function findSystem(string $slug): ?self
    {
        return static::where('slug', $slug)->where('is_published', true)->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
