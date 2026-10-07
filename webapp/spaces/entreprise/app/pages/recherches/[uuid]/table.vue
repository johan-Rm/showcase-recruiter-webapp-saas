<script setup lang="ts">
import { jobTenureOf, roleOf, rowSkillsOf } from '#services/search/candidate'
import { displayName } from '#services/search/criteria'
import { exportFileName, exportScope } from '#services/search/export'
import { presenceWindowLabel } from '#services/search/presence'
import { channelState, payableCount } from '#services/search/reveal'
import { statesOf } from '#services/search/states'
import { overflowsLoaded } from '#services/search/stack'
import { filterByView, toSearchView, viewSlug, type SearchView } from '#services/search/views'

import type { TableColumn } from '@nuxt/ui'
import type { CandidateCard } from '#shared/types/candidate-card'
import { REVEAL_TYPES } from '#shared/types/reveal'
import type { RevealType } from '#shared/types/reveal'

definePageMeta({ layout: 'cockpit' })

const route = useRoute()
const router = useRouter()
const uuid = computed(() => String(route.params.uuid))
const { t } = useLocale()
const { update } = useSearches()

const metStateOf = (profile: CandidateCard) =>
  statesOf(profile).filter((state) => 'met' === state.key)

const {
  search,
  candidates,
  total,
  matchCount,
  favorites,
  selection,
  toggleSelected,
  clearSelection,
  toggleFavorite,
  pending,
  error,
  refresh,
} = useSearch(() => uuid.value)

const { cost, costOf, affords, reveal } = useReveal()

const editable = computed(() => true === search.value?.isOwn)
const name = computed(() =>
  search.value ? displayName(search.value, t) || t('search.card.unnamed') : '',
)

const rename = async (value: string) => {
  if (search.value) await update(search.value, { name: value })
}

const BATCH_CONFIRM_ABOVE = 10
const BATCH_MAX = 50
const revealType = ref<RevealType | null>(null)
const exporting = ref(false)
const gdprAcknowledged = ref(false)
const busy = ref(false)

const selectedCards = computed(() =>
  candidates.value.filter((candidate) => selection.value.includes(candidate.id)),
)
const scope = computed(() => exportScope(selectedCards.value))

const runOnSelection = async (action: () => Promise<void>): Promise<void> => {
  busy.value = true
  try {
    await action()
  } finally {
    busy.value = false
  }
}

const download = async (): Promise<void> => {
  if (!gdprAcknowledged.value || 0 === scope.value.exportable.length) return

  await runOnSelection(async () => {
    const csv = await $fetch<string>(`/api/searches/${uuid.value}/export`, {
      method: 'POST',
      body: { candidateIds: scope.value.exportable, gdprAcknowledged: true },
    })
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }))
    const link = document.createElement('a')
    link.href = url
    link.download = exportFileName(search.value ? displayName(search.value, t) : '')
    link.click()
    URL.revokeObjectURL(url)
  })

  clearSelection()
  exporting.value = false
  gdprAcknowledged.value = false
}

/**
 * CE QUE CHAQUE CANAL COÛTERAIT, à l'instant, sur la sélection courante — le chiffre que le bouton
 * porte avant le clic, et non plus seulement celui que la confirmation récapitule après.
 *
 * Un canal dont plus rien n'est à payer rend zéro : son bouton s'éteint plutôt que de proposer un
 * geste qui ne ferait rien.
 */
const payable = computed<Record<RevealType, number>>(
  () =>
    Object.fromEntries(
      REVEAL_TYPES.map((type) => [type, payableCount(selectedCards.value, type)]),
    ) as Record<RevealType, number>,
)

/** Le coût, ou la raison de son absence — les trois réponses que le menu peut faire sur un canal. */
const channelLabel = (type: RevealType): string => {
  if (payable.value[type] > 0)
    return t('search.reveal.cost', { count: payable.value[type] * cost.value })

  return t(
    `search.reveal.${'owned' === channelState(selectedCards.value, type) ? 'owned' : 'none'}`,
  )
}

const batchCost = computed(() =>
  revealType.value ? costOf(selectedCards.value, revealType.value) : 0,
)
const batchPayable = computed(() => (revealType.value ? payable.value[revealType.value] : 0))

