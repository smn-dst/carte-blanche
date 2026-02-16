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
     * Vecteur d'embedding (768 dimensions), stocké en JSON pour portabilité.
     *
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

    /**
     * @return array<int, float>
     */
    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    /**
     * @param array<int, float> $embedding
     */
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
