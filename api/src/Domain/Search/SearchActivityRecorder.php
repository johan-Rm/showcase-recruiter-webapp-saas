<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Entity\SavedSearch;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Ce qui garde une recherche vivante — le contact humain, et lui seul.
 *
 * Un seul point d'entrée pour tous les gestes qui comptent : ouvrir le jeu, marquer vu, écarter,
 * remettre, retenir, libérer, ajuster les critères, renommer, partager, exporter. Éparpillée en
 * autant d'affectations, la règle se serait perdue au premier geste ajouté — et une recherche
 * activement travaillée se serait refermée dans le dos de son auteur.
 *
 * **Le recalcul du jeu n'est PAS un contact.** Une ingestion Jarvi touche toutes les recherches de
 * la base ; si elle passait par ici, la clôture automatique ne fermerait plus jamais rien.
 *
 * Il écrit tout de suite, plutôt que de compter sur un `flush` d'appelant : la moitié des gestes
 * concernés sont des lectures, qui n'en ont pas.
 */
final readonly class SearchActivityRecorder
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function record(SavedSearch $search, ?DateTimeImmutable $at = null): void
    {
        $search->touch($at);

        $this->em->flush();
    }
}
