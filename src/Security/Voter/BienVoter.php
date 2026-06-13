<?php

namespace App\Security\Voter;

use App\Entity\Bien;
use App\Entity\User;
use App\Enum\RoleUtilisateur;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BienVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Bien;
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

        /** @var Bien $bien */
        $bien = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($bien, $user),
            self::EDIT => $this->canEdit($bien, $user),
            self::DELETE => false,
            default => false,
        };
    }

    private function canView(Bien $bien, User $user): bool
    {
        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            $userProp = $user->getProprietaire();
            return $userProp !== null && $bien->getProprietaire() === $userProp;
        }

        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            foreach ($bien->getContrats() as $contrat) {
                if ($contrat->getLocataire() === $user) {
                    return true;
                }
            }
            return false;
        }

        if (\in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            return true;
        }

        return false;
    }

    private function canEdit(Bien $bien, User $user): bool
    {
        if ($user->getRole() === RoleUtilisateur::GESTIONNAIRE) {
            return true;
        }

        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            $userProp = $user->getProprietaire();
            return $userProp !== null && $bien->getProprietaire() === $userProp;
        }

        return false;
    }
}
