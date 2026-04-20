<?php

namespace App\Exception;

final class ProfileEmailAlreadyUsedException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cet email est déjà utilisé par un autre compte.');
    }
}
