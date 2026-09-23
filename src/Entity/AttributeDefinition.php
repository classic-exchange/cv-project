<?php

namespace App\Entity;

use App\Enum\AttributeDataType;
use App\Repository\AttributeDefinitionRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: AttributeDefinitionRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Attribute with this name already exists.')]
class AttributeDefinition
{
    public const ONE_OF_MANY_OPTIONS_LIMIT = 10;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotNull]
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?AttributeCategory $category = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100, maxMessage: 'Name cannot be longer than {{ limit }} characters')]
    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[Assert\Length(max: 255, maxMessage: 'Description cannot be longer than {{ limit }} characters')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[Assert\NotNull]
    #[ORM\Column(type: 'string', enumType: AttributeDataType::class)]
    private ?AttributeDataType $dataType = null;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private ?int $version = null;

    #[ORM\Column]
    private ?bool $builtIn = false;

    /**
     * @var Collection<int, AttributeOption>
     */
    #[ORM\OneToMany(targetEntity: AttributeOption::class, mappedBy: 'definition', orphanRemoval: true, cascade: ['persist'])]
    private Collection $options;

    #[Assert\Callback]
    public function validateOptions(ExecutionContextInterface $context): void
    {
        if ($this->dataType !== AttributeDataType::ONE_OF_MANY) {
            if (!$this->options->isEmpty()) {
                $context->buildViolation('Only one-of-many attributes can have options.')
                    ->atPath('options')
                    ->addViolation();
            }
            return;
        }
        if ($this->options->isEmpty()) {
            $context->buildViolation('One-of-many attributes must have at least one option.')
                ->atPath('options')
                ->addViolation();
            return;
        }
        $values = [];
        foreach ($this->options as $option) {
            $value = $option->getValue();
            if (in_array($value, $values, true)) {
                $context->buildViolation('Option values must be unique.')
                    ->atPath('options')
                    ->addViolation();
                return;
            }
            $values[] = $value;
            if (count($values) > self::ONE_OF_MANY_OPTIONS_LIMIT) {
                $context->buildViolation(sprintf('Number of option values must be no more than %d.', self::ONE_OF_MANY_OPTIONS_LIMIT))
                    ->atPath('options')
                    ->addViolation();
                return;
            }
        }
    }

    public function __construct()
    {
        $this->options = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCategory(): ?AttributeCategory
    {
        return $this->category;
    }

    public function setCategory(?AttributeCategory $category): static
    {
        $this->category = $category;
        return $this;
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

    public function getDataType(): ?AttributeDataType
    {
        return $this->dataType;
    }

    public function setDataType(AttributeDataType $dataType): static
    {
        $this->dataType = $dataType;
        return $this;
    }

    public function getVersion(): ?int
    {
        return $this->version;
    }

    public function isBuiltIn(): bool
    {
        return $this->builtIn;
    }

    public function setBuiltIn(bool $builtIn): static
    {
        $this->builtIn = $builtIn;
        return $this;
    }

    /**
     * @return Collection<int, AttributeOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(AttributeOption $option): static
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setDefinition($this);
        }
        return $this;
    }

    public function removeOption(AttributeOption $option): static
    {
        if ($this->options->removeElement($option)) {
            if ($option->getDefinition() === $this) {
                $option->setDefinition(null);
            }
        }
        return $this;
    }
}
