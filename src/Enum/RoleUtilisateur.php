<?php

namespace App\Enum;

enum RoleUtilisateur: string
{
    case ADMIN = 'ROLE_ADMIN';
    case GESTIONNAIRE = 'ROLE_GESTIONNAIRE';
    case COMPTABLE = 'ROLE_COMPTABLE';
    case PROPRIETAIRE = 'ROLE_PROPRIETAIRE';
    case LOCATAIRE = 'ROLE_LOCATAIRE';
}
