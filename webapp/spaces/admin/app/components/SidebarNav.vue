<script setup lang="ts">
// Navigation de l'espace admin, partagée par la sidebar desktop et le tiroir mobile (USlideover).
// Marque + trois groupes (Pilotage / Comptes / Supervision) en UNavigationMenu vertical, PUIS le
// pied : compte et notifications.
//
// Ce pied a longtemps vécu dans la barre haute, et ce fichier affirmait qu'il n'avait rien à faire
// ici. L'argument était que la barre haute est l'endroit conventionnel de ces contrôles ; il tient,
// mais il coûtait la seule bande horizontale de l'écran à de l'outillage qu'on consulte rarement —
// on ne change pas de thème plusieurs fois par jour. Le pied de colonne, lui, était vide.
// La bascule ne vaut qu'au-dessus de `lg` : en dessous la colonne n'existe pas, et le tiroir étant
// fermé par défaut, le compte y serait à deux gestes et la pastille de notification invisible.
// C'est pourquoi le pied ne se rend que si `collapsible` — le tiroir garde la navigation seule.
//
// Ce composant AFFICHE la navigation, il ne la déclare pas : la liste vit dans `useNav`, que
// le fil d'ariane lit aussi.
// REPLIÉE, la navigation se réduit à ses icônes. Le mode est NATIF au composant de menu : il masque
// les titres de groupes lui-même et rend les libellés en infobulle au survol. Les sept entrées
// portent toutes une icône — sans quoi replier les rendrait muettes.
//
// Deux props et un événement, jamais l'état lui-même : ce composant sert AUSSI le tiroir mobile, qui
// n'est jamais replié et n'a pas de bouton. Et un composant affiche, il ne mute pas l'état global —
// c'est la coque qui possède le repli et qui le bascule.
interface Props {
  collapsed?: boolean
  /** Le repli est-il seulement possible ici ? Faux dans le tiroir mobile. */
  collapsible?: boolean
}

const { collapsed = false, collapsible = false } = defineProps<Props>()

defineEmits<{ toggle: [] }>()

const { navGroups } = useNav()

// La page courante ne peut pas se signaler par la seule couleur : elle porte une cartouche ET un
// repère de bord. Le fond passe par `before`, la couche que le survol utilise déjà — posée en
// absolu, la cartouche n'ajoute aucun décalage et hérite du rayon. Le repère prend `after`, libre
// tant que la variante `highlight` reste éteinte.
//
// Il se pose en `end` — le côté de FIN de lecture, donc à droite en français et à gauche en arabe :
// une marque de fin de ligne suit le sens de lecture, elle ne se fixe pas à un bord physique.
const navUi = computed(() => ({
  label: 'text-muted',
  // REPLIÉ, la bibliothèque masque le libellé en `hidden` — donc `display: none`, ce qui le retire
  // AUSSI de l'arbre d'accessibilité : mesuré, les sept liens se présentaient sans aucun nom. On le
  // cache à l'œil seulement. `inline` est là pour annuler le `hidden` de la variante, que `sr-only`
  // ne touche pas : il déplace et rogne, il ne rétablit pas l'affichage.
  linkLabel: collapsed ? 'inline sr-only' : 'truncate',
  separator: 'hidden',
  // L'icône par défaut de la bibliothèque fait 20 px pour un libellé de 14 : elle passait avant le
  // mot. À 16 px elle l'accompagne.
  // `group-data-[active]` et pas une simple classe : la bibliothèque teinte l'icône active en
  // accent, et deux utilitaires de couleur au même poids se départagent par l'ordre de la feuille.
  // Le variant de groupe passe devant par sa spécificité, quel que soit cet ordre.
  linkLeadingIcon: 'size-4 group-data-[active]:text-highlighted',
  link: [
    // Cartouche NEUTRE, au fond de page : la ligne active se distingue par sa VALEUR, pas par une
    // teinte. Un fond mêlé d'accent ferait porter la marque à la couleur seule.
    'data-[active]:before:bg-bg',
    // Un repère PLEIN au bord de fin, sans diffusion ni halo : la marque est franche ou n'est pas.
    // Même rayon que la pilule (6 px) du seul côté qu'il touche — le lien n'a pas de rayon propre.
    'data-[active]:after:absolute data-[active]:after:inset-y-0 data-[active]:after:end-0',
    'data-[active]:after:w-1 data-[active]:after:rounded-e-[6px] data-[active]:after:bg-accent',
    // Le libellé et l'icône restent neutres : le repère du bord désigne déjà la ligne, les teinter
    // reviendrait à le dire deux fois, plus faiblement.
    'data-[active]:text-highlighted',
  ].join(' '),
}))
</script>

<template>
  <div class="sidebar-nav flex h-full flex-col gap-block">
    <!-- La marque et le bouton partagent une ligne quand la place existe ; repliés, ils s'empilent,
         le rail n'ayant pas la largeur de les mettre côte à côte. -->
    <div
      class="flex gap-element"
      :class="collapsed ? 'flex-col items-center' : 'items-start justify-between'"
    >
      <LayoutBrand orientation="vertical" :compact="collapsed" class="px-1" />

      <button
        v-if="collapsible"
        type="button"
        class="inline-grid size-8 flex-none cursor-pointer place-items-center rounded-lg text-muted transition-colors hover:bg-accent-soft hover:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :aria-expanded="!collapsed"
        :aria-label="collapsed ? $t('nav.admin.expandMenu') : $t('nav.admin.collapseMenu')"
        @click="$emit('toggle')"
      >
        <!-- Le chevron pointe vers où la colonne VA, pas où elle est. Il se retourne en arabe :
             replier va vers le bord de début de lecture, qui change de côté. -->
        <Icon
          :name="collapsed ? 'lucide:chevron-right' : 'lucide:chevron-left'"
          class="size-4 rtl:rotate-180"
          aria-hidden="true"
        />
      </button>
    </div>

    <!-- PAS de `tooltip` ici, et ce n'est pas un oubli. L'infobulle qui rendrait les libellés au
         survol réclame un fournisseur que seul `<UApp>` monte, et l'application ne le monte nulle
         part. L'activer sans lui ne reste pas sans effet : mesuré, le rail perdait une entrée sur
         sept, voyait deux titres de groupes réapparaître, et la colonne disparaissait au
         rechargement. Elle se réactivera quand le socle posera ce fournisseur — jusque-là, ce sont
         les noms accessibles qui portent seuls le sens des icônes. -->
    <UNavigationMenu
      orientation="vertical"
      :items="navGroups"
      :collapsed="collapsed"
      :aria-label="$t('nav.main')"
      :ui="navUi"
      class="flex-1"
    />

    <!-- PIED — les deux contrôles de l'opérateur, seulement là où la colonne existe vraiment. Le
         tiroir mobile rend le même composant : les y afficher les aurait mis derrière un tiroir
         fermé, à deux gestes au lieu d'un, et la pastille de notification y serait invisible. -->
    <div v-if="collapsible" class="border-t border-line pt-element">
      <UserControls orientation="vertical" :compact="collapsed" />
    </div>
  </div>
</template>
