<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    // Change from single color to multiple colors using JSON array
    #[ORM\Column(type: Types::JSON)]
    private array $colors = [];

    // Change from single size to multiple sizes using JSON array
    #[ORM\Column(type: Types::JSON)]
    private array $sizes = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

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

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getColors(): array
    {
        return $this->colors;
    }

    public function setColors(array $colors): static
    {
        $this->colors = $colors;

        return $this;
    }

    public function addColor(string $color): static
    {
        if (!in_array($color, $this->colors)) {
            $this->colors[] = $color;
        }

        return $this;
    }

    public function removeColor(string $color): static
    {
        $this->colors = array_diff($this->colors, [$color]);

        return $this;
    }

    public function getSizes(): array
    {
        return $this->sizes;
    }

    public function setSizes(array $sizes): static
    {
        $this->sizes = $sizes;

        return $this;
    }

    public function addSize(int $size): static
    {
        if (!in_array($size, $this->sizes)) {
            $this->sizes[] = $size;
        }

        return $this;
    }

    public function removeSize(int $size): static
    {
        $this->sizes = array_diff($this->sizes, [$size]);

        return $this;
    }
}
