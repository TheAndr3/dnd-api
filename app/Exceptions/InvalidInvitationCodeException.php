<?php

namespace App\Exceptions;

use Exception;

class InvalidInvitationCodeException extends Exception
{
    protected $message = 'Invalid invitation code';
    protected $code = 404;
}
