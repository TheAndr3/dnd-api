<?php

namespace App\Exceptions;

use Exception;

class CharacterAlreadyInCampaignException extends Exception
{
    protected $message = 'Character is already in this campaign';
    protected $code = 409;
}
