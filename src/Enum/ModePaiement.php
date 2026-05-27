<?php

namespace App\Enum;

enum ModePaiement: string
{
    case ESPECES = 'ESPECES';
    case VIREMENT = 'VIREMENT';
    case ORANGE_MONEY = 'ORANGE_MONEY';
    case MTN_MOMO = 'MTN_MOMO';
    case CHEQUE = 'CHEQUE';
    case AUTRE = 'AUTRE';
}
