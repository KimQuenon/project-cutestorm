<?php

namespace App\Entity;

use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProductRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[UniqueEntity(fields: ['reference'], message: "This reference already exists, please choose another one.")]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Reference required.")]
    private ?string $reference = null;

    #[ORM\Column(length: 255)]
    #[Assert\Length(min: 2, max: 100, minMessage: "This field must be at least 2 characters long.", maxMessage: "This field can't be longer than 100 characters.")]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\Length(min: 10, minMessage: "This field must be at least 10 characters long.")]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Price required.")]
    private ?float $price = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\ManyToOne(inversedBy: 'product')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductColor $color = null;

    /**
     * @var Collection<int, ProductVariant>
     */
    #[ORM\OneToMany(targetEntity: ProductVariant::class, mappedBy: 'product', orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $productVariants;

    /**
     * @var Collection<int, ProductImage>
     */
    #[ORM\OneToMany(targetEntity: ProductImage::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $productImages;

    /**
     * @var Collection<int, ProductCategory>
     */
    #[ORM\ManyToMany(targetEntity: ProductCategory::class, mappedBy: 'product')]
    private Collection $productCategories;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'product', cascade: ['remove'], orphanRemoval: true)]
    private Collection $reviews;

    public function __construct()
    {
        $this->productVariants = new ArrayCollection();
        $this->productImages = new ArrayCollection();
        $this->productCategories = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    /**
     * Initialize slug
     *
     * @return void
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function initializeSlug(): void
    {
        $slugify = new Slugify();

        $productName = $slugify->slugify($this->name);
        $productRef = $slugify->slugify($this->reference);

        $this->slug = $productName . '-' . $productRef;
    }

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getColor(): ?ProductColor
    {
        return $this->color;
    }

    public function setColor(?ProductColor $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getProductVariants(): Collection
    {
        return $this->productVariants;
    }

    public function addProductVariant(ProductVariant $variant): self
    {
        if (!$this->productVariants->contains($variant)) {
            $this->productVariants[] = $variant;
            $variant->setProduct($this);
        }

        return $this;
    }

    public function removeProductVariant(ProductVariant $variant): self
    {
        if ($this->productVariants->contains($variant)) {
            $this->productVariants->removeElement($variant);
            // set the owning side to null (unless already changed)
            if ($variant->getProduct() === $this) {
                $variant->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductImage>
     */
    public function getProductImages(): Collection
    {
        return $this->productImages;
    }

    public function addProductImage(ProductImage $productImage): static
    {
        if (!$this->productImages->contains($productImage)) {
            $this->productImages->add($productImage);
            $productImage->setProduct($this);
        }

        return $this;
    }

    public function removeProductImage(ProductImage $productImage): static
    {
        if ($this->productImages->removeElement($productImage)) {
            // set the owning side to null (unless already changed)
            if ($productImage->getProduct() === $this) {
                $productImage->setProduct(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ProductCategory>
     */
    public function getProductCategories(): Collection
    {
        return $this->productCategories;
    }

    public function addProductCategory(ProductCategory $productCategory): static
    {
        if (!$this->productCategories->contains($productCategory)) {
            $this->productCategories->add($productCategory);
            $productCategory->addProduct($this);
        }

        return $this;
    }

    public function removeProductCategory(ProductCategory $productCategory): static
    {
        if ($this->productCategories->removeElement($productCategory)) {
            $productCategory->removeProduct($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        $sortedReviews = $this->reviews->toArray();
        usort($sortedReviews, function($a, $b) {
            return $b->getId() <=> $a->getId();
        });

        return new ArrayCollection($sortedReviews);
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setProduct($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getProduct() === $this) {
                $review->setProduct(null);
            }
        }

        return $this;
    }
}
