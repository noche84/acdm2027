<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = ['name', 'room', 'street', 'postal_code', 'city', 'map_url', 'website', 'access_info', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /** « Musée de la Boverie – auditorium » */
    public function getLabelAttribute(): string
    {
        return $this->room ? "{$this->name} – {$this->room}" : $this->name;
    }

    /** « Parc de la Boverie 3, 4020 Liège » */
    public function getAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->street,
            trim(($this->postal_code ?? '').' '.($this->city ?? '')),
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    public function getMapLinkAttribute(): ?string
    {
        if ($this->map_url) {
            return $this->map_url;
        }

        $query = trim($this->name.' '.($this->address ?? $this->city));

        return 'https://www.google.com/maps/search/?api=1&query='.urlencode($query);
    }
}
