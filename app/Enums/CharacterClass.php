<?php

namespace App\Enums;

enum CharacterClass: string
{
    case BARBARIAN = 'barbarian';
    case BARD = 'bard';
    case CLERIC = 'cleric';
    case DRUID = 'druid';
    case PALADIN = 'paladin';
    case RANGER = 'ranger';
    case ROGUE = 'rogue';
    case SORCERER = 'sorcerer';
    case WIZARD = 'wizard';

    public function roles(): string
    {
        return match($this){
            self::BARBARIAN => 'Barbarian',
            self::BARD => 'Bard',
            self::CLERIC => 'Cleric',
            self::DRUID => 'Druid',
            self::PALADIN => 'Paladin',
            self::RANGER => 'Ranger',
            self::ROGUE => 'Rogue',
            self::SORCERER => 'Sorcerer',
            self::WIZARD => 'Wizard',
        };
    }

}
