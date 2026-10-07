<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Candidate\ProfileViewRecorder;
use App\Entity\Candidate;
use App\Entity\Recruiter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Le cas que l'appel réel ne sait pas provoquer : la base tombe pendant l'enregistrement d'une
 * visite. Le recruteur a demandé une fiche — il doit la recevoir. Une trace perdue est un incident
 * mineur, une fiche perdue est une panne (tâche API-072).
 */
final class ProfileViewRecorderTest extends TestCase
{
    public function testRecordingFailureIsSwallowedAndLogged(): void
    {
        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('createQuery')->willThrowException(
            new RuntimeException('base injoignable')
        );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('error');

        $recorder = new ProfileViewRecorder($entityManager, $logger);

        $recorder->record(self::createStub(Candidate::class), self::createStub(Recruiter::class));
    }

    public function testNothingIsLoggedWhenTheVisitIsAlreadyKnown(): void
    {
        $query = self::createStub(Query::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('createQuery')->willReturn($query);
        // Déjà vue aujourd'hui : ni insertion, ni écriture, ni erreur.
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        $query->method('setParameter')->willReturnSelf();
        $query->method('getSingleScalarResult')->willReturn(1);

        (new ProfileViewRecorder($entityManager, $logger))
            ->record(self::createStub(Candidate::class), self::createStub(Recruiter::class));
    }
}
