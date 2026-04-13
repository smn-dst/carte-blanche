<?php

namespace App\EventListener;

use App\Entity\Restaurant;
use App\Service\EmbeddingService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
class RestaurantEmbeddingListener
{
    public function __construct(
        private readonly EmbeddingService $embeddingService,
    ) {
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleEvent($args);
    }

    /** @param LifecycleEventArgs<EntityManagerInterface> $args */
    private function handleEvent(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Restaurant) {
            return;
        }

        // Génère l'embedding en tâche de fond : les erreurs sont loguées sans lever d'exception
        $this->embeddingService->embedRestaurant($entity);
    }
}
