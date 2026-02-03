<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'player_id',
    ];
}
