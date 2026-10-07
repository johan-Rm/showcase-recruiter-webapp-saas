<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Un métier reconnu sur un profil : encore exercé, ou déjà quitté (task API-026).
 *
 * Il n'y a **pas de troisième cas** ici, et c'est délibéré. Le poste du moment n'est connu que sur
 * la moitié du vivier ; quand il ne l'est pas, la correspondance ne porte simplement aucune de ces
 * deux valeurs. Un code `INCONNU` aurait obligé chaque appelant à le traiter pour un résultat
 * identique au silence — et surtout, il aurait fallu l'AFFICHER, alors qu'il n'y a rien à dire.
 *
 * `PAST` n'est jamais déduit d'une absence : il exige la preuve d'un AUTRE poste occupé aujourd'hui.
 */
enum JobTenure: string
{
    /** Le profil occupe ce métier en ce moment. */
    case CURRENT = 'CURRENT';

    /** Le profil occupe aujourd'hui un AUTRE métier, connu et différent de celui-ci. */
    case PAST = 'PAST';
}
