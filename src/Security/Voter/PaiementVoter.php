<?php

namespace App\Security\Voter;

use App\Entity\Paiement;
use App\Entity\User;
use App\Enum\RoleUtilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PaiementVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const DECLARE = 'DECLARE';
    public const VALIDER = 'VALIDER';
    public const ANNULER = 'ANNULER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::DECLARE, self::VALIDER, self::ANNULER])
            && $subject instanceof Paiement;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($user->getRole() === RoleUtilisateur::ADMIN) {
            return true;
        }

        /** @var Paiement $paiement */
        $paiement = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($paiement, $user),
            self::DECLARE => $this->canDeclare($paiement, $user),
            self::VALIDER => $this->canValider($paiement, $user),
            self::ANNULER => $this->canAnnuler($paiement, $user),
            default => false,
        };
    }

    private function canView(Paiement $paiement, User $user): bool
    {
        if (in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            return true;
        }

        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            return $paiement->getContrat()->getLocataire() === $user;
        }

        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            return $paiement->getContrat()->getBien()->getProprietaire() === $user->getProprietaire();
        }

        return false;
    }

    private function canDeclare(Paiement $paiement, User $user): bool
    {
        // Only Gestionnaire and Comptable can declare a payment (or maybe Locataire directly in some configs)
        return in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE]);
    }

    private function canValider(Paiement $paiement, User $user): bool
    {
        // Only Comptable can validate a payment officially
        return $user->getRole() === RoleUtilisateur::COMPTABLE;
    }

    private function canAnnuler(Paiement $paiement, User $user): bool
    {
        return false; // Reserved to Admin only
    }
}
