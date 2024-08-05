<?php

namespace App\Entity;

use App\Entity\Order;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CartItemRepository;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $quantity = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProductVariant $productVariant = null;

    #[ORM\ManyToOne(inversedBy: 'cartItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Cart $cart = null;


    #[ORM\ManyToOne(inversedBy: 'cartItems', targetEntity: Order::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Order $order = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getProductVariant(): ?ProductVariant
    {
        return $this->productVariant;
    }

    public function setProductVariant(?ProductVariant $productVariant): static
    {
        $this->productVariant = $productVariant;

        return $this;
    }

    public function getCart(): ?Cart
    {
        return $this->cart;
    }

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;

        return $this;
    }


    public function getOrder(): ?Order
    {
        return $this->order;
    }


    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;

    }
}
