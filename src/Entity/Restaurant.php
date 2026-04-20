<?php

namespace App\Entity;

use App\Enum\StatusRestaurantEnum;
use App\Repository\RestaurantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Annonce
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $latitude = null;

    #[ORM\Column(type: Types::FLOAT)]
    private ?float $longitude = null;

    #[ORM\Column]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    private ?string $annualRevenue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $rent = null;

    #[ORM\Column(nullable: true)]
    private ?int $leaseRemaining = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2)]
    private ?string $askingPrice = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pappersUrl = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $viewCount = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $favoriteCount = 0;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    // Enchère
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $auctionDate = null;

    #[ORM\Column(type: Types::TIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $auctionTime = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $auctionLocation = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $auctionLocationLat = null;

    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $auctionLocationLng = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, nullable: true)]
    private ?string $ticketPrice = null;

    #[ORM\Column(options: ['default' => 50])]
    private int $maxCapacity = 50;

    #[ORM\Column(options: ['default' => 0])]
    private int $ticketsSold = 0;

    // Statut
    #[ORM\Column(enumType: StatusRestaurantEnum::class, options: ['default' => 'brouillon'])]
    private StatusRestaurantEnum $status = StatusRestaurantEnum::BROUILLON;

    #[ORM\ManyToOne(inversedBy: 'restaurants')]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false)]
    private ?User $owner = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'restaurants')]
    #[ORM\JoinTable(name: 'restaurant_category')]
    #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id')]
    private Collection $categories;

    /**
     * @var Collection<int, Image>
     */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: Image::class)]
    private Collection $images;

    /**
     * @var Collection<int, Ticket>
     */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: Ticket::class)]
    private Collection $tickets;

    /**
     * @var Collection<int, CartItem>
     */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: CartItem::class)]
    private Collection $cartItems;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: Favorite::class)]
    private Collection $favorites;

    /**
     * @var Collection<int, AiLog>
     */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: AiLog::class)]
    private Collection $aiLogs;

    /**
     * @var Collection<int, RestaurantEmbedding>
     */
    #[ORM\OneToMany(mappedBy: 'restaurant', targetEntity: RestaurantEmbedding::class)]
    private Collection $restaurantEmbeddings;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->tickets = new ArrayCollection();
        $this->cartItems = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->aiLogs = new ArrayCollection();
        $this->restaurantEmbeddings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): static
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): static
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getAnnualRevenue(): ?string
    {
        return $this->annualRevenue;
    }

    public function setAnnualRevenue(?string $annualRevenue): static
    {
        $this->annualRevenue = $annualRevenue;

        return $this;
    }

    public function getRent(): ?string
    {
        return $this->rent;
    }

    public function setRent(?string $rent): static
    {
        $this->rent = $rent;

        return $this;
    }

    public function getLeaseRemaining(): ?int
    {
        return $this->leaseRemaining;
    }

    public function setLeaseRemaining(?int $leaseRemaining): static
    {
        $this->leaseRemaining = $leaseRemaining;

        return $this;
    }

    public function getAskingPrice(): ?string
    {
        return $this->askingPrice;
    }

    public function setAskingPrice(string $askingPrice): static
    {
        $this->askingPrice = $askingPrice;

        return $this;
    }

    public function getPappersUrl(): ?string
    {
        return $this->pappersUrl;
    }

    public function setPappersUrl(?string $pappersUrl): static
    {
        $this->pappersUrl = $pappersUrl;

        return $this;
    }

    public function getViewCount(): int
    {
        return $this->viewCount;
    }

    public function setViewCount(int $viewCount): static
    {
        $this->viewCount = $viewCount;

        return $this;
    }

    public function getFavoriteCount(): int
    {
        return $this->favoriteCount;
    }

    public function setFavoriteCount(int $favoriteCount): static
    {
        $this->favoriteCount = $favoriteCount;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getAuctionDate(): ?\DateTimeInterface
    {
        return $this->auctionDate;
    }

    public function setAuctionDate(?\DateTimeInterface $auctionDate): static
    {
        $this->auctionDate = $auctionDate;

        return $this;
    }

    public function getAuctionTime(): ?\DateTimeInterface
    {
        return $this->auctionTime;
    }

    public function setAuctionTime(?\DateTimeInterface $auctionTime): static
    {
        $this->auctionTime = $auctionTime;

        return $this;
    }

    public function getAuctionLocation(): ?string
    {
        return $this->auctionLocation;
    }

    public function setAuctionLocation(?string $auctionLocation): static
    {
        $this->auctionLocation = $auctionLocation;

        return $this;
    }

    public function getAuctionLocationLat(): ?float
    {
        return $this->auctionLocationLat;
    }

    public function setAuctionLocationLat(?float $auctionLocationLat): static
    {
        $this->auctionLocationLat = $auctionLocationLat;

        return $this;
    }

    public function getAuctionLocationLng(): ?float
    {
        return $this->auctionLocationLng;
    }

    public function setAuctionLocationLng(?float $auctionLocationLng): static
    {
        $this->auctionLocationLng = $auctionLocationLng;

        return $this;
    }

    public function getTicketPrice(): ?string
    {
        return $this->ticketPrice;
    }

    public function setTicketPrice(?string $ticketPrice): static
    {
        $this->ticketPrice = $ticketPrice;

        return $this;
    }

    public function getMaxCapacity(): int
    {
        return $this->maxCapacity;
    }

    public function setMaxCapacity(int $maxCapacity): static
    {
        $this->maxCapacity = $maxCapacity;

        return $this;
    }

    public function getTicketsSold(): int
    {
        return $this->ticketsSold;
    }

    public function setTicketsSold(int $ticketsSold): static
    {
        $this->ticketsSold = $ticketsSold;

        return $this;
    }

    public function getStatus(): StatusRestaurantEnum
    {
        return $this->status;
    }

    public function setStatus(StatusRestaurantEnum $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function getFirstImage(): ?Image
    {
        if ($this->images->isEmpty()) {
            return null;
        }

        $sorted = $this->images->toArray();
        usort($sorted, fn (Image $a, Image $b) => $a->getPosition() <=> $b->getPosition());

        return $sorted[0];
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setRestaurant($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getRestaurant() === $this) {
                $image->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Ticket>
     */
    public function getTickets(): Collection
    {
        return $this->tickets;
    }

    public function addTicket(Ticket $ticket): static
    {
        if (!$this->tickets->contains($ticket)) {
            $this->tickets->add($ticket);
            $ticket->setRestaurant($this);
        }

        return $this;
    }

    public function removeTicket(Ticket $ticket): static
    {
        if ($this->tickets->removeElement($ticket)) {
            if ($ticket->getRestaurant() === $this) {
                $ticket->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CartItem>
     */
    public function getCartItems(): Collection
    {
        return $this->cartItems;
    }

    public function addCartItem(CartItem $cartItem): static
    {
        if (!$this->cartItems->contains($cartItem)) {
            $this->cartItems->add($cartItem);
            $cartItem->setRestaurant($this);
        }

        return $this;
    }

    public function removeCartItem(CartItem $cartItem): static
    {
        if ($this->cartItems->removeElement($cartItem)) {
            if ($cartItem->getRestaurant() === $this) {
                $cartItem->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Favorite>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    public function addFavorite(Favorite $favorite): static
    {
        if (!$this->favorites->contains($favorite)) {
            $this->favorites->add($favorite);
            $favorite->setRestaurant($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getRestaurant() === $this) {
                $favorite->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AiLog>
     */
    public function getAiLogs(): Collection
    {
        return $this->aiLogs;
    }

    public function addAiLog(AiLog $aiLog): static
    {
        if (!$this->aiLogs->contains($aiLog)) {
            $this->aiLogs->add($aiLog);
            $aiLog->setRestaurant($this);
        }

        return $this;
    }

    public function removeAiLog(AiLog $aiLog): static
    {
        if ($this->aiLogs->removeElement($aiLog)) {
            if ($aiLog->getRestaurant() === $this) {
                $aiLog->setRestaurant(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RestaurantEmbedding>
     */
    public function getRestaurantEmbeddings(): Collection
    {
        return $this->restaurantEmbeddings;
    }

    public function addRestaurantEmbedding(RestaurantEmbedding $restaurantEmbedding): static
    {
        if (!$this->restaurantEmbeddings->contains($restaurantEmbedding)) {
            $this->restaurantEmbeddings->add($restaurantEmbedding);
            $restaurantEmbedding->setRestaurant($this);
        }

        return $this;
    }

    public function removeRestaurantEmbedding(RestaurantEmbedding $restaurantEmbedding): static
    {
        if ($this->restaurantEmbeddings->removeElement($restaurantEmbedding)) {
            if ($restaurantEmbedding->getRestaurant() === $this) {
                $restaurantEmbedding->setRestaurant(null);
            }
        }

        return $this;
    }
}
