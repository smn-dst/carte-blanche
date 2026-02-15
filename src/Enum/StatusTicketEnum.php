<?php

namespace App\Enum;

enum StatusTicketEnum: string
{
    case VALIDE = 'valide';
    case UTILISE = 'utilisee';
    case EXPIRE = 'expiree';
}

function getStatusTicketEnum(string $status): StatusTicketEnum
{
    return match ($status) {
        'valide' => StatusTicketEnum::VALIDE,
        'utilisee' => StatusTicketEnum::UTILISE,
        'expiree' => StatusTicketEnum::EXPIRE,
        default => throw new \InvalidArgumentException('Invalid ticket status'),
    };
}
