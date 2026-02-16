<?php

namespace App\Entity;

use App\Repository\RestaurantEmbeddingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantEmbeddingRepository::class)]
#[ORM\Table(name: 'restaurant_embedding')]
class RestaurantEmbedding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    /**
     * Vecteur d'embedding (768 dimensions), stocké en JSON pour portabilité.
     *
     * @var array<int, float>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $embedding = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'restaurant_id', referencedColumnName: 'id', nullable: false)]
    private ?Restaurant $restaurant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

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

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
    }
}
