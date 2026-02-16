<?php

namespace App\Entity;

use App\Repository\FaqEntryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaqEntryRepository::class)]
#[ORM\Table(name: 'faq_entry')]
class FaqEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $answer = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, FaqEmbedding>
     */
    #[ORM\OneToMany(mappedBy: 'faqEntry', targetEntity: FaqEmbedding::class)]
    private Collection $faqEmbeddings;

    public function __construct()
    {
        $this->faqEmbeddings = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): static
    {
        $this->question = $question;

        return $this;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): static
    {
        $this->answer = $answer;

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

    /**
     * @return Collection<int, FaqEmbedding>
     */
    public function getFaqEmbeddings(): Collection
    {
        return $this->faqEmbeddings;
    }

    public function addFaqEmbedding(FaqEmbedding $faqEmbedding): static
    {
        if (!$this->faqEmbeddings->contains($faqEmbedding)) {
            $this->faqEmbeddings->add($faqEmbedding);
            $faqEmbedding->setFaqEntry($this);
        }

        return $this;
    }

    public function removeFaqEmbedding(FaqEmbedding $faqEmbedding): static
    {
        if ($this->faqEmbeddings->removeElement($faqEmbedding)) {
            if ($faqEmbedding->getFaqEntry() === $this) {
                $faqEmbedding->setFaqEntry(null);
            }
        }

        return $this;
    }
}