const askReveal = async (type: RevealType): Promise<void> => {
  revealType.value = type
  if (payable.value[type] <= BATCH_CONFIRM_ABOVE) await runReveal()
}

const confirming = computed({
  get: () => null !== revealType.value && batchPayable.value > BATCH_CONFIRM_ABOVE,
  set: (open: boolean) => {
    if (!open) revealType.value = null
  },
})

/**
 * L'ACTION CONSOMME LA SÉLECTION, et ce n'est pas une commodité : tant qu'elle tient, la barre haute
 * montre le menu à la place des onglets. Sans ce vidage, on reste enfermé après avoir agi — les
 * lignes se dé-floutent sous les yeux, et il faut penser à sortir soi-même d'un état qu'on n'a pas
 * choisi de garder.
 */
const runReveal = async (): Promise<void> => {
  const type = revealType.value
  if (!type) return

  await runOnSelection(async () => {
    await reveal(selection.value.slice(0, BATCH_MAX), type)
    await refresh()
  })
  clearSelection()
  revealType.value = null
}

const view = computed<SearchView>({
  get: () => toSearchView(route.query.vue),
  set: (next) => {
    void router.replace({ query: { ...route.query, vue: viewSlug(next) } })
  },
})

const today = new Date()
const rows = computed(() =>
  'favorites' === view.value ? favorites.value : filterByView(candidates.value, view.value),
)
const hiddenSelected = computed(
  () =>
    selection.value.length - rows.value.filter((row) => selection.value.includes(row.id)).length,
)

/**
 * LES RETENUS EN PREMIER, et sans jamais se désactiver.
 *
 * C'est la vue par défaut : la désactiver quand rien n'est retenu ouvrirait l'écran sur un onglet
 * éteint. C'est l'état vide qui prend le relais — il dit ce qui manque, là où un onglet grisé ne dit
 * rien et laisse chercher.
 */
const views = computed(() => [
  {
    value: 'favorites' as const,
    label: `${t('search.view.favorites')} ${favorites.value.length}`,
  },
  {
    value: 'all' as const,
    // « Tous 100 sur 352 » quand la recherche déborde : l'onglet montre ce qu'on peut travailler,
    // la mention finale dit ce que la recherche ramène. Sur une recherche courte les deux nombres
    // se confondent, et le second se tait plutôt que de se répéter.
    label: overflowsLoaded(total.value, matchCount.value)
      ? `${t('search.view.all')} ${total.value} ${t('search.viewer.panel.of', { count: matchCount.value })}`
      : `${t('search.view.all')} ${total.value}`,
  },
])

/**
 * CE QUE LE DÉCLENCHEUR DIT AVANT QU'ON L'OUVRE — sur quoi on va agir, jamais ce que ça coûte.
 *
 * Les quatre canaux ont des coûts différents ; un chiffre unique posé ici en inventerait un
 * cinquième, qui ne correspondrait à aucun geste réel. Le coût vit dans les entrées du menu.
 */
const selectionLabel = computed(() =>
  hiddenSelected.value > 0
    ? `${t('search.selection.count', { count: selection.value.length })} · ${t('search.selection.hidden', { count: hiddenSelected.value })}`
    : t('search.selection.count', { count: selection.value.length }),
)

/**
 * LE MENU DE LA SÉLECTION — deux groupes, et le séparateur porte la frontière : ce qui **engage**
 * au-dessus, ce qui **range** en dessous.
 *
 * Le premier groupe est vide sur une recherche partagée : dévoiler et exporter sont réservés à
 * l'auteur — le serveur refuse l'export en toutes lettres —, et un menu dont la moitié des entrées
 * est éteinte fait chercher pourquoi là où un menu court dit ce qui est possible.
 *
 * « Voir les profils sélectionnés » n'est pas un ornement : la sélection survit à la navigation, on
 * peut donc arriver ici avec des profils cochés hors de la vue par défaut. Sans cette entrée,
 * l'écran annoncerait « 2 hors de cette vue » sans offrir le geste qui les ramène — et les onglets,
 * masqués par la sélection, ne sont plus là pour le faire.
 */
