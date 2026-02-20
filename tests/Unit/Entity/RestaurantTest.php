<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Restaurant;
use App\Entity\User;
use App\Enum\StatusRestaurantEnum;
use PHPUnit\Framework\TestCase;

class RestaurantTest extends TestCase
{
    private function createRestaurant(): Restaurant
    {
        $restaurant = new Restaurant();
        $restaurant->setName('Le Gourmet');
        $restaurant->setAddress('123 rue de Paris');
        $restaurant->setLatitude(48.8566);
        $restaurant->setLongitude(2.3522);
        $restaurant->setCapacity(50);
        $restaurant->setAskingPrice('500000.00');
        $restaurant->setCreatedAt(new \DateTimeImmutable());

        return $restaurant;
    }

    public function testGettersAndSetters(): void
    {
        $restaurant = $this->createRestaurant();

        $this->assertSame('Le Gourmet', $restaurant->getName());
        $this->assertSame('123 rue de Paris', $restaurant->getAddress());
        $this->assertSame(48.8566, $restaurant->getLatitude());
        $this->assertSame(2.3522, $restaurant->getLongitude());
        $this->assertSame(50, $restaurant->getCapacity());
        $this->assertSame('500000.00', $restaurant->getAskingPrice());
    }

    public function testDefaultStatus(): void
    {
        $restaurant = new Restaurant();

        $this->assertSame(StatusRestaurantEnum::BROUILLON, $restaurant->getStatus());
    }

    public function testSetStatus(): void
    {
        $restaurant = $this->createRestaurant();
        $restaurant->setStatus(StatusRestaurantEnum::PUBLIE);

        $this->assertSame(StatusRestaurantEnum::PUBLIE, $restaurant->getStatus());
    }

    public function testDefaultViewCount(): void
    {
        $restaurant = new Restaurant();

        $this->assertSame(0, $restaurant->getViewCount());
    }

    public function testIncrementViewCount(): void
    {
        $restaurant = $this->createRestaurant();
        $restaurant->setViewCount(10);

        $this->assertSame(10, $restaurant->getViewCount());
    }

    public function testDefaultFavoriteCount(): void
    {
        $restaurant = new Restaurant();

        $this->assertSame(0, $restaurant->getFavoriteCount());
    }

    public function testOwnerRelation(): void
    {
        $owner = new User();
        $owner->setEmail('owner@test.com');
        $owner->setFirstName('John');
        $owner->setLastName('Doe');
        $owner->setPassword('password');

        $restaurant = $this->createRestaurant();
        $restaurant->setOwner($owner);

        $this->assertSame($owner, $restaurant->getOwner());
        $this->assertSame('owner@test.com', $restaurant->getOwner()->getEmail());
    }

    public function testCategoryCollection(): void
    {
        $restaurant = $this->createRestaurant();
        $category = new Category();

        $this->assertCount(0, $restaurant->getCategories());

        $restaurant->addCategory($category);
        $this->assertCount(1, $restaurant->getCategories());
        $this->assertTrue($restaurant->getCategories()->contains($category));

        $restaurant->addCategory($category);
        $this->assertCount(1, $restaurant->getCategories());

        $restaurant->removeCategory($category);
        $this->assertCount(0, $restaurant->getCategories());
    }

    public function testImageCollection(): void
    {
        $restaurant = $this->createRestaurant();
        $image = new Image();

        $this->assertCount(0, $restaurant->getImages());

        $restaurant->addImage($image);
        $this->assertCount(1, $restaurant->getImages());
        $this->assertSame($restaurant, $image->getRestaurant());

        $restaurant->removeImage($image);
        $this->assertCount(0, $restaurant->getImages());
    }

    public function testGetFirstImageReturnsNullWhenEmpty(): void
    {
        $restaurant = $this->createRestaurant();

        $this->assertNull($restaurant->getFirstImage());
    }

    public function testGetFirstImageReturnsByPosition(): void
    {
        $restaurant = $this->createRestaurant();

        $image1 = new Image();
        $image1->setPosition(2);
        $image1->setFileName('second.jpg');

        $image2 = new Image();
        $image2->setPosition(1);
        $image2->setFileName('first.jpg');

        $restaurant->addImage($image1);
        $restaurant->addImage($image2);

        $firstImage = $restaurant->getFirstImage();
        $this->assertSame('first.jpg', $firstImage->getFileName());
    }

    public function testAuctionProperties(): void
    {
        $restaurant = $this->createRestaurant();

        $restaurant->setAuctionLocation('Salle des ventes Paris');
        $restaurant->setAuctionLocationLat(48.8600);
        $restaurant->setAuctionLocationLng(2.3400);
        $restaurant->setTicketPrice('150.00');
        $restaurant->setMaxCapacity(100);
        $restaurant->setTicketsSold(25);

        $this->assertSame('Salle des ventes Paris', $restaurant->getAuctionLocation());
        $this->assertSame(48.8600, $restaurant->getAuctionLocationLat());
        $this->assertSame(2.3400, $restaurant->getAuctionLocationLng());
        $this->assertSame('150.00', $restaurant->getTicketPrice());
        $this->assertSame(100, $restaurant->getMaxCapacity());
        $this->assertSame(25, $restaurant->getTicketsSold());
    }

    public function testOptionalProperties(): void
    {
        $restaurant = $this->createRestaurant();

        $restaurant->setDescription('Un excellent restaurant gastronomique');
        $restaurant->setAnnualRevenue('1200000.00');
        $restaurant->setRent('5000.00');
        $restaurant->setLeaseRemaining(36);
        $restaurant->setPappersUrl('https://pappers.fr/entreprise/123456');

        $this->assertSame('Un excellent restaurant gastronomique', $restaurant->getDescription());
        $this->assertSame('1200000.00', $restaurant->getAnnualRevenue());
        $this->assertSame('5000.00', $restaurant->getRent());
        $this->assertSame(36, $restaurant->getLeaseRemaining());
        $this->assertSame('https://pappers.fr/entreprise/123456', $restaurant->getPappersUrl());
    }

    public function testUpdatedAt(): void
    {
        $restaurant = $this->createRestaurant();
        $now = new \DateTimeImmutable();

        $restaurant->setUpdatedAt($now);

        $this->assertSame($now, $restaurant->getUpdatedAt());
    }
}
