<?php

namespace App\Entity;

use App\Repository\HandledMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Le registre des clés d'idempotence déjà traitées.
 *
 * Le transport livre au moins une fois : un worker tué avant d'avoir acquitté
 * son message le laisse en état « en cours » dans `messenger_messages`, et le
 * transport Doctrine le redistribue au bout du `redeliver_timeout`. Ce registre
 * est ce qui rend ce rejeu inoffensif, et la contrainte d'unicité est ce qui
 * tient quand deux scalers consomment le même message en même temps.
 */
#[ORM\Entity(repositoryClass: HandledMessageRepository::class)]
#[ORM\Table(name: 'handled_message')]
#[ORM\UniqueConstraint(name: 'uniq_handled_message_idempotency_key', columns: ['idempotency_key'])]
class HandledMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $idempotencyKey;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $handledAt;

    public function __construct(string $idempotencyKey)
    {
        $this->idempotencyKey = $idempotencyKey;
        $this->handledAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function getHandledAt(): \DateTimeImmutable
    {
        return $this->handledAt;
    }
}
