<?php

namespace App\Command;

use App\Repository\HandledMessageRepository;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

/**
 * Vide le registre d'idempotence des entrées trop anciennes pour servir.
 *
 * Le registre ne fait que grandir : chaque message traité y laisse sa clé. Passé
 * quelques jours, plus aucun rejeu ne viendra la réclamer. Le travail est écrit
 * comme une commande, et c'est le déclencheur qui change : le Scheduler la joue
 * chaque nuit, une Clever Task la joue à la demande.
 *
 * Le fuseau est explicite : les serveurs de Clever Cloud sont en UTC, et sans lui
 * « 3 h » voudrait dire 4 h ou 5 h du matin à Paris selon la saison.
 */
#[AsCommand(
    name: 'app:handled-messages:purge',
    description: "Supprime du registre d'idempotence les clés trop anciennes",
)]
#[AsCronTask('0 3 * * *', timezone: 'Europe/Paris')]
final class PurgeHandledMessagesCommand
{
    public function __construct(
        private readonly HandledMessageRepository $handledMessages,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'Âge au-delà duquel une clé est supprimée')]
        string $olderThan = '30 days',
    ): int {
        $limit = new DatePoint('-'.$olderThan);
        $deleted = $this->handledMessages->purgeOlderThan($limit);

        $io->success(\sprintf('%d clé(s) enregistrée(s) avant le %s supprimée(s).', $deleted, $limit->format('Y-m-d H:i:s T')));

        return Command::SUCCESS;
    }
}
