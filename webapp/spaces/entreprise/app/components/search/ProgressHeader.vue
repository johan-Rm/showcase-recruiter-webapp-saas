<script setup lang="ts">
// OÙ J'EN SUIS : les trois chiffres du tri.
//
// ILS PORTENT SUR LA RECHERCHE ENTIÈRE, jamais sur ce qui est chargé. Ils comptaient les fiches en
// mémoire, et annonçaient 30 vus là où le serveur en comptait 74 — un chiffre juste sur ce qu'il
// regardait, faux sur ce qu'il prétendait dire. Un libellé gris s'excusait dessous ; il a disparu
// avec la cause.
//
// ELLE NE PARLE QUE DU TRI ACCOMPLI : combien vus, retenus, écartés. Le nom de la recherche et le
// rang « 8 / 234 » vivent dans la barre haute, qui survit au masquage de cette colonne sous
// 1024 pixels.
//
// Un fichier à part, et pas un bloc de la colonne : celle-ci dépasserait les ~200 lignes que le
// README autorise — « au-delà, découper : c'est le signe de plusieurs responsabilités agrégées ».
//
// TROIS ENCADRÉS, ET UN SEUL EST UN GESTE. Ils prennent la forme des encadrés de la fiche profil,
// et sa règle : toujours le même nombre de cases, sinon la grille change de largeur d'un profil à
// l'autre. Un encadré dont le geste n'a pas lieu d'être se DÉSACTIVE, il ne disparaît pas.
//
// PAS DE QUATRIÈME CASE « À VOIR » : le rang de la barre haute dit déjà où l'on en est dans le jeu,
// et un second décompte de la même chose se contredirait au premier écart.
//
// PAS DE GESTE « REPRENDRE » sur « Vus » : le parcours démarre sur les non vus, et reprendre un tri
// interrompu n'a pas d'objet. La case est un simple constat.
type Props = {
  seenCount: number
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
  { key: 'seen', value: props.seenCount, icon: 'i-lucide-eye', tint: '--muted', action: null },
  {
    key: 'favorites',
    value: props.favoriteCount,
    icon: 'i-lucide-star',
    tint: '--accent',
    action: null,
  },
  {
    key: 'dismissed',
    value: props.dismissedCount,
    icon: 'i-lucide-eye-off',
    tint: '--verdict-missed',
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
  <header class="search-progress-header flex flex-none flex-col gap-element">
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
        :style="{ '--bc': `var(${count.tint})` }"
        class="flex min-w-0 flex-col items-center gap-0.5 rounded-lg bg-[color-mix(in_srgb,var(--bc)_var(--tint-fill),transparent)] px-1.5 py-1 text-center text-[color-mix(in_srgb,var(--bc)_62%,var(--text))]"
        :class="
          count.action?.enabled
            ? 'cursor-pointer transition-colors hover:bg-[color-mix(in_srgb,var(--bc)_calc(var(--tint-fill)+14%),transparent)]'
            : count.action
              ? 'opacity-50'
              : ''
        "
        @click="count.action?.enabled ? count.action.run() : undefined"
      >
        <!-- TOUTE LA CASE PORTE SA TEINTE — le fond, le libellé, le nombre —, et c'est ce qui
             permet de distinguer les trois d'un coup d'œil sans les lire. La recette est celle des
             badges d'état : le fond mélange la teinte à du transparent, le texte à soixante-deux
             pour cent de l'encre de la page. La DOSE DU FOND, elle, suit le thème (`--tint-fill`) —
             sur fond clair le gris discret et l'accent tirent tous deux sur le vert et se
             confondent à faible dose. C'est ce mélange
             qui fait suivre le contraste d'un thème à l'autre, `--text` changeant avec eux — une
             teinte pure sur un aplat de la même teinte ne le ferait pas.
             SANS BORDURE : l'aplat suffit à poser la case. Un trait par-dessus lui ajoute un
             second contour là où la couleur en dessinait déjà un, et trois cadres alignés se
             remettent à se ressembler — c'est exactement ce qu'on vient de défaire.
             L'ICÔNE SEULE GARDE LA TEINTE PURE : elle est le repère de forme, et c'est elle qui
             porte le sens pour qui ne distingue pas le vert du rouge.
             L'ORDRE DU DOM RESTE `dt` PUIS `dd` : une liste de définitions veut son
             terme avant sa définition, et un `dd` sans `dt` qui le précède n'est plus une paire.
             C'est `order-first` sur le nombre qui le remonte À L'ŒIL, sans rien casser pour qui
             écoute la page. -->
        <dt class="flex min-w-0 max-w-full items-center gap-1 text-caption">
          <UIcon
            :name="count.icon"
            class="h-3 w-3 flex-none"
            style="color: var(--bc)"
            aria-hidden="true"
          />
          <span class="truncate">{{ $t(`search.viewer.panel.counts.${count.key}`) }}</span>
        </dt>
        <dd class="order-first text-small font-semibold tabular-nums">
          {{ count.value }}
        </dd>
      </component>
    </dl>
  </header>
</template>
