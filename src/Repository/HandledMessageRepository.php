<?php

namespace App\Repository;

use App\Entity\HandledMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HandledMessage>
 */
class HandledMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HandledMessage::class);
    }

    /**
     * Réserve la clé, et dit si l'appelant est le premier à l'obtenir.
     *
     * Le SELECT seul ne suffirait pas : entre lui et l'INSERT, un autre worker
     * peut passer. Il évite le travail inutile dans le cas courant, et c'est la
     * contrainte d'unicité qui tranche la course. La violation qu'elle lève se
     * lit comme un « quelqu'un d'autre s'en est déjà chargé ».
     */
    public function claim(string $idempotencyKey): bool
    {
        if (null !== $this->findOneBy(['idempotencyKey' => $idempotencyKey])) {
            return false;
        }

        $manager = $this->getEntityManager();

        try {
            $manager->persist(new HandledMessage($idempotencyKey));
            $manager->flush();
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        return true;
    }
}
