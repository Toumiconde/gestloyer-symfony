<?php

namespace App\Enum;

enum StatutContrat: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case ACTIF = 'ACTIF';
    case EXPIRE = 'EXPIRE';
    case RESILIE = 'RESILIE';
    case SUSPENDU = 'SUSPENDU';
}
