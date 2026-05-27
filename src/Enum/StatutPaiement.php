<?php

namespace App\Enum;

enum StatutPaiement: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case PARTIEL    = 'PARTIEL';
    case COMPLET    = 'COMPLET';
    case VALIDE     = 'VALIDE';
    case EN_RETARD  = 'EN_RETARD';
    case ANNULE     = 'ANNULE';
}
