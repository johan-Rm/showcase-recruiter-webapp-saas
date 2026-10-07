<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Trace la dernière connexion (tâche 100) : à chaque login réussi sur le firewall `login`
 * (json_login → JWT), pose `User::lastLoginAt`. Filtré sur ce firewall pour NE PAS compter les
 * rafraîchissements de token (`/v1/auth/refresh`) comme des connexions.
 */
final readonly class LastLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    /** @return array<class-string, string> */
    public static function getSubscribedEvents(): array
    {
        return [LoginSuccessEvent::class => 'onLoginSuccess'];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        if ('login' !== $event->getFirewallName()) {
            return;
        }

        $user = $event->getUser();
        if (!$user instanceof User) {
            return;
        }

        $user->setLastLoginAt(new DateTimeImmutable());
        $this->em->flush();
    }
}
