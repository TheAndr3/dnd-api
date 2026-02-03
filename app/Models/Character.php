<?php

namespace App\Models;

use App\Enums\CharacterClass;
use App\Enums\CharacterRace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Character extends Model
{
    protected $fillable = [
        'name',
        'race',
        'class',
        'level',
        'strength',
        'dexterity',
        'constitution',
        'intelligence',
        'wisdom',
        'charisma',
        'hit_points',
        'armor_class',
        'speed',
        'initiative',
        'mana_points',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'class' => CharacterClass::class,
            'race' => CharacterRace::class,
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class)->withTimestamps();
    }

    public function isOwnedBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }
        return $this->user_id === $user->id;
    }
}
