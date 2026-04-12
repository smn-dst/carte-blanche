<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Restaurant;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class CartService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function getOrCreateCart(User $user): Cart
    {
        $cart = $user->getCart();
        if (null !== $cart) {
            return $cart;
        }

        $cart = new Cart();
        $cart->setUser($user);
        $cart->setCreatedAt(new \DateTimeImmutable());
        $this->em->persist($cart);
        $this->em->flush();

        return $cart;
    }

    public function addItem(User $user, Restaurant $restaurant, int $quantity): CartItem
    {
        $cart = $this->getOrCreateCart($user);
        $cart->setUpdatedAt(new \DateTimeImmutable());

        // Vérifier si le restaurant est déjà dans le panier
        foreach ($cart->getCartItems() as $item) {
            if ($item->getRestaurant()?->getId() === $restaurant->getId()) {
                $newQty = min($item->getQuantity() + $quantity, $this->getMaxQuantity($restaurant));
                $item->setQuantity($newQty);
                $this->em->flush();

                return $item;
            }
        }

        $item = new CartItem();
        $item->setCart($cart);
        $item->setRestaurant($restaurant);
        $item->setQuantity(min($quantity, $this->getMaxQuantity($restaurant)));
        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }

    public function updateQuantity(CartItem $item, int $quantity): void
    {
        $restaurant = $item->getRestaurant();
        if (null === $restaurant) {
            return;
        }

        $max = $this->getMaxQuantity($restaurant);
        $item->setQuantity(min(max(1, $quantity), $max));

        $cart = $item->getCart();
        if (null !== $cart) {
            $cart->setUpdatedAt(new \DateTimeImmutable());
        }

        $this->em->flush();
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $item->getCart();
        if (null !== $cart) {
            $cart->setUpdatedAt(new \DateTimeImmutable());
        }

        $this->em->remove($item);
        $this->em->flush();
    }

    public function clearCart(Cart $cart): void
    {
        foreach ($cart->getCartItems() as $item) {
            $this->em->remove($item);
        }
        $cart->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
    }

    public function getCartTotal(Cart $cart): float
    {
        $total = 0;
        foreach ($cart->getCartItems() as $item) {
            $restaurant = $item->getRestaurant();
            if (null !== $restaurant && null !== $restaurant->getTicketPrice()) {
                $total += (float) $restaurant->getTicketPrice() * $item->getQuantity();
            }
        }

        return $total;
    }

    public function getItemCount(Cart $cart): int
    {
        $count = 0;
        foreach ($cart->getCartItems() as $item) {
            $count += $item->getQuantity();
        }

        return $count;
    }

    private function getMaxQuantity(Restaurant $restaurant): int
    {
        $remaining = $restaurant->getMaxCapacity() - $restaurant->getTicketsSold();

        return min(10, max(0, $remaining));
    }
}
