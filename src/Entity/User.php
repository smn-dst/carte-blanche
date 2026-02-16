<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
// use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]

/**
 * #[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
 * Géré par le Dto et le formulaire, pas besoin de cette contrainte au niveau de l'entité
 * qui pourrait causer des problèmes lors de la validation de l'entité dans d'autres contextes (ex: mise à jour du profil sans changer l'email).
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isVerified = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isSuspended = null;

    /**
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(mappedBy: 'buyer', targetEntity: Order::class)]
    private Collection $orders;

    /**
     * @var Collection<int, Restaurant>
     */
    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Restaurant::class)]
    private Collection $restaurants;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Cart::class)]
    private ?Cart $cart = null;

    /**
     * @var Collection<int, Refund>
     */
    #[ORM\OneToMany(mappedBy: 'requestedBy', targetEntity: Refund::class)]
    private Collection $refundsRequested;

    /**
     * @var Collection<int, Refund>
     */
    #[ORM\OneToMany(mappedBy: 'processedBy', targetEntity: Refund::class)]
    private Collection $refundsProcessed;

    /**
     * @var Collection<int, Favorite>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Favorite::class)]
    private Collection $favorites;

    /**
     * @var Collection<int, AiLog>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: AiLog::class)]
    private Collection $aiLogs;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserPreferenceEmbedding::class)]
    private ?UserPreferenceEmbedding $userPreferenceEmbedding = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $token = null;

    /**
     * @var Collection<int, PasswordResetToken>
     */
    #[ORM\OneToMany(targetEntity: PasswordResetToken::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $passwordResetTokens;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
        $this->restaurants = new ArrayCollection();
        $this->refundsRequested = new ArrayCollection();
        $this->refundsProcessed = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->aiLogs = new ArrayCollection();
        $this->passwordResetTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

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

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isSuspended(): ?bool
    {
        return $this->isSuspended;
    }

    public function setIsSuspended(bool $isSuspended): static
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if (!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setBuyer($this);
        }

        return $this;
    }

    public function removeOrder(Order $order): static
    {
        if ($this->orders->removeElement($order)) {
            if ($order->getBuyer() === $this) {
                $order->setBuyer(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Restaurant>
     */
    public function getRestaurants(): Collection
    {
        return $this->restaurants;
    }

    public function addRestaurant(Restaurant $restaurant): static
    {
        if (!$this->restaurants->contains($restaurant)) {
            $this->restaurants->add($restaurant);
            $restaurant->setOwner($this);
        }

        return $this;
    }

    public function removeRestaurant(Restaurant $restaurant): static
    {
        if ($this->restaurants->removeElement($restaurant)) {
            if ($restaurant->getOwner() === $this) {
                $restaurant->setOwner(null);
            }
        }

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        if (null !== $cart && $cart->getUser() !== $this) {
            $cart->setUser($this);
        }
        $this->cart = $cart;

        return $this;
    }

    /**
     * @return Collection<int, Refund>
     */
    public function getRefundsRequested(): Collection
    {
        return $this->refundsRequested;
    }

    public function addRefundsRequested(Refund $refund): static
    {
        if (!$this->refundsRequested->contains($refund)) {
            $this->refundsRequested->add($refund);
            $refund->setRequestedBy($this);
        }

        return $this;
    }

    public function removeRefundsRequested(Refund $refund): static
    {
        if ($this->refundsRequested->removeElement($refund)) {
            if ($refund->getRequestedBy() === $this) {
                $refund->setRequestedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Refund>
     */
    public function getRefundsProcessed(): Collection
    {
        return $this->refundsProcessed;
    }

    public function addRefundsProcessed(Refund $refund): static
    {
        if (!$this->refundsProcessed->contains($refund)) {
            $this->refundsProcessed->add($refund);
            $refund->setProcessedBy($this);
        }

        return $this;
    }

    public function removeRefundsProcessed(Refund $refund): static
    {
        if ($this->refundsProcessed->removeElement($refund)) {
            if ($refund->getProcessedBy() === $this) {
                $refund->setProcessedBy(null);
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
            $favorite->setUser($this);
        }

        return $this;
    }

    public function removeFavorite(Favorite $favorite): static
    {
        if ($this->favorites->removeElement($favorite)) {
            if ($favorite->getUser() === $this) {
                $favorite->setUser(null);
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
            $aiLog->setUser($this);
        }

        return $this;
    }

    public function removeAiLog(AiLog $aiLog): static
    {
        if ($this->aiLogs->removeElement($aiLog)) {
            if ($aiLog->getUser() === $this) {
                $aiLog->setUser(null);
            }
        }

        return $this;
    }

    public function getUserPreferenceEmbedding(): ?UserPreferenceEmbedding
    {
        return $this->userPreferenceEmbedding;
    }

    public function setUserPreferenceEmbedding(?UserPreferenceEmbedding $userPreferenceEmbedding): static
    {
        if (null !== $userPreferenceEmbedding && $userPreferenceEmbedding->getUser() !== $this) {
            $userPreferenceEmbedding->setUser($this);
        }
        $this->userPreferenceEmbedding = $userPreferenceEmbedding;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return Collection<int, PasswordResetToken>
     */
    public function getPasswordResetTokens(): Collection
    {
        return $this->passwordResetTokens;
    }

    public function addPasswordResetToken(PasswordResetToken $passwordResetToken): static
    {
        if (!$this->passwordResetTokens->contains($passwordResetToken)) {
            $this->passwordResetTokens->add($passwordResetToken);
            $passwordResetToken->setOwner($this);
        }

        return $this;
    }

    public function removePasswordResetToken(PasswordResetToken $passwordResetToken): static
    {
        if ($this->passwordResetTokens->removeElement($passwordResetToken)) {
            // set the owning side to null (unless already changed)
            if ($passwordResetToken->getOwner() === $this) {
                $passwordResetToken->setOwner(null);
            }
        }

        return $this;
    }
}
