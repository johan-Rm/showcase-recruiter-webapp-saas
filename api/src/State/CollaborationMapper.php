<?php

declare(strict_types=1);

namespace App\State;

use App\ApiResource\Output\AssignmentOutput;
use App\ApiResource\Output\FavoriteOutput;
use App\ApiResource\Output\ProfileNoteOutput;
use App\ApiResource\Output\SavedSearchOutput;
use App\Entity\CandidateAssignment;
use App\Entity\Favorite;
use App\Entity\ProfileNote;
use App\Entity\Recruiter;
use App\Entity\SavedSearch;

/**
 * Mapping Entity → Output DTO plat pour la collaboration entreprise (favoris, notes).
 */
final class CollaborationMapper
{
    public static function toFavorite(Favorite $favorite): FavoriteOutput
    {
        return new FavoriteOutput(
            $favorite->getUuid()->toString(),
            $favorite->candidate->getUuid()->toString(),
            $favorite->addedBy->getUuid()->toString(),
            $favorite->getCreatedAt(),
        );
    }

    public static function toAssignment(CandidateAssignment $assignment): AssignmentOutput
    {
        return new AssignmentOutput(
            $assignment->getUuid()->toString(),
            $assignment->candidate->getUuid()->toString(),
            $assignment->assignee->getUuid()->toString(),
            $assignment->assignedBy->getUuid()->toString(),
            $assignment->assignedAt,
        );
    }

    /**
     * `newCount`, `seenCount` et `isOwn` dépendent du recruteur qui lit — la même recherche partagée
     * ne se sérialise pas pareil pour son auteur et pour un destinataire.
     */
    public static function toSavedSearch(
        SavedSearch $search,
        Recruiter $reader,
        int $newCount,
        int $resultCount,
        int $seenCount,
        int $favoriteCount,
    ): SavedSearchOutput {
        $owner = $search->owner->getPerson();

        // `newCount` ne décrit pas un fait neutre : il dit « il y a quelque chose à aller voir ».
        // Sur une recherche close, c'est faux, et la règle est tenue ICI pour les quatre chemins
        // plutôt que dans chaque client — un client qui l'oublierait ferait clignoter chaque
        // semaine une recherche que son auteur a déclarée terminée (task API-028).
        if (null !== $search->closedAt) {
            $newCount = 0;
        }

        return new SavedSearchOutput(
            $search->getUuid()->toString(),
            $search->name,
            $search->criteria,
            $search->isAutoNamed,
            $search->isShared,
            $search->owner->getUuid()->toString(),
            trim($owner->givenName.' '.$owner->familyName),
            $search->owner->getId() === $reader->getId(),
            $newCount,
            $resultCount,
            $seenCount,
            $favoriteCount,
            $search->closedAt,
            $search->getCreatedAt(),
            $search->getUpdatedAt(),
        );
    }

    public static function toNote(ProfileNote $note): ProfileNoteOutput
    {
        return new ProfileNoteOutput(
            $note->getUuid()->toString(),
            $note->candidate->getUuid()->toString(),
            $note->author->getUuid()->toString(),
            $note->body,
            $note->getCreatedAt(),
            $note->getUpdatedAt(),
        );
    }
}
