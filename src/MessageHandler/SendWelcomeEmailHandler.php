<?php

namespace App\MessageHandler;

use App\Message\SendWelcomeEmail;
use App\Repository\HandledMessageRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

/**
 * Le handler que fait tourner `messenger:consume async`.
 *
 * Il réserve la clé avant d'envoyer, et pas après. Le choix n'est pas neutre :
 * dans cet ordre, un processus tué entre la réservation et l'envoi perd le mail,
 * alors que l'ordre inverse le ferait partir deux fois. Pour un mail de
 * bienvenue, un doublon se voit et un manquant se rattrape, donc on protège
 * l'envoi. Un traitement qu'on préfère rejouer inutilement mériterait l'ordre
 * inverse : c'est une décision à prendre message par message, pas une règle.
 */
#[AsMessageHandler]
final readonly class SendWelcomeEmailHandler
{
    public function __construct(
        private HandledMessageRepository $handledMessages,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendWelcomeEmail $message): void
    {
        if (!$this->handledMessages->claim($message->idempotencyKey)) {
            $this->logger->info('Message déjà traité, celui-ci est un rejeu.', [
                'idempotency_key' => $message->idempotencyKey,
            ]);

            return;
        }

        $this->mailer->send(
            (new Email())
                ->from('bonjour@symfony-clever-demo.example')
                ->to($message->email)
                ->subject('Bienvenue')
                ->text("Bienvenue sur l'application de démonstration de la série.")
        );
    }
}
