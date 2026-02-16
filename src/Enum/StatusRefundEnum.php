<?php

namespace App\Enum;

enum StatusRefundEnum: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVE = 'approuve';
    case REFUSE = 'refuse';
    case TRAITE = 'traite';
}

function getStatusRefundEnum(string $status): StatusRefundEnum
{
    return match ($status) {
        'en_attente' => StatusRefundEnum::EN_ATTENTE,
        'approuve' => StatusRefundEnum::APPROUVE,
        'refuse' => StatusRefundEnum::REFUSE,
        'traite' => StatusRefundEnum::TRAITE,
        default => throw new \InvalidArgumentException('Invalid refund status'),
    };
}
