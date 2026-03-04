<?php

namespace App\Exception;

final class RestaurantNotFoundException extends \DomainException
{
    public function __construct(int $id)
    {
        parent::__construct(sprintf('Le restaurant #%d est introuvable.', $id));
    }
}
