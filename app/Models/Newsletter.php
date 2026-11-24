<?php

namespace App\Models;

use App\Enums\Brand;
use App\Enums\NewsletterStatus;
use App\Support\HtmlCleaner;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Newsletter extends Model
{
    protected $fillable = ['subject', 'body', 'include_upcoming', 'upcoming_count'];

    protected function casts(): array
    {
        return [
            'status' => NewsletterStatus::class,
            'include_upcoming' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(fn (Newsletter $n) => $n->body = HtmlCleaner::clean($n->body));
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function isSent(): bool
    {
        return $this->status === NewsletterStatus::Sent;
    }

    /** @return Collection<int, Event> */
    public function upcomingEvents(): Collection
    {
        if (! $this->include_upcoming) {
            return new Collection;
        }

        return Event::query()->published()->upcoming()
            ->with(['venue', 'series'])
            ->limit(max(1, (int) $this->upcoming_count))
            ->get();
    }
}
