<?php

namespace App\Enum;

enum StatusVendorRequestEnum: string
{
    case EN_ATTENTE = 'en_attente';
    case APPROUVE = 'approuve';
    case REFUSE = 'refuse';
}
