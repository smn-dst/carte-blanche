<?php

namespace App\Tests\Unit\Security;

use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use App\Security\Voter\RestaurantVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\InMemoryUser;

class RestaurantVoterTest extends TestCase
{
    private RestaurantVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new RestaurantVoter();
    }

    private function createToken(?User $user): UsernamePasswordToken
    {
        if (null === $user) {
            $anonymousUser = new InMemoryUser('visitor', 'password');

            return new UsernamePasswordToken($anonymousUser, 'main', []);
        }

        return new UsernamePasswordToken($user, 'main', $user->getRoles());
    }

    /**
     * @param list<string> $roles
     */
    private function createUser(array $roles = ['ROLE_USER']): User
    {
        $user = new User();
        $user->setRoles($roles);
        $user->setEmail('test@test.com');

        return $user;
    }

    private function createRestaurant(User $owner, StatusRestaurantEnum $status = StatusRestaurantEnum::PUBLIE): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setOwner($owner);
        $restaurant->setStatus($status);

        return $restaurant;
    }

    public function testViewPublishedRestaurantGrantedToAnyone(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $visitor = $this->createUser();
        $restaurant = $this->createRestaurant($owner, StatusRestaurantEnum::PUBLIE);

        $result = $this->voter->vote(
            $this->createToken($visitor),
            $restaurant,
            [RestaurantVoter::VIEW]
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testViewDraftRestaurantDeniedToNonOwner(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $other = $this->createUser();
        $restaurant = $this->createRestaurant($owner, StatusRestaurantEnum::BROUILLON);

        $result = $this->voter->vote(
            $this->createToken($other),
            $restaurant,
            [RestaurantVoter::VIEW]
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testViewDraftRestaurantGrantedToOwner(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $restaurant = $this->createRestaurant($owner, StatusRestaurantEnum::BROUILLON);

        $result = $this->voter->vote(
            $this->createToken($owner),
            $restaurant,
            [RestaurantVoter::VIEW]
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testEditGrantedToOwner(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $restaurant = $this->createRestaurant($owner);

        $result = $this->voter->vote(
            $this->createToken($owner),
            $restaurant,
            [RestaurantVoter::EDIT]
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testEditDeniedToNonOwner(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $other = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $result = $this->voter->vote(
            $this->createToken($other),
            $restaurant,
            [RestaurantVoter::EDIT]
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testEditGrantedToAdmin(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $admin = $this->createUser(['ROLE_ADMIN']);
        $restaurant = $this->createRestaurant($owner);

        $result = $this->voter->vote(
            $this->createToken($admin),
            $restaurant,
            [RestaurantVoter::EDIT]
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeleteGrantedToOwner(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $restaurant = $this->createRestaurant($owner);

        $result = $this->voter->vote(
            $this->createToken($owner),
            $restaurant,
            [RestaurantVoter::DELETE]
        );

        $this->assertSame(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testDeleteDeniedToNonOwner(): void
    {
        $owner = $this->createUser(['ROLE_VENDOR']);
        $other = $this->createUser();
        $restaurant = $this->createRestaurant($owner);

        $result = $this->voter->vote(
            $this->createToken($other),
            $restaurant,
            [RestaurantVoter::DELETE]
        );

        $this->assertSame(VoterInterface::ACCESS_DENIED, $result);
    }
}
