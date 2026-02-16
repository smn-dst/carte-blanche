<?php

namespace App\Entity;

use App\Repository\FaqEmbeddingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqEmbeddingRepository::class)]
#[ORM\Table(name: 'faq_embedding')]
class FaqEmbedding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    /**
     * Vecteur d'embedding (768 dimensions), stocké en JSON pour portabilité.
     * Avec PostgreSQL + pgvector, un type personnalisé peut remplacer ce champ.
     *
     * @var array<int, float>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $embedding = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'faq_entry_id', referencedColumnName: 'id', nullable: false)]
    private ?FaqEntry $faqEntry = null;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFaqEntry(): ?FaqEntry
    {
        return $this->faqEntry;
    }

    public function setFaqEntry(?FaqEntry $faqEntry): static
    {
        $this->faqEntry = $faqEntry;

        return $this;
    }
}
