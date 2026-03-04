<?php

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Image;
use App\Entity\Restaurant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class RestaurantCrudWithImagesTest extends WebTestCase
{
    private const TINY_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5+N2wAAAAASUVORK5CYII=';

    /**
     * @var list<string>
     */
    private array $temporaryUploadPaths = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryUploadPaths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testRestaurantCrudFlowWithImageInsertionAndDeletion(): void
    {
        $client = static::createClient();
        $this->ensureDatabaseIsReady();
        $this->ensureUploadDirectoryExists();

        $entityManager = $this->getEntityManager();
        $vendor = $this->createVendor($entityManager);
        $category = $this->createCategory($entityManager);

        $vendorId = $vendor->getId();
        $categoryId = $category->getId();
        $restaurantId = null;

        self::assertNotNull($vendorId);
        self::assertNotNull($categoryId);

        try {
            $client->loginUser($vendor);

            // Create
            $crawler = $client->request('GET', '/restaurant/nouveau');
            self::assertResponseIsSuccessful();
            self::assertSelectorNotExists('label[for="restaurant_form_latitude"]');
            self::assertSelectorNotExists('label[for="restaurant_form_longitude"]');
            self::assertSelectorExists('input[name="restaurant_form[latitude]"].hidden');
            self::assertSelectorExists('input[name="restaurant_form[longitude]"].hidden');
            if ($this->hasGoogleMapsApiKey()) {
                self::assertStringContainsString('maps.googleapis.com/maps/api/js', (string) $client->getResponse()->getContent());
            }

            $createCsrfToken = $this->extractRestaurantFormCsrfToken($crawler);
            $createImage1 = $this->createUploadedImage('restaurant-create-1.png');
            $createImage2 = $this->createUploadedImage('restaurant-create-2.png');

            $client->request('POST', '/restaurant/nouveau', [
                'restaurant_form' => [
                    '_token' => $createCsrfToken,
                    'name' => 'Restaurant CRUD E2E',
                    'description' => 'Description initiale',
                    'address' => '10 rue de Rivoli, Paris',
                    'latitude' => '48.8557',
                    'longitude' => '2.3601',
                    'capacity' => '75',
                    'askingPrice' => '245000.00',
                    'annualRevenue' => '',
                    'rent' => '',
                    'leaseRemaining' => '',
                    'pappersUrl' => '',
                    'auctionDate' => '',
                    'auctionTime' => '',
                    'auctionLocation' => 'Hôtel Drouot, Paris',
                    'auctionLocationLat' => '48.8719',
                    'auctionLocationLng' => '2.3398',
                    'ticketPrice' => '',
                    'maxCapacity' => '120',
                    'categories' => [(string) $categoryId],
                ],
            ], [
                'restaurant_form' => [
                    'uploadedImages' => [$createImage1, $createImage2],
                ],
            ]);

            self::assertResponseRedirects();
            $restaurantId = $this->extractRestaurantIdFromRedirect($client);
            self::assertSame(
                sprintf('/restaurant/%d/modifier', $restaurantId),
                parse_url((string) $client->getResponse()->headers->get('Location'), PHP_URL_PATH)
            );

            $restaurant = $this->fetchRestaurant($restaurantId);
            self::assertSame('Restaurant CRUD E2E', $restaurant->getName());
            self::assertSame('10 rue de Rivoli, Paris', $restaurant->getAddress());
            self::assertSame(48.8557, $restaurant->getLatitude());
            self::assertSame(2.3601, $restaurant->getLongitude());
            self::assertSame('Hôtel Drouot, Paris', $restaurant->getAuctionLocation());
            self::assertSame(48.8719, $restaurant->getAuctionLocationLat());
            self::assertSame(2.3398, $restaurant->getAuctionLocationLng());
            self::assertCount(2, $restaurant->getImages());

            /** @var Image $firstImage */
            $firstImage = $restaurant->getImages()->first();
            self::assertNotNull($firstImage->getFileName());
            self::assertFileExists($this->getUploadDirectory().'/'.$firstImage->getFileName());

            // Read (index + show)
            $client->request('GET', '/mes-restaurants');
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', 'Restaurant CRUD E2E');

            $client->request('GET', sprintf('/restaurant/%d', $restaurantId));
            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', 'Restaurant CRUD E2E');

            // Update
            $crawler = $client->request('GET', sprintf('/restaurant/%d/modifier', $restaurantId));
            self::assertResponseIsSuccessful();

            $editCsrfToken = $this->extractRestaurantFormCsrfToken($crawler);
            $updateImage1 = $this->createUploadedImage('restaurant-update-1.png');
            $updateImage2 = $this->createUploadedImage('restaurant-update-2.png');

            $client->request('POST', sprintf('/restaurant/%d/modifier', $restaurantId), [
                'restaurant_form' => [
                    '_token' => $editCsrfToken,
                    'name' => 'Restaurant CRUD E2E Updated',
                    'description' => 'Description mise à jour',
                    'address' => '20 boulevard Saint-Germain, Paris',
                    'latitude' => '48.8523',
                    'longitude' => '2.3470',
                    'capacity' => '90',
                    'askingPrice' => '290000.00',
                    'annualRevenue' => '820000.00',
                    'rent' => '6500.00',
                    'leaseRemaining' => '30',
                    'pappersUrl' => '',
                    'auctionDate' => '',
                    'auctionTime' => '',
                    'auctionLocation' => 'Salle Gaveau, Paris',
                    'auctionLocationLat' => '48.8751',
                    'auctionLocationLng' => '2.3110',
                    'ticketPrice' => '',
                    'maxCapacity' => '140',
                    'categories' => [(string) $categoryId],
                ],
            ], [
                'restaurant_form' => [
                    'uploadedImages' => [$updateImage1, $updateImage2],
                ],
            ]);

            self::assertResponseRedirects(sprintf('/restaurant/%d/modifier', $restaurantId));

            $restaurant = $this->fetchRestaurant($restaurantId);
            self::assertSame('Restaurant CRUD E2E Updated', $restaurant->getName());
            self::assertSame(90, $restaurant->getCapacity());
            self::assertSame('290000.00', $restaurant->getAskingPrice());
            self::assertSame('Salle Gaveau, Paris', $restaurant->getAuctionLocation());
            self::assertSame(48.8751, $restaurant->getAuctionLocationLat());
            self::assertSame(2.3110, $restaurant->getAuctionLocationLng());
            self::assertCount(4, $restaurant->getImages());

            // Delete inserted images first, then delete restaurant
            $tokenManager = static::getContainer()->get(CsrfTokenManagerInterface::class);
            foreach ($restaurant->getImages()->toArray() as $image) {
                if (!$image instanceof Image || null === $image->getId()) {
                    continue;
                }

                $deleteImagePath = sprintf('/restaurant/%d/image/%d/supprimer', $restaurantId, $image->getId());
                $csrfToken = $tokenManager->getToken('delete_image_'.$image->getId())->getValue();
                $client->request('POST', $deleteImagePath, ['_token' => $csrfToken]);
                self::assertResponseRedirects(sprintf('/restaurant/%d/modifier', $restaurantId));
            }

            $restaurant = $this->fetchRestaurant($restaurantId);
            self::assertCount(0, $restaurant->getImages());

            $deleteRestaurantToken = $tokenManager->getToken('delete_restaurant_'.$restaurantId)->getValue();
            $client->request('POST', sprintf('/restaurant/%d/supprimer', $restaurantId), [
                '_token' => $deleteRestaurantToken,
            ]);
            self::assertResponseRedirects('/mes-restaurants');

            $this->getEntityManager()->clear();
            self::assertNull(
                $this->getEntityManager()->getRepository(Restaurant::class)->find($restaurantId),
                'Le restaurant doit être supprimé après le flux CRUD complet.'
            );
        } finally {
            $this->cleanupPersistedFixtures($restaurantId, $categoryId, $vendorId);
        }
    }

    private function ensureDatabaseIsReady(): void
    {
        try {
            $connection = $this->getEntityManager()->getConnection();
            $connection->executeQuery('SELECT 1');
            $schemaManager = $connection->createSchemaManager();
            $tables = ['restaurant', 'image', 'category', 'user'];
            foreach ($tables as $table) {
                if (!$schemaManager->tablesExist([$table])) {
                    $this->markTestSkipped(sprintf('Table "%s" absente en base de test.', $table));
                }
            }
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Base de test indisponible : '.$exception->getMessage());
        }
    }

    private function ensureUploadDirectoryExists(): void
    {
        $uploadDirectory = $this->getUploadDirectory();
        if (is_dir($uploadDirectory)) {
            return;
        }

        if (!mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
            self::fail('Impossible de créer le dossier d\'upload des restaurants pour les tests.');
        }
    }

    private function createVendor(EntityManagerInterface $entityManager): User
    {
        $vendor = new User();
        $vendor->setEmail('vendor-crud-'.bin2hex(random_bytes(8)).'@example.test');
        $vendor->setFirstName('Vendor');
        $vendor->setLastName('Crud');
        $vendor->setPassword('test-password');
        $vendor->setRoles(['ROLE_VENDOR']);
        $vendor->setIsVerified(true);
        $vendor->setIsSuspended(false);

        $entityManager->persist($vendor);
        $entityManager->flush();

        return $vendor;
    }

    private function createCategory(EntityManagerInterface $entityManager): Category
    {
        $category = new Category();
        $category->setName('Bistronomie');
        $category->setSlug('bistronomie-'.bin2hex(random_bytes(8)));
        $category->setDescription('Catégorie utilisée pour les tests CRUD restaurant.');

        $entityManager->persist($category);
        $entityManager->flush();

        return $category;
    }

    private function createUploadedImage(string $originalName): UploadedFile
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'restaurant_upload_');
        if (false === $tmpFile) {
            self::fail('Impossible de créer un fichier temporaire pour le test d\'upload.');
        }

        $imagePath = $tmpFile.'.png';
        if (!rename($tmpFile, $imagePath)) {
            self::fail('Impossible de préparer le fichier image temporaire.');
        }

        $imageContent = base64_decode(self::TINY_PNG_BASE64, true);
        if (false === $imageContent) {
            self::fail('Impossible de décoder l\'image PNG de test.');
        }

        if (false === file_put_contents($imagePath, $imageContent)) {
            self::fail('Impossible d\'écrire le fichier image temporaire.');
        }

        $this->temporaryUploadPaths[] = $imagePath;

        return new UploadedFile($imagePath, $originalName, 'image/png', null, true);
    }

    private function extractRestaurantFormCsrfToken(Crawler $crawler): string
    {
        $field = $crawler->filter('input[name="restaurant_form[_token]"]');
        self::assertGreaterThan(0, $field->count(), 'Le token CSRF du formulaire restaurant est introuvable.');

        $value = $field->attr('value');
        self::assertIsString($value);
        self::assertNotSame('', trim($value));

        return $value;
    }

    private function extractRestaurantIdFromRedirect(KernelBrowser $client): int
    {
        $location = $client->getResponse()->headers->get('Location');
        self::assertNotNull($location, 'La redirection attendue après création est absente.');

        $path = parse_url($location, PHP_URL_PATH);
        self::assertIsString($path);
        self::assertMatchesRegularExpression('#^/restaurant/(\d+)/modifier$#', $path);

        preg_match('#^/restaurant/(\d+)/modifier$#', $path, $matches);
        self::assertArrayHasKey(1, $matches);

        return (int) $matches[1];
    }

    private function fetchRestaurant(int $restaurantId): Restaurant
    {
        $entityManager = $this->getEntityManager();
        $entityManager->clear();

        $restaurant = $entityManager->getRepository(Restaurant::class)->find($restaurantId);
        self::assertInstanceOf(Restaurant::class, $restaurant);

        return $restaurant;
    }

    private function cleanupPersistedFixtures(?int $restaurantId, ?int $categoryId, ?int $vendorId): void
    {
        try {
            $entityManager = $this->getEntityManager();
            $entityManager->clear();

            if (null !== $restaurantId) {
                $restaurant = $entityManager->getRepository(Restaurant::class)->find($restaurantId);
                if ($restaurant instanceof Restaurant) {
                    foreach ($restaurant->getImages()->toArray() as $image) {
                        if ($image instanceof Image) {
                            $restaurant->removeImage($image);
                            $entityManager->remove($image);
                        }
                    }
                    $entityManager->remove($restaurant);
                }
            }

            if (null !== $categoryId) {
                $category = $entityManager->getRepository(Category::class)->find($categoryId);
                if ($category instanceof Category) {
                    $entityManager->remove($category);
                }
            }

            if (null !== $vendorId) {
                $vendor = $entityManager->getRepository(User::class)->find($vendorId);
                if ($vendor instanceof User) {
                    $entityManager->remove($vendor);
                }
            }

            $entityManager->flush();
        } catch (\Throwable) {
            // Cleanup best-effort only for test data.
        }
    }

    private function getUploadDirectory(): string
    {
        $uploadDirectory = static::getContainer()->getParameter('restaurants_images_directory');
        self::assertIsString($uploadDirectory);

        return $uploadDirectory;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    private function hasGoogleMapsApiKey(): bool
    {
        $apiKey = $_SERVER['GOOGLE_MAPS_API_KEY'] ?? $_ENV['GOOGLE_MAPS_API_KEY'] ?? null;

        return is_string($apiKey) && '' !== trim($apiKey);
    }
}
