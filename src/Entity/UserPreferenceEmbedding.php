<?php

namespace App\Entity;

use App\Repository\UserPreferenceEmbeddingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPreferenceEmbeddingRepository::class)]
#[ORM\Table(name: 'user_preference_embedding')]
class UserPreferenceEmbedding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $preferencesText = null;

    /**
     * Préférences structurées pour le pré-filtrage dur.
     * Ex: {"budgetMin":500000,"budgetMax":1000000,"capacityMin":100,
     *      "preferredCity":"Toulouse","searchRadius":25,"cuisineTypes":["french"]}.
     *
     * Nullable côté PHP + DB : lignes anciennes ou NULL en base, sinon Doctrine peut
     * laisser la propriété non initialisée après hydratation (newInstanceWithoutConstructor).
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $preferencesData = null;

    /**
     * @var array<int, float>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $embedding = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, unique: true)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPreferencesText(): ?string
    {
        return $this->preferencesText;
    }

    public function setPreferencesText(string $preferencesText): static
    {
        $this->preferencesText = $preferencesText;

        return $this;
    }

    /** @return array<string, mixed> */
    public function getPreferencesData(): array
    {
        return $this->preferencesData ?? [];
    }

    /** @param array<string, mixed> $preferencesData */
    public function setPreferencesData(array $preferencesData): static
    {
        $this->preferencesData = $preferencesData;

        return $this;
    }

    /** @return array<int, float> */
    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    /** @param array<int, float> $embedding */
    public function setEmbedding(array $embedding): static
    {
        $this->embedding = $embedding;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
