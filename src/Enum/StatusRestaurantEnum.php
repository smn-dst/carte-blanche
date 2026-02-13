<?php

namespace App\Enum;

enum StatusRestaurantEnum: string
{
    case BROUILLON = 'brouillon';
    case EN_MODERATION = 'en_moderation';
    case PUBLIE = 'publie';
    case EN_PAUSE = 'en_pause';
    case PROGRAMME = 'programme';
    case EN_COURS = 'en_cours';
    case TERMINEE = 'terminee';
    case VENDU = 'vendu';
    case ANNULE = 'annule';
}

function getStatusRestaurantEnum(string $status): StatusRestaurantEnum
{
    return match ($status) {
        'brouillon' => StatusRestaurantEnum::BROUILLON,
        'en_moderation' => StatusRestaurantEnum::EN_MODERATION,
        'publie' => StatusRestaurantEnum::PUBLIE,
        'en_pause' => StatusRestaurantEnum::EN_PAUSE,
        'programme' => StatusRestaurantEnum::PROGRAMME,
        'en_cours' => StatusRestaurantEnum::EN_COURS,
        'terminee' => StatusRestaurantEnum::TERMINEE,
        'vendu' => StatusRestaurantEnum::VENDU,
        'annule' => StatusRestaurantEnum::ANNULE,
        default => throw new \InvalidArgumentException('Invalid restaurant status'),
    };
}
