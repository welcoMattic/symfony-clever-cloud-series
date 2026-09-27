<?php

namespace App\Command;

use App\Message\SendWelcomeEmail;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * De quoi donner du travail au worker sans attendre un vrai visiteur.
 *
 * Lancez-la deux fois avec la même adresse : le second message part bien dans la
 * file, il est bien consommé, et le handler le reconnaît comme un rejeu. C'est
 * l'idempotence qui se vérifie, pas la déduplication à l'envoi.
 */
#[AsCommand(
    name: 'app:welcome',
    description: "Met un mail de bienvenue dans la file asynchrone",
)]
final class SendWelcomeEmailCommand
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: "L'adresse du nouveau compte")]
        string $email,
    ): int {
        $message = SendWelcomeEmail::forAccount($email);

        $this->bus->dispatch($message);

        $io->success(\sprintf('Message mis en file avec la clé « %s ».', $message->idempotencyKey));

        return Command::SUCCESS;
    }
}
