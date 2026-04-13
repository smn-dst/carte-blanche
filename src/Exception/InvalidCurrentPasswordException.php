<?php

namespace App\Exception;

final class InvalidCurrentPasswordException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Le mot de passe actuel est incorrect.');
    }
}
