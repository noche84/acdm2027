<?php

namespace App\Models;

use App\Enums\Brand;
use App\Support\HtmlCleaner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Series extends Model
{
    protected $table = 'series';

    protected $fillable = ['brand', 'name', 'slug', 'description', 'default_time', 'default_venue_id', 'default_price_info', 'website', 'sort', 'is_active'];

    protected function casts(): array
    {
        return [
            'brand' => Brand::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Series $series) {
            $series->slug = $series->slug ?: Str::slug($series->name);
            $series->description = HtmlCleaner::clean($series->description);
        });
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function defaultVenue(): BelongsTo
    {
        return $this->belongsTo(Venue::class, 'default_venue_id');
    }
}
