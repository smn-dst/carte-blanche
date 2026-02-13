<?php
// Source
// https://symfony.com/doc/current/components/http_foundation/enum_converter.html

namespace App\Enum;

enum StatusOrderEnum: string
{
    case EN_ATTENTE = 'en_attente';
    case PAYEE = 'payee';
    case REMBOURSEMENT_PARTIEL = 'remboursement_partiel';
    case REMBOURSEE = 'remboursee';
    case ECHOUEE = 'echouee';
}

function getStatusOrderEnum(string $status): StatusOrderEnum
{
    return match ($status) {
        'en_attente' => StatusOrderEnum::EN_ATTENTE,
        'payee' => StatusOrderEnum::PAYEE,
        'remboursement_partiel' => StatusOrderEnum::REMBOURSEMENT_PARTIEL,
        'remboursee' => StatusOrderEnum::REMBOURSEE,
        'echouee' => StatusOrderEnum::ECHOUEE,
        default => throw new \InvalidArgumentException('Invalid status'),
    };
}