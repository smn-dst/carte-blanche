<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\CartService;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CartExtension extends AbstractExtension
{
    public function __construct(
        private readonly Security $security,
        private readonly CartService $cartService,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_item_count', $this->getCartItemCount(...)),
        ];
    }

    public function getCartItemCount(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        $cart = $user->getCart();
        if (null === $cart) {
            return 0;
        }

        return $this->cartService->getItemCount($cart);
    }
}
