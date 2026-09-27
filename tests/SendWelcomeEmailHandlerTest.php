<?php

namespace App\Tests;

use App\Message\SendWelcomeEmail;
use App\MessageHandler\SendWelcomeEmailHandler;
use App\Repository\HandledMessageRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Le transport livre au moins une fois : ce que ce test vérifie, c'est que le
 * handler survit à la seconde livraison sans refaire le travail. Il tourne sans
 * base de données, comme le reste de la suite, parce que le job de tests du
 * workflow de déploiement ne monte pas de conteneur.
 */
final class SendWelcomeEmailHandlerTest extends TestCase
{
    public function testItSendsTheEmailOnlyOnceForTheSameEvent(): void
    {
        // Un stub, pas un mock : ce qu'on attend du registre, c'est une réponse,
        // pas un appel. La première livraison obtient la clé, la seconde se la
        // voit refuser, exactement comme la contrainte d'unicité le ferait.
        $handledMessages = $this->createStub(HandledMessageRepository::class);
        $handledMessages->method('claim')->willReturnOnConsecutiveCalls(true, false);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $handler = new SendWelcomeEmailHandler($handledMessages, $mailer, new NullLogger());
        $message = SendWelcomeEmail::forAccount('alice@example.com');

        $handler($message);
        $handler($message);
    }

    /**
     * La clé doit rester la même d'un envoi à l'autre, sinon le registre ne
     * reconnaît jamais un rejeu. C'est tout l'écart avec un UUID tiré à l'envoi.
     */
    public function testTheIdempotencyKeyIsStableAcrossDispatches(): void
    {
        self::assertSame(
            SendWelcomeEmail::forAccount('alice@example.com')->idempotencyKey,
            SendWelcomeEmail::forAccount('alice@example.com')->idempotencyKey,
        );
    }
}
