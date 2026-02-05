<?php

namespace App\Exceptions;

use Exception;

class CharacterAlreadyInCampaignException extends Exception
{
    protected $message = 'Character already in campaign';
    protected $code = 400;
}
