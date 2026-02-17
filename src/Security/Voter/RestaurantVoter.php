<?php

namespace App\Security\Voter;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Restaurant>
 */
final class RestaurantVoter extends Voter
{
    public const VIEW = 'RESTAURANT_VIEW';
    public const EDIT = 'RESTAURANT_EDIT';
    public const DELETE = 'RESTAURANT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Restaurant;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        /** @var Restaurant $restaurant */
        $restaurant = $subject;

        $user = $token->getUser();

        if (self::VIEW === $attribute) {
            if (
                StatusRestaurantEnum::PUBLIE === $restaurant->getStatus()
                || StatusRestaurantEnum::PROGRAMME === $restaurant->getStatus()
                || StatusRestaurantEnum::EN_COURS === $restaurant->getStatus()
            ) {
                return true;
            }

            return $this->isOwnerOrAdmin($user, $restaurant);
        }

        if (self::EDIT === $attribute || self::DELETE === $attribute) {
            return $this->isOwnerOrAdmin($user, $restaurant);
        }

        return false;
    }

    private function isOwnerOrAdmin(mixed $user, Restaurant $restaurant): bool
    {
        if (!$user instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        return $restaurant->getOwner() === $user;
    }
}
