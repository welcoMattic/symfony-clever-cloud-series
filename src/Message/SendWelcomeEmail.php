<?php

namespace App\Message;

/**
 * Le message que le worker de l'article 5 consomme.
 *
 * La clé d'idempotence voyage avec le message et se déduit de l'événement
 * métier, l'inscription de ce compte : deux livraisons du même événement
 * portent donc la même clé. Un UUID tiré à l'envoi ne conviendrait pas, un
 * double envoi en produirait deux et les deux traitements passeraient.
 */
final readonly class SendWelcomeEmail
{
    public function __construct(
        public string $email,
        public string $idempotencyKey,
    ) {
    }

    public static function forAccount(string $email): self
    {
        return new self($email, 'welcome.'.$email);
    }
}
