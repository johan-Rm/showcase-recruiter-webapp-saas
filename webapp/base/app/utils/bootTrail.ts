/**
 * Le minutage de la frise du rideau de démarrage — les instants, et rien d'autre.
 *
 * Module PUR : aucune minuterie, aucun DOM, aucun état. Il dit QUAND le jalon suivant s'allume et
 * COMBIEN de temps la frise réclame en tout. L'appelant décide s'il joue cette suite ; le rideau,
 * lui, se sert de la durée totale pour savoir jusqu'à quand il doit rester à l'écran.
 *
 * Il est ici, et non dans le composant, parce qu'une durée qui change ne se voit pas à l'œil nu :
 * c'est le seul morceau de cette tâche qu'une régression pourrait traverser en silence.
 */

/**
 * Durée de remplissage d'UN trait, en millisecondes.
 *
 * Doit rester alignée sur la transition CSS du trait (`BootTrail.vue`) : c'est elle qui dessine le
 * remplissage, celle-ci ne fait qu'enchaîner les avancées au même rythme.
 */
export const BOOT_TRAIL_FILL_MS = 600

/**
 * Les instants — en millisecondes depuis le montage — auxquels le jalon suivant s'allume.
 *
 * Le premier jalon est allumé d'entrée, tel que le serveur l'a rendu : la suite ne décrit donc que
 * les AVANCÉES, une par trait, jamais une par jalon. Trois jalons donnent deux instants.
 */
export const bootTrailAdvances = (stepCount: number): number[] =>
  stepCount < 2
    ? []
    : Array.from({ length: stepCount - 1 }, (_, index) => index * BOOT_TRAIL_FILL_MS)

/**
 * Le temps que la frise réclame pour aller jusqu'au bout, dernier trait entièrement rempli compris.
 *
 * C'est le PLANCHER d'affichage du rideau — jamais son plafond : un démarrage plus lent garde la
 * main, cette durée ne fait que l'empêcher d'être plus court. Zéro en deçà de deux jalons : sans
 * trait à remplir, il n'y a rien à attendre.
 */
export const bootTrailDurationMs = (stepCount: number): number =>
  stepCount < 2 ? 0 : (stepCount - 1) * BOOT_TRAIL_FILL_MS
