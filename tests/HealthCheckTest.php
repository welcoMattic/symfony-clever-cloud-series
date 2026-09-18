<?php

namespace App\Tests;

use App\EventListener\HealthCheckListener;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le healthcheck est la seule URL que Clever Cloud appelle pour décider si une
 * instance entre dans le load balancer. C'est donc le premier candidat à un
 * test : s'il casse, le déploiement ne sort jamais du sas.
 */
final class HealthCheckTest extends WebTestCase
{
    public function testItAnswersOkWithoutTouchingTheDatabase(): void
    {
        $client = static::createClient();
        $client->request('GET', HealthCheckListener::PATH);

        $response = $client->getResponse();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"status":"ok"}', $response->getContent());
    }

    /**
     * Le listener court-circuite kernel.request : il ne doit le faire que sur
     * son propre chemin, sinon il répondrait à la place de l'application.
     */
    public function testItLeavesTheOtherPathsAlone(): void
    {
        $client = static::createClient();
        $client->request('GET', '/cc-health-not-really');

        self::assertSame(Response::HTTP_NOT_FOUND, $client->getResponse()->getStatusCode());
    }
}
