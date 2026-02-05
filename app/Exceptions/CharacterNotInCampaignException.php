<?php

namespace App\Exceptions;

use Exception;

class CharacterNotInCampaignException extends Exception
{
    protected $message = 'Character is not in this campaign';
    protected $code = 404;
}
