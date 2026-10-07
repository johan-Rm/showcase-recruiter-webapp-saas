<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Domain\Email\EmailAccess;
use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Les deux blocs que la notification de déploiement ajoute au gabarit commun (task API-065).
 *
 * PREMIER TEST DE CETTE MÉCANIQUE : le gabarit sert cinq émetteurs et n'était couvert par rien. Il
 * ne porte donc pas seulement sur ce qu'on ajoute, mais sur ce qu'on ne casse pas — un e-mail de
 * connexion doit sortir exactement comme avant.
 *
 * C'est l'ABSENCE qui est ici le vrai sujet. Une apparence se relit à l'œil sur un message reçu ;
 * ce qui ne se voit pas, c'est ce qui NE devrait pas être là. Un bloc d'accès rendu de sa propre
 * initiative serait une fuite de mots de passe dans un e-mail envoyé à un candidat, et rien dans le
 * message reçu ne le signalerait à celui qui l'a écrit.
 *
 * Aucune base de données n'est sollicitée : composer un message ne touche qu'aux gabarits.
 */
final class DeployNotificationEmailTest extends KernelTestCase
{
    public function testTheChangesAppearInBothVersionsOfTheMessage(): void
    {
        $email = $this->compose(changes: [
            'une recherche large se signale, sans rien empêcher',
            'un canal sans donnée ne se facture plus',
        ]);

        foreach ([$email->getHtmlBody(), $email->getTextBody()] as $body) {
            self::assertStringContainsString('Ce qui change', (string) $body);
            self::assertStringContainsString('une recherche large se signale, sans rien empêcher', (string) $body);
            self::assertStringContainsString('un canal sans donnée ne se facture plus', (string) $body);
        }
    }

    public function testTheAccessesAppearInBothVersionsOfTheMessage(): void
    {
        $email = $this->compose(accesses: [
            new EmailAccess('Espace entreprise', 'https://entreprise.exemple.test', 'entreprise@exemple.test', 'motdepasse-de-recette'),
        ]);

        foreach ([$email->getHtmlBody(), $email->getTextBody()] as $body) {
            self::assertStringContainsString('Vos accès', (string) $body);
            self::assertStringContainsString('Espace entreprise', (string) $body);
            self::assertStringContainsString('https://entreprise.exemple.test', (string) $body);
            // Étiquetés, pas juxtaposés : sans elles, deux suites de caractères côte à côte
            // n'apprennent pas laquelle recopier dans quel champ (task API-071).
            self::assertStringContainsString('login : entreprise@exemple.test', (string) $body);
            self::assertStringContainsString('mot de passe : motdepasse-de-recette', (string) $body);
        }
    }

    /**
     * Le cas des quatre e-mails du produit : ils ne fournissent aucun des deux blocs, ils ne doivent
     * donc en voir aucun. C'est la garde qui compte le plus de tout ce fichier.
     */
    public function testNeitherBlockAppearsWhenNothingIsProvided(): void
    {
        $email = $this->compose();

        foreach ([$email->getHtmlBody(), $email->getTextBody()] as $body) {
            self::assertStringNotContainsString('Ce qui change', (string) $body);
            self::assertStringNotContainsString('Vos accès', (string) $body);
        }
    }

    /**
     * La même garde, prise par l'autre bout : c'est le mot de passe lui-même qu'on cherche dans un
     * message qui n'en demandait pas. Un futur remaniement du gabarit qui renommerait le titre du
     * bloc passerait à travers le test précédent, pas à travers celui-ci.
     */
    public function testAPasswordNeverReachesAMessageThatDidNotAskForOne(): void
    {
        $email = $this->compose();
        $whole = $email->getHtmlBody().$email->getTextBody();

        self::assertStringNotContainsString('motdepasse-de-recette', $whole);
    }

    /**
     * Le message reste entier quand les deux blocs s'ajoutent : ils s'insèrent, ils ne remplacent
     * rien. Sans cette garde, une ancre de gabarit mal placée pourrait avaler le bouton.
     */
    public function testTheRestOfTheMessageSurvivesTheTwoBlocks(): void
    {
        $email = $this->compose(
            changes: ['un changement'],
            accesses: [new EmailAccess('Un accès', 'https://exemple.test', 'moi', 'secret')],
        );

        foreach ([$email->getHtmlBody(), $email->getTextBody()] as $body) {
            self::assertStringContainsString('Le titre', (string) $body);
            self::assertStringContainsString('Un paragraphe.', (string) $body);
            self::assertStringContainsString('Ouvrir', (string) $body);
            self::assertStringContainsString('https://exemple.test/action', (string) $body);
            self::assertStringContainsString("L'équipe Shortlist", (string) $body);
        }
    }

    /**
     * @param list<string>      $changes
     * @param list<EmailAccess> $accesses
     */
    private function compose(array $changes = [], array $accesses = []): Email
    {
        self::bootKernel();
        $mailer = self::getContainer()->get(TransactionalMailer::class);

        return $mailer->compose(
            new TransactionalEmail(
                subject: 'Un sujet',
                preheader: 'Un aperçu.',
                heading: 'Le titre',
                paragraphs: ['Un paragraphe.'],
                changes: $changes,
                accesses: $accesses,
                cta: new EmailCta('Ouvrir', 'https://exemple.test/action'),
            ),
            new Address('destinataire@exemple.test'),
        );
    }
}
