<?php

namespace App\Enum;

enum StatusTicketEnum: string
{
    case VALIDE = 'valide';
    case UTILISEE = 'utilisee';
    case EXPIREE = 'expiree';
}

function getStatusTicketEnum(string $status): StatusTicketEnum
{
    return match ($status) {
        'valide' => StatusTicketEnum::VALIDE,
        'utilisee' => StatusTicketEnum::UTILISEE,
        'expiree' => StatusTicketEnum::EXPIREE,
        default => throw new \InvalidArgumentException('Invalid ticket status'),
    };
}
