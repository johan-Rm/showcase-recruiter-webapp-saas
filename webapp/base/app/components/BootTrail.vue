<script setup lang="ts">
// La frise du rideau de démarrage : des jalons reliés par des traits que l'accent remplit l'un
// après l'autre. Purement présentative — elle reçoit le jalon courant, elle ne le calcule pas. La
// minuterie, le plancher d'affichage et le choix des jalons appartiennent à `AppBootShell`.
//
// DÉCORATIVE, et masquée aux lecteurs d'écran : le rideau annonce déjà « Chargement… », et trois
// mots lus par-dessus seraient coupés en plein milieu par la fin du chargement. `disabled` va avec
// `aria-hidden` : il retire les déclencheurs de Nuxt UI du parcours de tabulation, car un contenu
// masqué qui reste atteignable au clavier est pire que pas de masquage du tout.
//
// `UStepper` (Nuxt UI) porte la structure — pastille, icône, trait, libellé — et positionne ses
// traits en propriétés LOGIQUES (`start`/`end`), donc l'arabe se retourne sans code dédié. Seule
// l'apparence du trait est reprise : le thème le peint d'un bloc quand l'étape est franchie, alors
// qu'on veut le voir SE remplir.
defineProps<{
  /** Les jalons, dans l'ordre du parcours. */
  steps: { icon: string; title: string }[]
  /** Indice du jalon allumé — les traits qui le précèdent sont pleins. */
  active: number
}>()

// Le remplissage se joue sur un pseudo-élément posé au bord de DÉBUT du trait, dont la largeur passe
// de zéro à tout : en `start`/`w-*`, il suit le sens de lecture au lieu d'être figé vers la droite.
// Un dégradé glissé aurait demandé une direction physique, donc une exception pour l'arabe.
//
// `group-data-[state=completed]:bg-line` neutralise le thème, qui peindrait sinon le trait entier
// d'un coup et couvrirait le remplissage ; `group-data-[disabled]:opacity-100` annule le
// grisonnement que `disabled` applique aux traits — ici, `disabled` ne dit pas « indisponible »,
// il dit « rien à cliquer ».
//
// La durée reste alignée sur `BOOT_TRAIL_FILL_MS`, qui cadence les avancées.
const ui = {
  separator: [
    'overflow-hidden bg-line group-data-[state=completed]:bg-line group-data-[disabled]:opacity-100',
    "after:absolute after:inset-y-0 after:start-0 after:w-0 after:rounded-full after:bg-accent after:content-['']",
    'after:transition-[width] after:duration-[600ms] after:ease-out',
    'group-data-[state=completed]:after:w-full',
    // Mouvement réduit : les traits sont pleins DÈS LE RENDU SERVEUR, avant même que la moindre
    // ligne de JavaScript s'exécute. Sans cette classe, la frise s'affiche vide puis se complète
    // d'un coup à l'hydratation — un sursaut, exactement ce que la préférence demande d'éviter.
    'motion-reduce:after:w-full',
  ].join(' '),
  title: 'text-muted',
}
</script>

<template>
  <UStepper
    class="boot-trail w-full max-w-xs"
    :items="steps"
    :model-value="active"
    :ui="ui"
    size="sm"
    disabled
    aria-hidden="true"
  />
</template>
