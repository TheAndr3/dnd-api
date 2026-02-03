<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Campaign extends Model
{
    protected $fillable = [
        'name',
        'invitation_code',
        'gm_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Campaign $campaign) {
            if (empty($campaign->invitation_code)) {
                $campaign->invitation_code = Str::upper(Str::random(8));
            }
        });
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gm_id');
    }

    public function characters(): BelongsToMany
    {
        return $this->belongsToMany(Character::class)->withTimestamps();
    }

    public function isMaster(User $user): bool
    {
        return $this->gm_id === $user->id;
    }
}
