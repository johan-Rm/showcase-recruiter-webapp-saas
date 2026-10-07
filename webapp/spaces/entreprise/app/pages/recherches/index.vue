<script setup lang="ts">
import { groupSearches } from '#services/search/collection'

import type { StoredSearch } from '#shared/types/search'

// Mes recherches — la collection, en cartes. C'est d'ici qu'on ouvre, qu'on reprend ou qu'on
// clôture une recherche, et c'est aussi d'ici qu'on la renomme, la duplique, la partage ou la
// supprime — par le menu de sa carte.
//
// Layout STANDARD `cockpit` : cette page compose ses sections (gouttière + corps). Corps SANS
// surface — ce sont les CARTES qui la portent, sinon elles se fondraient dans le fond.
//
// La page ORCHESTRE : elle lit un état déjà chargé et ne connaît ni le BFF ni la forme de l'API.
definePageMeta({ layout: 'cockpit' })

const { searches, ownerName, setClosed, update, duplicate, remove, pending, error } = useSearches()

// Survoler, focaliser ou faire entrer une carte à l'écran met sa recherche en tête du
// chargement de fond : la page relaie ce que la carte annonce, la carte ne mute rien.
const { intend } = useSearchIntent()

const { t } = useLocale()

// Trois groupes, et ils ne se replient pas pareil. Les closes descendent sous un pli : ranger est
// TOUT le bénéfice de la clôture, et ouvert par défaut il ne rangerait rien. Les recherches d'un
// collègue, elles, restent DÉPLIÉES — c'est du travail vivant qu'on m'a confié, et le replier
// reviendrait à le cacher.
const groups = computed(() => groupSearches(searches.value))

const actionErrorKey = ref('')

// Ce que chaque geste de la carte déclenche. La carte ANNONCE, la page exécute — et chaque
// opération rend une clé i18n plutôt que de lever : un refus s'affiche par une phrase, pas par un
// code HTTP.
const cardActions = (search: StoredSearch) => ({
  // Le seul geste qui MÈNE quelque part : les autres agissent sur place et rendent une clé d'erreur.
  onModify: () => navigateTo(`/recherches/${search.id}/modifier`),
  onClosure: async (isClosed: boolean) => {
    actionErrorKey.value = await setClosed(search, isClosed)
  },
  onRename: async (name: string) => {
    actionErrorKey.value = await update(search, { name })
  },
  onDuplicate: async () => {
    actionErrorKey.value = await duplicate(search)
  },
  onShare: async (isShared: boolean) => {
    actionErrorKey.value = await update(search, { isShared })
  },
  onDelete: async () => {
    actionErrorKey.value = await remove(search)
  },
})
</script>

<template>
  <div class="flex min-h-0 flex-1 flex-col gap-element">
    <Gutter>
      <span class="text-small text-muted">{{ $t('search.guide.list') }}</span>
      <!-- On ne crée pas une recherche ici : les deux moteurs (critères, langage naturel) vivent sur
           l'accueil, seul endroit où l'on décrit qui l'on cherche. -->
      <template #action>
        <UButton to="/">{{ $t('search.new') }}</UButton>
      </template>
    </Gutter>

    <section class="relative flex min-h-0 flex-1 flex-col gap-block overflow-auto">
      <h1 class="font-display text-h2">{{ $t('page.entreprise.searches.title') }}</h1>

      <div class="flex min-h-0 flex-1 flex-col">
        <p v-if="pending" class="m-auto text-muted">{{ $t('search.loading') }}</p>

        <UAlert v-else-if="error" color="error" variant="subtle" :title="$t('search.error')" />

        <p v-else-if="0 === searches.length" class="m-auto max-w-readable text-center text-muted">
          {{ $t('search.empty') }}
        </p>

        <template v-else>
          <p v-if="actionErrorKey" class="mb-4 text-small text-alert" role="alert">
            {{ t(actionErrorKey) }}
          </p>

          <!-- MES recherches ouvrent la page, sans intertitre : « Mes recherches » posé sous un
               titre de page « Vos recherches enregistrées » dirait deux fois la même chose. C'est
               déjà ce que fait le pli des closes, qui n'annonce rien au-dessus de lui. -->
          <ul class="grid gap-block sm:grid-cols-2 xwide:grid-cols-3">
            <li v-for="search in groups.mine" :key="search.id">
              <SearchCard
                :search="search"
                :owner-name="ownerName(search)"
                v-bind="cardActions(search)"
                @intent="intend(search.id)"
              />
            </li>
          </ul>

          <!-- Les recherches d'un collègue, groupées mais DÉPLIÉES — cf. le commentaire du script.
               Un VRAI titre de section, et non la ligne de texte du pli : la section étant toujours
               ouverte, c'est le seul moyen qu'un lecteur d'écran l'annonce et qu'on y saute au
               clavier. Les cartes descendent donc au niveau 3, sinon elles seraient lues comme des
               voisines du titre et non comme son contenu. -->
          <section v-if="groups.shared.length" class="mt-block flex flex-col gap-block">
            <h2
              class="flex items-center gap-4 text-small font-normal text-muted before:h-px before:flex-1 before:bg-line before:content-[''] after:h-px after:flex-1 after:bg-line after:content-['']"
            >
              {{ t('search.sharedSection', { count: groups.shared.length }) }}
            </h2>

            <ul class="grid gap-block sm:grid-cols-2 xwide:grid-cols-3">
              <li v-for="search in groups.shared" :key="search.id">
                <SearchCard
                  :search="search"
                  :owner-name="ownerName(search)"
                  :heading-level="3"
                  v-bind="cardActions(search)"
                  @intent="intend(search.id)"
                />
              </li>
            </ul>
          </section>

          <!-- Le pli ne s'affiche pas du tout quand rien n'est clos : une ligne qui annonce zéro
               n'annonce rien. Il prend les closes de TOUT LE MONDE, les miennes comme celles d'un
               collègue : « plus rien à y faire » ne dépend pas de l'auteur. -->
          <details v-if="groups.closed.length" class="search-closed-fold group mt-block">
            <!-- Un chevron, sinon le pli se lit comme un simple filet : rien n'indiquerait qu'on
                 peut l'ouvrir. Il pivote à l'ouverture, ce qui dit dans quel sens on va. -->
            <summary
              class="flex cursor-pointer list-none items-center gap-4 text-small text-muted before:h-px before:flex-1 before:bg-line before:content-[''] after:h-px after:flex-1 after:bg-line after:content-['']"
            >
              <span class="inline-flex items-center gap-1.5">
                <UIcon
                  name="i-lucide-chevron-right"
                  class="h-4 w-4 transition-transform group-open:rotate-90 motion-reduce:transition-none"
                />
                {{ t('search.closure.fold', { count: groups.closed.length }) }}
              </span>
            </summary>

            <ul class="mt-block grid gap-block sm:grid-cols-2 xwide:grid-cols-3">
              <li v-for="search in groups.closed" :key="search.id">
                <SearchCard
                  :search="search"
                  :owner-name="ownerName(search)"
                  v-bind="cardActions(search)"
                  @intent="intend(search.id)"
                />
              </li>
            </ul>
          </details>
        </template>
      </div>
    </section>
  </div>
</template>