const selectionMenu = computed(() => {
  const engaging = editable.value
    ? [
        ...REVEAL_TYPES.map((type) => ({
          // Le coût est DANS le libellé, pas dans un ornement de raccourci clavier : il fait partie
          // de ce que l'entrée propose, et c'est la même formulation que portait le bouton.
          //
          // Et quand il n'y a rien à payer, l'entrée dit LAQUELLE des deux raisons : tout est acquis,
          // ou il n'y a rien à obtenir. Confondues sous un même zéro, elles laissent le recruteur
          // sans savoir s'il tient déjà la donnée ou s'il doit chercher la personne autrement.
          label: `${t(`search.reveal.action.${type}`)} ${channelLabel(type)}`,
          // Un canal dont plus rien n'est à payer s'éteint : proposer un geste qui ne ferait rien
          // est pire que ne pas le proposer.
          disabled: busy.value || 0 === payable.value[type],
          onSelect: () => {
            void askReveal(type)
          },
        })),
        {
          label: t('search.export.action'),
          icon: 'i-lucide-download',
          disabled: busy.value,
          onSelect: () => {
            exporting.value = true
          },
        },
      ]
    : []

  const housekeeping = [
    {
      label: t('search.selection.show'),
      icon: 'i-lucide-eye',
      disabled: 0 === hiddenSelected.value,
      onSelect: () => {
        view.value = 'all'
      },
    },
    // Le doublon de la croix est voulu : elle est le geste rapide, l'entrée est le geste qu'on
    // trouve quand on ouvre le menu pour chercher comment sortir.
    {
      label: t('search.selection.clear'),
      icon: 'i-lucide-x',
      onSelect: () => {
        clearSelection()
      },
    },
  ]

  return engaging.length > 0 ? [engaging, housekeeping] : [housekeeping]
})

/**
 * Le fil remonte à la Visionneuse, jamais au tableau lui-même.
 *
 * Il porte le nom COURANT de la recherche — celui que la barre haute laisse éditer — et non une
 * copie prise à l'ouverture : renommer en haut et lire l'ancien nom deux lignes plus bas se verrait.
 */
const trail = computed(() => [
  { label: name.value, to: `/recherches/${uuid.value}` },
  { label: t('page.entreprise.table.title') },
])

const { isOpen, open } = useProfilePanel()
const openedId = ref<string | null>(null)

// La LIGNE porte déjà la vignette : on la passe au tiroir, qui l'affiche sans attendre le réseau.
// L'identifiant seul l'aurait laissé vide le temps d'un aller-retour, sur un geste qui doit répondre
// du premier coup.
const openProfile = (candidate: CandidateCard): void => {
  openedId.value = candidate.id
  void open(candidate)
}

watch(isOpen, (opened) => {
  if (!opened) openedId.value = null
})

const tableMeta = computed(() => ({
  class: {
    tr: (row: { original: CandidateCard }) =>
      [
        selection.value.includes(row.original.id) ? 'bg-accent-soft' : 'hover:bg-surface-soft',
        openedId.value === row.original.id ? 'is-open' : '',
      ]
        .filter(Boolean)
        .join(' '),
  },
}))

const columns: TableColumn<CandidateCard>[] = [
  { id: 'select', header: '' },
  { id: 'role', header: () => t('search.column.role') },
  { id: 'experience', header: () => t('search.column.experience') },
  // L'ENTÊTE NOMME UN VERBE, et c'est la seule colonne dans ce cas : les autres montrent une donnée,
  // celle-ci offre un geste. Sa quatrième place suit la même logique — retenir se décide à portée du
  // poste et de l'ancienneté, pas au onzième coup d'œil.
  { id: 'favorite', header: () => t('search.column.retain') },
  { id: 'identity', header: () => t('search.column.name') },
  { id: 'email', header: () => t('search.column.email') },
  { id: 'phone', header: () => t('search.column.phone') },
  { id: 'presence', header: () => t('search.column.presence') },
  { id: 'location', header: () => t('search.column.city') },
  { id: 'mobility', header: () => t('search.column.mobility') },
]
</script>

