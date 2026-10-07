<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Enum\RevealType;

/**
 * Ce qu'une entreprise a payé sur UN candidat : quatre accès indépendants.
 *
 * Un objet plutôt que quatre booléens baladés de signature en signature : le masquage se lit alors
 * partout de la même façon (`$access->email`), et ajouter un accès ne réécrit pas les appels un
 * par un.
 *
 * L'identité n'y figure pas (task `API-018`) : nom, photo, employeur et profil LinkedIn se servent
 * sans crédit. Cet objet ne décrit plus que des MOYENS DE JOINDRE et des DOCUMENTS.
 */
final readonly class RevealedAccess
{
    public function __construct(
        public bool $email = false,
        public bool $phone = false,
        public bool $resume = false,
        public bool $report = false,
    ) {
    }

    /** Rien de payé : l'état de tout profil qu'on n'a pas encore dévoilé. */
    public static function none(): self
    {
        return new self();
    }

    /**
     * @param array<string, true> $types dévoilements indexés par valeur d'enum
     */
    public static function fromTypes(array $types): self
    {
        return new self(
            isset($types[RevealType::EMAIL->value]),
            isset($types[RevealType::PHONE->value]),
            isset($types[RevealType::RESUME->value]),
            isset($types[RevealType::INTERVIEW_REPORT->value]),
        );
    }

    /** Au moins un accès payé — ce que la Table appelle « dévoilé » face à « non dévoilé ». */
    public function any(): bool
    {
        return $this->email || $this->phone || $this->resume || $this->report;
    }

    /**
     * Au moins un moyen de joindre acheté.
     *
     * L'export s'y adosse : exiger les DEUX canaux ferait payer deux crédits ce qu'un seul ouvrait
     * hier, et exiger l'identité n'a plus de sens — elle est gratuite.
     */
    public function anyContact(): bool
    {
        return $this->email || $this->phone;
    }
}
