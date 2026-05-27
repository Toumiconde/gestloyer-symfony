<?php

namespace App\Enum;

enum StatutIncident: string
{
    case DECLARE = 'DECLARE';
    case EN_COURS = 'EN_COURS';
    case DEVIS_SOUMIS = 'DEVIS_SOUMIS';
    case APPROUVE = 'APPROUVE';
    case RESOLU = 'RESOLU';
    case CLOTURE = 'CLOTURE';
}
