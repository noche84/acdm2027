<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Partner extends Model
{
    public const CATEGORIES = [
        'pouvoirs-publics' => 'Pouvoirs publics',
        'lieux' => 'Lieux d\'accueil',
        'soutien' => 'Soutiens et mécènes',
        'medias' => 'Médias',
    ];

    protected $fillable = ['name', 'logo', 'url', 'category', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort')->orderBy('name');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }
}
