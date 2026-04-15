<?php

namespace App\MessageHandler;

use App\Message\SendAbandonedCartEmailMessage;
use App\Repository\CartRepository;
use App\Service\SendMailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class SendAbandonedCartEmailHandler
{
    public function __construct(
        private CartRepository $cartRepository,
        private SendMailService $sendMailService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(SendAbandonedCartEmailMessage $message): void
    {
        $cart = $this->cartRepository->find($message->cartId);

        if (null === $cart) {
            return;
        }

        if ($cart->getCartItems()->isEmpty()) {
            return;
        }

        $user = $cart->getUser();
        if (null === $user || null === $user->getEmail()) {
            return;
        }

        $this->sendMailService->sendAbandonedCartEmail($user, $cart);

        $cart->setAbandonedCartEmailSentAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
