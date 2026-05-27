<?php

namespace App\Enum;

enum PrioriteIncident: string
{
    case FAIBLE = 'FAIBLE';
    case NORMAL = 'NORMAL';
    case URGENT = 'URGENT';
    case CRITIQUE = 'CRITIQUE';
}
