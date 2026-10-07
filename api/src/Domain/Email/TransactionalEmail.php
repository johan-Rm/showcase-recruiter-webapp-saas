<?php

declare(strict_types=1);

namespace App\Domain\Email;

/**
 * Le CONTENU d'un e-mail transactionnel, séparé de sa mise en page : les gabarits
 * `emails/transactional.{html,txt}.twig` la portent et rendent exactement ces champs.
 *
 * C'est le seul endroit qui nomme le contrat des gabarits. Sans lui, chaque émetteur recopierait les
 * clés et l'un d'eux écrirait `footerNote` là où Twig attend `footer_note` — Twig ne rend rien pour
 * une variable inconnue, la ligne disparaîtrait sans un mot (`strict_variables` n'est armé qu'en
 * test).
 */
final readonly class TransactionalEmail
{
    /**
     * @param list<string>      $paragraphs le corps, un paragraphe par entrée
     * @param list<string>      $changes    ce que cette livraison apporte, une entrée par ligne ;
     *                                      vide, le bloc n'est pas rendu
     * @param list<EmailAccess> $accesses   les accès à communiquer — recette uniquement, cf.
     *                                      EmailAccess ; vide, le bloc n'est pas rendu
     * @param string            $preheader  ligne d'aperçu des boîtes de réception, jamais affichée
     *                                      dans le message : sans elle, le client compose l'aperçu
     *                                      avec le début du corps, donc « Bonjour, »
     */
    public function __construct(
        public string $subject,
        public string $preheader,
        public string $heading,
        public array $paragraphs,
        public array $changes = [],
        public array $accesses = [],
        public ?EmailCta $cta = null,
        public ?string $note = null,
        public ?string $footerNote = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toContext(): array
    {
        return [
            'subject' => $this->subject,
            'preheader' => $this->preheader,
            'heading' => $this->heading,
            'paragraphs' => $this->paragraphs,
            'changes' => $this->changes,
            'accesses' => $this->accesses,
            'cta' => $this->cta,
            'note' => $this->note,
            'footer_note' => $this->footerNote,
        ];
    }
}