<template>
  <div data-page="search-table" class="flex min-h-0 flex-1 flex-col gap-element">
    <!-- LA GOUTTIÈRE SITUE, elle n'agit plus. Elle a porté sept commandes, dont quatre dépensaient
         des crédits, dans la bande la plus fine de la coque et à trois rangées des lignes qu'elles
         visaient ; le dernier bouton qui y restait remontait à la Visionneuse, alors que c'est elle
         qui vient AVANT. Le fil dit la même chose sans réintroduire un geste, et l'espace n'a plus
         qu'une seule façon de remonter — celle de l'écran de création de recherche.
         L'aide passe à droite, en pastille : un texte nu à cette place se lirait comme un bouton
         éteint. -->
    <Gutter data-zone="gutter">
      <Breadcrumb :items="trail" />

      <template #action>
        <!-- Elle s'efface sous `lg` : une bande fine ne tient pas les deux, et savoir où l'on est
             prime sur une aide que le titre de la page redit en partie. -->
        <p class="hidden items-center gap-1.5 text-small text-muted lg:flex">
          <UIcon name="i-lucide-info" class="h-4 w-4 flex-none" aria-hidden="true" />
          {{ $t('search.guide.table') }}
        </p>
      </template>
    </Gutter>

    <ActionBar data-zone="actionbar">
      <!-- LE SURTITRE DIT CE QU'EST LA LIGNE DU DESSOUS. Un nom de recherche est souvent
           auto-généré depuis les critères — « Product Owner · scrum » se lit alors comme un
           intitulé de poste, et rien ne dit qu'on est DANS une recherche enregistrée.
           MASQUÉ AUX LECTEURS D'ÉCRAN : le titre porte déjà, en mention invisible, le nom de
           l'écran. Les deux ne disent pas la même chose — l'un nomme l'écran, l'autre nomme
           l'objet — mais les entendre tous les deux à chaque lecture du titre serait redondant.
           `text-label` porte le rôle : capitales, graisse et interlettrage d'une étiquette de
           section. SEUL LE CORPS est réécrit, et c'est une valeur à la main assumée — onze pixels
           est le plancher de l'échelle, et ce surtitre doit passer en dessous pour rester un
           filigrane plutôt qu'un titre. Les trois autres attributs du palier restent appariés.
           IL S'EFFACE PAR L'OPACITÉ, jamais en allégeant la graisse : la charte pose que les quatre
           attributs de ce palier ne valent qu'ensemble, et un petit corps espacé mais maigre cesse
           de se lire comme une étiquette pour redevenir du contenu. On le fond, on ne le change
           pas.
           EN ACCENT ET FONDU DE MOITIÉ : la teinte le rattache au produit plutôt qu'au gris des
           textes secondaires, et le fondu l'empêche de disputer l'attention au nom qu'il coiffe.
           L'accent à pleine opacité crierait ici — c'est la couleur du seul geste de décision de
           l'écran. -->
      <div class="flex min-w-0 flex-col">
        <span class="text-label text-[0.5625rem] text-accent opacity-40" aria-hidden="true">
          {{ $t('search.viewer.panel.current') }}
        </span>

        <h1 class="flex min-w-0 items-baseline font-display text-h3">
          <span class="sr-only">{{ $t('page.entreprise.table.title') }} ·</span>
          <SearchShellName
            :name="name"
            :editable="editable"
            :placeholder="$t('search.sentence.name.placeholder')"
            :label="$t('search.sentence.name.label')"
            :edit-label="$t('search.sentence.name.edit')"
            @rename="rename"
          />
        </h1>
      </div>

      <!-- SA DROITE A TROIS ÉTATS, jamais deux à la fois : les filtres, l'avancement (sur la
           Visionneuse), ou les actions sur la sélection. C'est la bande qui alterne par
           construction — y loger le menu évite d'ajouter une quatrième bande qui apparaîtrait et
           pousserait les lignes. La contrepartie est assumée : on ne change plus de vue tant qu'une
           sélection est active, et c'est « Voir les profils sélectionnés » qui rattrape le cas. -->
      <template #end>
        <UTabs
          v-if="0 === selection.length"
          v-model="view"
          :items="views"
          variant="link"
          size="sm"
          :content="false"
        />

        <template v-else>
          <UDropdownMenu :items="selectionMenu" size="sm">
            <UButton
              color="neutral"
              variant="outline"
              size="sm"
              icon="i-lucide-check"
              trailing-icon="i-lucide-chevron-down"
            >
              {{ selectionLabel }}
            </UButton>
          </UDropdownMenu>

          <!-- LA SORTIE SE VOIT. Un état qui masque les onglets ne cache pas sa sortie dans ce
               qu'il masque : reléguée au seul menu, elle demanderait de deviner qu'elle est
               là-dedans, pour revoir des filtres qu'on n'a pas choisi de perdre. -->
          <UButton
            color="neutral"
            variant="ghost"
            size="sm"
            icon="i-lucide-x"
            :aria-label="$t('search.selection.clear')"
            :disabled="busy"
            @click="clearSelection()"
          />
        </template>
      </template>
    </ActionBar>

    <!-- Le même conseil que sur la Visionneuse, et au même endroit : une recherche ne doit pas
         montrer deux comportements selon l'outil qui l'affiche. -->

    <section
      data-zone="content"
      class="search-table relative flex min-h-0 flex-1 flex-col overflow-hidden rounded-lg bg-surface"
    >
      <SearchState v-if="pending" tone="loading" :title="$t('search.loading')" />
      <SearchState
        v-else-if="error"
        tone="error"
        :title="$t('search.error')"
        :hint="$t('search.state.errorHint')"
      />
      <SearchState
        v-else-if="!search?.criteria.length"
        icon="i-lucide-list-filter"
        :title="$t('search.viewer.noCriteria')"
        :hint="$t('search.state.noCriteriaHint')"
      />

      <!-- RIEN DE RETENU, ET C'EST LE DÉBUT DU PARCOURS, pas une panne : cet écran s'ouvre sur la
           récolte, et la récolte se fait dans la Visionneuse. Il dit donc ce qui manque et y ramène,
           plutôt que de basculer tout seul sur « Tous » — un onglet par défaut qui change d'une
           visite à l'autre fait disparaître l'entonnoir au moment précis où il devrait s'expliquer.
           L'onglet « Tous » reste actif au-dessus : le rattrapage ne passe pas par le vide. -->
      <SearchState
        v-else-if="'favorites' === view && 0 === favorites.length"
        icon="i-lucide-star"
        :title="$t('page.entreprise.table.emptyRetained')"
        :hint="$t('page.entreprise.table.emptyRetainedHint')"
      >
        <UButton :to="`/recherches/${uuid}`" color="neutral" variant="outline">
          {{ $t('page.entreprise.viewer.title') }}
        </UButton>
      </SearchState>

      <template v-else>
        <!-- LE BLOC DU TABLEAU NE PORTE QUE SES LIGNES : jamais de bande au-dessus des entêtes.
             Une bande qui surgit à la sélection pousse les lignes vers le bas et donne à l'écran
             deux grammaires — une bande qui alterne son contenu, une autre qui apparaît. Les gestes
             sur la sélection vivent dans la barre haute, qui alterne déjà. -->
        <UTable
          :data="rows"
          :columns="columns"
          :meta="tableMeta"
          sticky
          :empty="$t('page.entreprise.table.empty')"
          class="min-h-0 flex-1"
          @select="(_event, row) => openProfile(row.original)"
        >
          <template #select-cell="{ row }">
            <UCheckbox
              :model-value="selection.includes(row.original.id)"
              :aria-label="$t('search.selection.toggle')"
              @update:model-value="toggleSelected(row.original.id)"
              @click.stop
            />
          </template>

          <!-- LA CELLULE A UNE LARGEUR, et c'est elle qui borne la colonne. Elle prend celle que
               l'intitulé avait déjà le droit d'occuper : sans borne, la seconde ligne poussait la
               colonne à 48 % du tableau — 731 px de compétences contre 241 px de titre au plus —
               et affamait l'e-mail, le téléphone et la fenêtre Maroc, qui porteront de vraies
               valeurs dès le premier dévoilement. -->
          <template #role-cell="{ row }">
            <div class="flex w-[28ch] min-w-0 flex-col gap-1 overflow-hidden">
              <!-- LE TITRE TIENT LA GAUCHE, les qualificatifs filent à droite. Mêlés sur un même
                   plan, ils se lisaient comme un titre à rallonge dont la longueur changeait à
                   chaque ligne ; séparés, l'œil descend une colonne d'intitulés alignés. -->
              <div class="flex items-center gap-element">
                <span
                  class="min-w-0 flex-1 truncate"
                  :class="roleOf(row.original).isFallback ? 'italic text-muted' : undefined"
                >
                  {{ roleOf(row.original).text || '—' }}
                </span>
                <!-- « actuel » suffit : la colonne s'appelle POSTE et l'intitulé est juste à côté.
                     RIEN quand le serveur ne sait pas — une fois sur deux — et surtout pas
                     « actuel » par défaut : l'absence ne dit pas le passé, elle dit l'ignorance. -->
                <span v-if="jobTenureOf(row.original)" class="flex-none text-small text-muted">
                  {{ $t(`search.column.tenure.${jobTenureOf(row.original)}`) }}
                </span>
                <UiBadge
                  v-for="state in metStateOf(row.original)"
                  :key="state.key"
                  :tint="state.tint"
                  :icon="state.icon"
                >
                  {{ $t(state.labelKey) }}
                </UiBadge>
              </div>

              <!-- AUTANT DE BADGES QUE LA LARGEUR PERMET, et la hauteur d'une seule rangée les
                   compte : ceux qui passent à la ligne sont hors champ. Aucun mot n'est donc jamais
                   coupé — une compétence tronquée se reconnaît mal, or c'est ce qu'on vient y lire —
                   et la colonne ne bouge pas d'une ligne à l'autre. Le nombre visible varie, c'est
                   le prix.
                   Ce qui tombe n'est pas quelconque : `rowSkillsOf` met en tête ce qui a fait entrer
                   le profil, donc ce sont les moins parlantes qui sortent du champ.

                   AUCUNE SECONDE LIGNE sans compétence : une ligne vide sous l'intitulé se lit comme
                   une donnée manquante, alors qu'il n'y a rien à manquer.

                   `bdi` : l'élément isole le sens de lecture. Dans une page en arabe, un nom de
                   technologie latin — et ils ne se traduisent pas — se lit sinon à l'envers. -->
              <ul
                v-if="rowSkillsOf(row.original).length"
                class="flex h-5 min-w-0 flex-wrap gap-1 overflow-hidden"
              >
                <li v-for="skill in rowSkillsOf(row.original)" :key="skill">
                  <UBadge color="neutral" variant="outline" size="sm">
                    <bdi>{{ skill }}</bdi>
                  </UBadge>
                </li>
              </ul>
            </div>
          </template>

          <template #experience-cell="{ row }">
            <span class="whitespace-nowrap tabular-nums">
              {{ $t('search.profile.years', { count: row.original.experienceYears }) }}
            </span>
          </template>

          <template #favorite-cell="{ row }">
            <UButton
              icon="i-lucide-star"
              :color="row.original.isFavorite ? 'primary' : 'neutral'"
              variant="ghost"
              size="xs"
              :disabled="!editable"
              :aria-label="
                $t(row.original.isFavorite ? 'search.action.retained' : 'search.action.retain')
              "
              @click.stop="toggleFavorite(row.original)"
            />
          </template>

          <template #identity-cell="{ row }">
            <div class="flex items-center gap-element">
              <UAvatar
                :src="row.original.photoUrl ?? undefined"
                :text="row.original.givenName.slice(0, 1)"
                size="xs"
              />
              <SearchMaskedValue
                :value="
                  row.original.familyName
                    ? `${row.original.givenName} ${row.original.familyName}`
                    : null
                "
                present
                kind="name"
              />
            </div>
          </template>

          <template #email-cell="{ row }">
            <SearchMaskedValue
              :value="row.original.contactEmail"
              :present="row.original.hasEmail"
              kind="email"
            />
          </template>

          <template #phone-cell="{ row }">
            <SearchMaskedValue
              :value="row.original.phone"
              :present="row.original.hasPhone"
              kind="phone"
            />
          </template>

          <template #presence-cell="{ row }">
            <SearchPill
              v-if="presenceWindowLabel(row.original.presencePeriods, today)"
              icon="i-lucide-calendar-days"
            >
              {{ presenceWindowLabel(row.original.presencePeriods, today) }}
            </SearchPill>
            <span v-else class="text-muted">—</span>
          </template>

          <template #location-cell="{ row }">
            {{ [row.original.city, row.original.country].filter(Boolean).join(', ') || '—' }}
          </template>

          <template #mobility-cell="{ row }">
            <SearchPill v-if="row.original.geoMobility" icon="i-lucide-map-pin">
              {{ $t('search.mobility.all') }}
            </SearchPill>
            <span v-else class="text-muted">—</span>
          </template>
        </UTable>
      </template>
    </section>

    <ProfilePanel />

    <UModal v-model:open="exporting" :title="$t('search.export.title')">
      <template #body>
        <div class="flex flex-col gap-block">
          <p v-if="0 === scope.exportable.length" class="max-w-readable" role="alert">
            {{ $t('search.export.nothing') }}
          </p>
          <template v-else>
            <p class="max-w-readable">
              {{ $t('search.export.body', { count: scope.exportable.length }) }}
              <template v-if="scope.ignored > 0">
                {{ $t('search.export.ignored', { count: scope.ignored }) }}
              </template>
            </p>
            <UCheckbox v-model="gdprAcknowledged" :label="$t('search.export.gdpr')" />
          </template>
          <div class="flex justify-end gap-element">
            <UButton color="neutral" variant="ghost" @click="exporting = false">
              {{ $t('search.export.cancel') }}
            </UButton>
            <UButton
              v-if="scope.exportable.length > 0"
              :loading="busy"
              :disabled="!gdprAcknowledged"
              @click="download()"
            >
              {{ $t('search.export.submit') }}
            </UButton>
          </div>
        </div>
      </template>
    </UModal>

    <UModal v-model:open="confirming" :title="$t('search.reveal.confirm.title')">
      <template #body>
        <div class="flex flex-col gap-block">
          <p class="max-w-readable">
            {{
              $t('search.reveal.confirm.body', {
                count: batchPayable,
                cost: batchCost,
                access: revealType ? $t(`search.reveal.action.${revealType}`) : '',
              })
            }}
          </p>
          <p v-if="selection.length > BATCH_MAX" class="max-w-readable text-small text-muted">
            {{ $t('search.reveal.confirm.capped', { max: BATCH_MAX, count: selection.length }) }}
          </p>
          <p v-if="!affords(batchCost)" class="max-w-readable text-small text-alert" role="alert">
            {{ $t('search.reveal.insufficient') }}
          </p>
          <div class="flex justify-end gap-element">
            <UButton color="neutral" variant="ghost" @click="revealType = null">
              {{ $t('search.reveal.confirm.cancel') }}
            </UButton>
            <UButton :loading="busy" :disabled="!affords(batchCost)" @click="runReveal()">
              {{ $t('search.reveal.confirm.submit', { cost: batchCost }) }}
            </UButton>
          </div>
        </div>
      </template>
    </UModal>
  </div>
</template>

<style scoped>
.search-table :deep(th) {
  padding: var(--spacing-cell-y) var(--spacing-cell-x);
  background: var(--accent-soft);
  color: var(--accent);
  font-size: var(--text-label);
  line-height: var(--text-label--line-height);
  font-weight: var(--text-label--font-weight);
  letter-spacing: var(--text-label--letter-spacing);
  text-transform: uppercase;
}

[dir='rtl'] .search-table :deep(th) {
  letter-spacing: normal;
}

.search-table :deep(td) {
  padding: var(--spacing-cell-y) var(--spacing-cell-x);
  font-size: var(--text-caption);
  line-height: var(--text-caption--line-height);
}

.search-table :deep(tr.is-open > td:first-child) {
  border-inline-start: 2px solid var(--accent);
}
</style>
