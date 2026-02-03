<?php

namespace App\Enums;

enum CharacterRace: string
{
    case HUMAN = 'human';
    case ELF = 'elf';
    case ORC = 'orc';
    case DWARF = 'dwarf';
    case HALFLING = 'halfling';
    case TIEFLING = 'tiefling';
    case GNOME = 'gnome';
    case DRACONIC = 'draconic';
    
    public function roles(): string
    {
        return match($this){
            self::HUMAN => 'Human',
            self::ELF => 'Elf',
            self::ORC => 'Orc',
            self::DWARF => 'Dwarf',
            self::HALFLING => 'Halfling',
            self::TIEFLING => 'Tiefling',
            self::GNOME => 'Gnome',
            self::DRACONIC => 'Draconic',
        };
    }
}