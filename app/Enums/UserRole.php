<?php

namespace App\Enums;

enum UserRole: string
{
    case PLAYER = 'player';
    case GM = 'gm';


    public function roles(): string
    {
        return match($this){
            self::PLAYER => 'Player',
            self::GM => 'GM',
        };
    }
}
