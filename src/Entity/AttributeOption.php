<?php

namespace App\Entity;

use App\Repository\AttributeOptionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AttributeOptionRepository::class)]
#[ORM\UniqueConstraint(columns: ['definition_id', 'value'])]
class AttributeOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'options')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?AttributeDefinition $definition = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100, maxMessage: 'Name cannot be longer than {{ limit }} characters')]
    #[ORM\Column(length: 100)]
    private ?string $value = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDefinition(): ?AttributeDefinition
    {
        return $this->definition;
    }

    public function setDefinition(?AttributeDefinition $definition): static
    {
        $this->definition = $definition;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }
}
