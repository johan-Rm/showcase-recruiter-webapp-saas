<?php

declare(strict_types=1);

namespace App\Command;

use App\Domain\Email\EmailAccess;
use App\Domain\Email\EmailCta;
use App\Domain\Email\TransactionalEmail;
use App\Domain\Email\TransactionalMailer;
use InvalidArgumentException;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mime\Address;

/**
 * Compose l'e-mail de fin de déploiement avec le gabarit du produit, et le rend SANS L'ENVOYER
 * (task API-065).
 *
 * Le partage des rôles est le sujet de cette commande : l'application possède l'apparence — mêmes
 * couleurs, même logo attaché, mêmes versions claire, sombre et texte que les quatre e-mails du
 * produit —, la machine de déploiement possède le transport et ce qu'il y a à raconter. Recopier
 * l'apparence dans un script d'exploitation aurait dupliqué cent cinquante lignes qui divergent au
 * premier changement, sans que personne ne s'en aperçoive avant de rouvrir un message.
 *
 * Elle n'envoie rien, et c'est double : le mot de passe du serveur de messagerie n'a rien à faire
 * dans cette image, et une commande sans effet de bord se teste — là où un envoi réclamerait un
 * serveur pour être éprouvé.
 *
 * Elle ne calcule rien non plus. L'image ne contient que le code, jamais l'historique du dépôt :
 * la liste des changements ne peut venir que de l'appelant.
 *
 * TOUT ARRIVE PAR L'ENTRÉE STANDARD, y compris les mots de passe de recette. Une option de ligne de
 * commande les exposerait dans la liste des processus à tout compte de la machine, le temps de
 * l'exécution.
 *
 *   … | php bin/console app:deploy:compose-notification > message.eml
 */
#[AsCommand(
    name: 'app:deploy:compose-notification',
    description: "Compose l'e-mail de fin de déploiement (JSON sur l'entrée standard) et le rend sur la sortie",
)]
final class ComposeDeployNotificationCommand extends Command
{
    public function __construct(private readonly TransactionalMailer $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Les diagnostics vont sur la sortie d'erreur : la sortie standard ne porte QUE le message,
        // que l'appelant redirige tel quel vers son serveur de messagerie.
        $errors = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $raw = file_get_contents('php://stdin');
        if (false === $raw || '' === trim($raw)) {
            $errors->writeln('<error>Rien reçu sur l\'entrée standard : un objet JSON est attendu.</error>');

            return Command::INVALID;
        }

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($raw, true, 32, \JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            // Le détail compte : sans lui, l'appelant ne sait pas si son bloc est tronqué ou mal
            // échappé, et une donnée mal formée produirait sinon un message silencieusement bancal.
            $errors->writeln(\sprintf('<error>Entrée illisible : %s</error>', $e->getMessage()));

            return Command::INVALID;
        }

        try {
            $from = self::address(self::requiredString($payload, 'from'));
            $recipients = array_map(self::address(...), self::requiredList($payload, 'to'));
            if ([] === $recipients) {
                throw new InvalidArgumentException('« to » ne contient aucun destinataire.');
            }

            $mail = new TransactionalEmail(
                subject: self::requiredString($payload, 'subject'),
                preheader: self::requiredString($payload, 'preheader'),
                heading: self::requiredString($payload, 'heading'),
                paragraphs: self::requiredList($payload, 'paragraphs'),
                changes: self::optionalList($payload, 'changes'),
                accesses: self::accesses($payload),
                cta: self::cta($payload),
                note: self::optionalString($payload, 'note'),
                footerNote: self::optionalString($payload, 'footerNote'),
            );
        } catch (InvalidArgumentException $e) {
            $errors->writeln(\sprintf('<error>Entrée incomplète : %s</error>', $e->getMessage()));

            return Command::INVALID;
        }

        $email = $this->mailer->compose($mail, ...$recipients)->from($from);

        // `write` et non `writeln` : pas un octet de plus que le message, qui se termine déjà comme
        // il faut. Sans OUTPUT_RAW, la console interpréterait au passage les chevrons du HTML.
        $output->write($email->toString(), false, OutputInterface::OUTPUT_RAW);

        return Command::SUCCESS;
    }

    /**
     * Les adresses acceptent les deux écritures : « nom@domaine » et « Nom <nom@domaine> ». La
     * seconde est celle qui s'affiche chez le destinataire.
     */
    private static function address(string $value): Address
    {
        return Address::create($value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!\is_string($value) || '' === trim($value)) {
            throw new InvalidArgumentException(\sprintf('« %s » manque ou n\'est pas un texte.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return \is_string($value) && '' !== trim($value) ? $value : null;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private static function requiredList(array $payload, string $key): array
    {
        if (!\array_key_exists($key, $payload)) {
            throw new InvalidArgumentException(\sprintf('« %s » manque.', $key));
        }

        return self::optionalList($payload, $key);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private static function optionalList(array $payload, string $key): array
    {
        $value = $payload[$key] ?? [];
        if (!\is_array($value)) {
            throw new InvalidArgumentException(\sprintf('« %s » n\'est pas une liste.', $key));
        }

        $lines = [];
        foreach ($value as $line) {
            if (!\is_string($line)) {
                throw new InvalidArgumentException(\sprintf('« %s » contient une entrée qui n\'est pas un texte.', $key));
            }
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function cta(array $payload): ?EmailCta
    {
        $cta = $payload['cta'] ?? null;
        if (!\is_array($cta)) {
            return null;
        }

        return new EmailCta(self::requiredString($cta, 'label'), self::requiredString($cta, 'url'));
    }

    /**
     * Absents ou vides, aucun accès n'est rendu — c'est le défaut, et il est volontaire : ce gabarit
     * sert cinq émetteurs qui n'en veulent pas. En revanche un bloc PRÉSENT mais incomplet est une
     * erreur, pas un bloc à moitié rendu : un accès sans mot de passe n'aide personne.
     *
     * @param array<string, mixed> $payload
     *
     * @return list<EmailAccess>
     */
    private static function accesses(array $payload): array
    {
        $entries = $payload['accesses'] ?? [];
        if (!\is_array($entries)) {
            throw new InvalidArgumentException('« accesses » n\'est pas une liste.');
        }

        $accesses = [];
        foreach ($entries as $entry) {
            if (!\is_array($entry)) {
                throw new InvalidArgumentException('« accesses » contient une entrée qui n\'est pas un objet.');
            }

            $accesses[] = new EmailAccess(
                self::requiredString($entry, 'label'),
                self::requiredString($entry, 'url'),
                self::requiredString($entry, 'login'),
                self::requiredString($entry, 'password'),
            );
        }

        return $accesses;
    }
}
