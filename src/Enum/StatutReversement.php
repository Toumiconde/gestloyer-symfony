<?php

namespace App\Enum;

enum StatutReversement: string
{
    case CALCULE = 'CALCULE';
    case EN_ATTENTE = 'EN_ATTENTE';
    case VERSE = 'VERSE';
    case ANNULE = 'ANNULE';
}
