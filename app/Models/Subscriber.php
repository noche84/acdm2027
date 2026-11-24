<?php

namespace App\Models;

use App\Enums\SubscriberStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Subscriber extends Model
{
    protected $fillable = ['email', 'status', 'token', 'source', 'consented_at', 'consent_ip', 'confirmed_at', 'unsubscribed_at'];

    protected function casts(): array
    {
        return [
            'status' => SubscriberStatus::class,
            'consented_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Subscriber $subscriber) {
            $subscriber->email = Str::lower(trim($subscriber->email));
            $subscriber->token = $subscriber->token ?: Str::random(48);
        });
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', SubscriberStatus::Confirmed->value);
    }

    public function confirm(): void
    {
        $this->forceFill([
            'status' => SubscriberStatus::Confirmed,
            'confirmed_at' => now(),
            'unsubscribed_at' => null,
        ])->save();
    }

    public function unsubscribe(): void
    {
        $this->forceFill([
            'status' => SubscriberStatus::Unsubscribed,
            'unsubscribed_at' => now(),
        ])->save();
    }

    public function getUnsubscribeUrlAttribute(): string
    {
        return route('newsletter.unsubscribe', $this->token);
    }

    public function getConfirmUrlAttribute(): string
    {
        return route('newsletter.confirm', $this->token);
    }
}
