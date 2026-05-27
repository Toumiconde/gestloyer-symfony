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
        return in_array($attribute, [self::VIEW, self::CREATE, self::RESILIER, self::RENOUVELER])
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
            self::CREATE => $this->canCreate($user),
            self::RESILIER => $this->canResilier($contrat, $user),
            self::RENOUVELER => $this->canRenouveler($contrat, $user),
            default => false,
        };
    }

    private function canView(?Contrat $contrat, User $user): bool
    {
        if (!$contrat) return false;
        
        if (in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            return true;
        }

        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            return $contrat->getLocataire() === $user;
        }

        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            return $contrat->getBien()->getProprietaire() === $user->getProprietaire();
        }

        return false;
    }

    private function canCreate(User $user): bool
    {
        return $user->getRole() === RoleUtilisateur::GESTIONNAIRE;
    }

    private function canResilier(Contrat $contrat, User $user): bool
    {
        return $user->getRole() === RoleUtilisateur::GESTIONNAIRE;
    }

    private function canRenouveler(Contrat $contrat, User $user): bool
    {
        return $user->getRole() === RoleUtilisateur::GESTIONNAIRE;
    }
}
