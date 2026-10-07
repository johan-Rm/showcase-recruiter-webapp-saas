<?php

declare(strict_types=1);

namespace App\Domain\Candidate;

use App\Domain\Jarvi\JarviContacts;
use App\Entity\Candidate;
use App\Enum\RevealType;

/**
 * Ce qu'un profil a RÉELLEMENT à servir sur chaque accès payant.
 *
 * Deux lecteurs, une seule règle, et c'est tout l'objet de cette classe : le dévoilement s'en sert
 * pour ne pas facturer un accès vide, la carte candidat pour dire au recruteur si ça vaut un crédit.
 * Écrites deux fois, ces règles divergeraient au premier changement — l'écran éteindrait un canal
 * que le serveur facture, ou l'inverse, et rien ne le signalerait : aucun test pris isolément ne voit
 * une divergence entre deux vérités.
 *
 * ⚠️ Une présence n'est PAS une valeur. Cette classe dit s'il y a quelque chose ; ce qui décide de
 * le montrer est l'accès payé, et lui seul.
 */
final class RevealableContent
{
    /**
     * Coordonnées : `Person` D'ABORD, réplica Jarvi ENSUITE.
     *
     * `Person` porte ce que le candidat a lui-même déclaré ou corrigé — ça prime toujours. Mais
     * l'ingestion Jarvi ne l'alimente jamais : sans ce repli sur la réplica, les coordonnées d'un
     * profil sourcé restent invisibles alors qu'on les détient.
     *
     * Le réplica est absent tant qu'aucune synchro n'a eu lieu — rare, la projection en crée un
     * vide dès la création. Absent, il ne vaut pas erreur : il vaut « rien à servir ».
     */
    public static function email(Candidate $candidate): ?string
    {
        return $candidate->getPerson()->contactEmail ?? JarviContacts::email($candidate->getJarvi()->contacts ?? []);
    }

    public static function phone(Candidate $candidate): ?string
    {
        return $candidate->getPerson()->phone ?? JarviContacts::phone($candidate->getJarvi()->contacts ?? []);
    }

    /**
     * Le CV se juge sur le FICHIER, pas sur la longueur de la liste.
     *
     * Le réplica dépose une entrée par CV, avec son identifiant de fichier, son nom et son type. Une
     * entrée sans identifiant n'est pas téléchargeable : la compter reviendrait à vendre un document
     * qu'on ne saurait pas servir.
     */
    public static function hasResume(Candidate $candidate): bool
    {
        foreach ($candidate->getJarvi()->resumes ?? [] as $resume) {
            $fileId = $resume['fileId'] ?? null;
            if (\is_string($fileId) && '' !== trim($fileId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Le compte rendu se juge sur SON TEXTE, jamais sur « rencontré ».
     *
     * Un profil peut avoir été reçu en entretien sans que le compte rendu ait été rédigé. S'appuyer
     * sur la date d'entretien — déjà exposée, donc tentante parce que gratuite — reviendrait à
     * vendre un document vide.
     */
    public static function hasReport(Candidate $candidate): bool
    {
        return '' !== trim($candidate->starInterviewReport ?? '');
    }

    /** Y a-t-il quelque chose à servir sur cet accès ? */
    public static function has(Candidate $candidate, RevealType $type): bool
    {
        return match ($type) {
            RevealType::EMAIL => null !== self::email($candidate),
            RevealType::PHONE => null !== self::phone($candidate),
            RevealType::RESUME => self::hasResume($candidate),
            RevealType::INTERVIEW_REPORT => self::hasReport($candidate),
        };
    }
}
