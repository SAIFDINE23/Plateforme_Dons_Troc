<?php

namespace App\Security\Voter;

use App\Entity\Annonce;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour gérer les permissions sur les annonces.
 * 
 * Permissions supportées :
 * - VIEW : Tout le monde peut voir une annonce publiée
 * - EDIT : Seul le propriétaire peut modifier son annonce
 * - DELETE : Seul le propriétaire peut supprimer son annonce
 */
class AnnonceVoter extends Voter
{
    public const VIEW = 'VIEW';
    public const EDIT = 'EDIT';
    public const DELETE = 'DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Ce voter ne supporte que les actions sur les entités Annonce
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        if (!$subject instanceof Annonce) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur n'est pas connecté
        if (!$user instanceof User) {
            // Pour VIEW, on pourrait autoriser les visiteurs à voir les annonces publiées
            // Pour l'instant, on exige une connexion
            return false;
        }

        /** @var Annonce $annonce */
        $annonce = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($annonce, $user),
            self::EDIT => $this->canEdit($annonce, $user),
            self::DELETE => $this->canDelete($annonce, $user),
            default => false,
        };
    }

    private function canView(Annonce $annonce, User $user): bool
    {
        // Le propriétaire peut toujours voir son annonce
        if ($annonce->getOwner() === $user) {
            return true;
        }

        // Les modérateurs et admins peuvent voir toutes les annonces
        if (in_array('ROLE_MODERATOR', $user->getRoles()) || in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Les autres utilisateurs peuvent voir uniquement les annonces publiées
        return $annonce->getState()->value === 'PUBLISHED';
    }

    private function canEdit(Annonce $annonce, User $user): bool
    {
        // Seul le propriétaire peut modifier son annonce
        return $annonce->getOwner() === $user;
    }

    private function canDelete(Annonce $annonce, User $user): bool
    {
        // Seul le propriétaire peut supprimer son annonce
        // Les admins pourraient aussi avoir ce droit si besoin
        return $annonce->getOwner() === $user;
    }
}
