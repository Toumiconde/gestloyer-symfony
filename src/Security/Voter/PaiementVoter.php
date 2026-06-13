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
        return \in_array($attribute, [self::VIEW, self::DECLARE, self::VALIDER, self::ANNULER])
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
            self::DECLARE, self::VALIDER => \in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE]),
            self::ANNULER => false,
            default => false,
        };
    }

    private function canView(Paiement $paiement, User $user): bool
    {
        if (\in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            return true;
        }

        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            $contrat = $paiement->getContrat();
            return $contrat !== null && $contrat->getLocataire() === $user;
        }

        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            $contrat = $paiement->getContrat();
            $userProp = $user->getProprietaire();
            if ($contrat === null || $userProp === null) {
                return false;
            }
            $bien = $contrat->getBien();
            return $bien !== null && $bien->getProprietaire() === $userProp;
        }

        return false;
    }
}
