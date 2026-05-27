<?php

namespace App\Enum;

enum StatutBien: string
{
    case DISPONIBLE = 'DISPONIBLE';
    case OCCUPE = 'OCCUPE';
    case TRAVAUX = 'TRAVAUX';
    case ARCHIVE = 'ARCHIVE';
}
