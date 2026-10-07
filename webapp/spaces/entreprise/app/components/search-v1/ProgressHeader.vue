<script setup lang="ts">
// OÙ J'EN SUIS : les trois chiffres du tri, et ce qu'ils couvrent.
//
// ELLE NE PARLE QUE DU TRI ACCOMPLI : combien vus, retenus, écartés, et sur quelle part du jeu. Le
// nom de la recherche, le fil d'avancement et le rang « 8 / 234 » vivent dans la barre haute, qui
// survit au masquage de cette colonne sous 1024 pixels. Le fil et le rang y sont VOISINS et le
// restent : ils se lisent en paire, l'un disant la proportion vue, l'autre la position, et séparer
// l'un de l'autre ampute chacun de ce qui l'explique.
//
// Un fichier à part, et pas un bloc de la colonne : celle-ci dépasserait les ~200 lignes que le
// README autorise — « au-delà, découper : c'est le signe de plusieurs responsabilités agrégées ».
//
// TROIS ENCADRÉS, ET UN SEUL EST UN GESTE. Ils prennent la forme des encadrés de la fiche profil,
// et sa règle : toujours le même nombre de cases, sinon la grille change de largeur d'un profil à
// l'autre. Un encadré dont le geste n'a pas lieu d'être se DÉSACTIVE, il ne disparaît pas.
//
// PAS DE QUATRIÈME CASE « À VOIR » : elle compterait sur la PILE CHARGÉE quand la barre de
// progression compte sur le JEU ENTIER, et « 11 à voir » se lirait « 11 avant d'avoir fini » alors
// qu'il en reste deux cents.
//
// PAS DE GESTE « REPRENDRE » sur « Vus » : le parcours démarre sur les non vus, et reprendre un tri
// interrompu n'a pas d'objet. La case est un simple constat.
type Props = {
  /** Le jeu entier — lu pour dire, le cas échéant, que les compteurs n'en couvrent qu'une part. */
  total: number
  seenCount: number
  /** Taille de la pile chargée : c'est la portée réelle des compteurs. */
  loadedCount: number
  dismissedCount: number
  favoriteCount: number
}

const props = defineProps<Props>()

const emit = defineEmits<{ review: [] }>()

/**
 * Les trois chiffres, prêts pour le gabarit.
 *
 * `action` porte le geste quand l'encadré en a un — le chiffre et ce qu'on en fait au même endroit.
 * Sans `action`, l'encadré est un simple constat.
 *
 * Le geste est une FERMETURE et non un nom d'événement : `emit(nom)` sur une union de noms ne se
 * type pas, le compilateur ne pouvant pas prouver que l'argument correspond à la surcharge.
 */
const counts = computed(() => [
  // « Vus » compte ce qu'on a derrière soi, et rien de plus : le parcours démarrant sur les non vus,
  // il n'y a plus de tri à reprendre.
  { key: 'seen', value: props.seenCount, icon: 'i-lucide-eye', tint: 'text-muted', action: null },
  {
    key: 'favorites',
    value: props.favoriteCount,
    icon: 'i-lucide-star',
    tint: 'text-accent',
    action: null,
  },
  {
    key: 'dismissed',
    value: props.dismissedCount,
    icon: 'i-lucide-eye-off',
    tint: 'text-verdict-missed',
    action: {
      run: () => emit('review'),
      enabled: props.dismissedCount > 0,
      labelCount: props.dismissedCount,
      labelKey: 'dismissedTitle',
    },
  },
])
</script>

<template>
  <header class="search-v1-progress-header flex flex-none flex-col gap-element">
    <!-- UNE SEULE RANGÉE : en 2×2, les cases mangent cent quarante pixels d'une colonne qui en
         fait deux cent quatre-vingts.
         TROIS COLONNES ÉGALES, et c'est ce qui rend les trois nombres comparables d'un coup d'œil :
         à largeurs libres ils tombent à trois abscisses différentes, et il faut lire chaque case au
         lieu de balayer la rangée. Ça ne tient qu'à trois conditions — trois cases et pas quatre, un
         nombre sur sa PROPRE ligne, et donc une cellule qui ne loge que son libellé, « Écartés »,
         sept lettres. Les réunir sur une ligne tronque les libellés. -->
    <dl class="grid grid-cols-3 gap-1">
      <component
        :is="count.action ? 'button' : 'div'"
        v-for="count in counts"
        :key="count.key"
        :type="count.action ? 'button' : undefined"
        :disabled="count.action ? !count.action.enabled : undefined"
        :aria-label="
          count.action
            ? $t(`search.viewer.panel.${count.action.labelKey}`, { count: count.action.labelCount })
            : undefined
        "
        class="flex min-w-0 flex-col items-center gap-0.5 rounded-lg border border-line px-1.5 py-1 text-center"
        :class="
          count.action?.enabled
            ? 'cursor-pointer transition-colors hover:bg-surface-soft'
            : count.action
              ? 'opacity-50'
              : ''
        "
        @click="count.action?.enabled ? count.action.run() : undefined"
      >
        <!-- L'icône porte la TEINTE, le libellé reste lisible : une couleur ne doit pas payer sa
             présence en contraste.
             L'ORDRE DU DOM RESTE `dt` PUIS `dd` : une liste de définitions veut son
             terme avant sa définition, et un `dd` sans `dt` qui le précède n'est plus une paire.
             C'est `order-first` sur le nombre qui le remonte À L'ŒIL, sans rien casser pour qui
             écoute la page. -->
        <dt class="flex min-w-0 max-w-full items-center gap-1 text-caption text-muted">
          <UIcon
            :name="count.icon"
            :class="count.tint"
            class="h-3 w-3 flex-none"
            aria-hidden="true"
          />
          <span class="truncate">{{ $t(`search.viewer.panel.counts.${count.key}`) }}</span>
        </dt>
        <dd class="order-first text-small font-semibold tabular-nums" :class="count.tint">
          {{ count.value }}
        </dd>
      </component>
    </dl>

    <!-- Ce que les trois chiffres COUVRENT, et seulement quand ils ne couvrent pas tout : la pile
         chargée peut être plus courte que le jeu, et « à voir 29 » se lirait alors « il reste 29
         profils » quand il en reste cent cinquante-cinq. -->
    <p v-if="loadedCount < total" class="text-caption text-muted">
      {{ $t('search.viewer.panel.scope', { count: loadedCount }) }}
    </p>
  </header>
</template>
