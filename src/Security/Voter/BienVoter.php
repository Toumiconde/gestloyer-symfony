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
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Bien;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // ROLE_ADMIN can do anything
        if ($user->getRole() === RoleUtilisateur::ADMIN) {
            return true;
        }

        /** @var Bien $bien */
        $bien = $subject;

        return match($attribute) {
            self::VIEW => $this->canView($bien, $user),
            self::EDIT => $this->canEdit($bien, $user),
            self::DELETE => $this->canDelete($bien, $user),
            default => false,
        };
    }

    private function canView(Bien $bien, User $user): bool
    {
        // Proprietaire can only view his own properties
        if ($user->getRole() === RoleUtilisateur::PROPRIETAIRE) {
            return $bien->getProprietaire() === $user->getProprietaire();
        }

        // Locataire can view the properties he rents
        if ($user->getRole() === RoleUtilisateur::LOCATAIRE) {
            foreach ($bien->getContrats() as $contrat) {
                if ($contrat->getLocataire() === $user) {
                    return true;
                }
            }
            return false;
        }

        // Gestionnaire and Comptable can view all properties
        if (in_array($user->getRole(), [RoleUtilisateur::GESTIONNAIRE, RoleUtilisateur::COMPTABLE])) {
            return true;
        }

        return false;
    }

    private function canEdit(Bien $bien, User $user): bool
    {
        // Only Gestionnaire and Admin can edit properties
        return $user->getRole() === RoleUtilisateur::GESTIONNAIRE;
    }

    private function canDelete(Bien $bien, User $user): bool
    {
        // Only Admin can delete (already handled above), or maybe Gestionnaire under conditions
        return false;
    }
}
