<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Candidate\RevealedAccess;
use App\Enum\RevealType;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'une entreprise a payé sur un candidat — quatre accès qui ne se commandent pas l'un l'autre.
 *
 * C'est la classe qui traduit des lignes de dévoilement en droits d'affichage : si elle se trompe,
 * on sert gratuitement ce qui se paie, ou l'inverse. D'où des cas explicites plutôt qu'un test
 * paramétré : chacun nomme une règle produit.
 */
final class RevealedAccessTest extends TestCase
{
    public function testRienDePayeNOuvreRien(): void
    {
        $access = RevealedAccess::none();

        self::assertFalse($access->email);
        self::assertFalse($access->phone);
        self::assertFalse($access->resume);
        self::assertFalse($access->report);
        self::assertFalse($access->any());
        self::assertFalse($access->anyContact());
    }

    /** La règle centrale de la task API-018 : payer un canal n'ouvre pas l'autre. */
    public function testUnCanalPayeNOuvrePasLAutre(): void
    {
        $access = RevealedAccess::fromTypes([RevealType::EMAIL->value => true]);

        self::assertTrue($access->email);
        self::assertFalse($access->phone);
        self::assertTrue($access->anyContact());
    }

    public function testLeTelephoneSeulOuvreLeTelephoneSeul(): void
    {
        $access = RevealedAccess::fromTypes([RevealType::PHONE->value => true]);

        self::assertFalse($access->email);
        self::assertTrue($access->phone);
        self::assertTrue($access->anyContact());
    }

    /** Un document acheté ne rend joignable personne : `anyContact` doit rester faux. */
    public function testUnDocumentNeRendPasJoignable(): void
    {
        $access = RevealedAccess::fromTypes([RevealType::RESUME->value => true]);

        self::assertTrue($access->resume);
        self::assertTrue($access->any());
        self::assertFalse($access->anyContact());
    }

    public function testLesQuatreAccesSeCumulent(): void
    {
        $access = RevealedAccess::fromTypes([
            RevealType::EMAIL->value => true,
            RevealType::PHONE->value => true,
            RevealType::RESUME->value => true,
            RevealType::INTERVIEW_REPORT->value => true,
        ]);

        self::assertTrue($access->email);
        self::assertTrue($access->phone);
        self::assertTrue($access->resume);
        self::assertTrue($access->report);
    }

    /**
     * Une valeur inconnue — un `CONTACT` hérité que la commande de conversion n'aurait pas encore
     * traité — n'ouvre RIEN. Elle ne doit surtout pas être interprétée comme un accès complet.
     */
    public function testUneValeurHeriteeNOuvreRien(): void
    {
        $access = RevealedAccess::fromTypes(['CONTACT' => true]);

        self::assertFalse($access->email);
        self::assertFalse($access->phone);
        self::assertFalse($access->any());
    }
}
