<?php

namespace App\Enum;

enum TypeBien: string
{
    case APPARTEMENT = 'APPARTEMENT';
    case VILLA = 'VILLA';
    case STUDIO = 'STUDIO';
    case BUREAU = 'BUREAU';
    case COMMERCE = 'COMMERCE';
    case TERRAIN = 'TERRAIN';
    case AUTRE = 'AUTRE';
}
