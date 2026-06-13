<?php

namespace App\Security\Voter;

use App\Entity\Contrat;
use App\Entity\User;
use App\Enum\RoleUtilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ContratVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const CREATE = 'CREATE';
    public const RESILIER = 'RESILIER';
    public const RENOUVELER = 'RENOUVELER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::CREATE, self::RESILIER, self::RENOUVELER])
            && ($subject instanceof Contrat || $subject === null);
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

        /** @var Contrat|null $contrat */
        $contrat = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($contrat, $user),
            self::CREATE, self::RESILIER, self::RENOUVELER => $user->getRole() === RoleUtilisateur::GESTIONNAIRE,
            default => false,
        };
    }

    private function canView(?Contrat $contrat, User $user): bool
    {
        if (!$contrat) {
            return false;
        }

        if (\in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            return true;
        }

        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            return $contrat->getLocataire() === $user;
        }

        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            $bien = $contrat->getBien();
            $userProp = $user->getProprietaire();
            return $bien !== null && $userProp !== null && $bien->getProprietaire() === $userProp;
        }

        return false;
    }
}
