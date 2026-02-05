<?php

namespace App\Exceptions;

use Exception;

class CharacterOwnershipException extends Exception
{
    protected $message = 'You can only add your own characters';
    protected $code = 403;
}
