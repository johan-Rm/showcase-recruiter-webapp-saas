<script setup lang="ts">
import type { TimelineItem } from '@nuxt/ui'

/**
 * Tiroir « profil complet » — surcouche MODALE commune aux deux écrans d'une recherche.
 *
 * IL MONTE PAR LE BAS et laisse une bande de page visible en haut : on est par-dessus l'écran de
 * travail, on ne l'a pas quitté. Le kit le rend déjà ainsi en direction `bottom` (marge haute,
 * poignée, glissement, `Échap`, page inerte, focus piégé) — d'où le composant du kit plutôt que la
 * fenêtre native, qui aurait demandé d'écrire la poignée et le geste tactile à la main.
 *
 * HAUTEUR FIXE et non hauteur du contenu : un tiroir qui changerait de taille selon la richesse du
 * profil rendrait le geste imprévisible d'un candidat à l'autre.
 *
 * SA LARGEUR EST CELLE DE LA FEUILLE, SON CONTENU EST BORNÉ. La feuille touche les deux bords —
 * c'est ce qui la distingue d'une fenêtre modale posée au milieu, et ce qui donne son sens à la
 * poignée de glissement. Ce qu'elle porte, en revanche, cesse de s'étaler : à pleine largeur, la
 * frise des expériences laissait six cents pixels de vide à sa droite sur un écran de 1920, et
 * l'œil traversait tout l'écran pour passer d'une colonne à l'autre. Le bornage vaut à partir du
 * seuil où les deux colonnes existent ; en dessous, une seule colonne n'a rien à resserrer.
 * L'EN-TÊTE SUIT, puisque le bornage porte sur le conteneur qui l'inclut : le titre et la croix
 * restent à l'aplomb du contenu qu'ils coiffent plutôt que de flotter aux extrémités.
 *
 * L'EN-TÊTE NE DÉFILE PAS : le débordement descend sur le corps, et le conteneur du kit — qui
 * défile par défaut — le laisse passer.
 *
 * DEUX COLONNES SUR GRAND ÉCRAN, une seule en dessous — le seuil est celui de la card de la
 * Visionneuse, pour que les deux surfaces du même écran changent de forme au même moment. À gauche
 * ce qu'on sait de la personne, à droite son parcours daté.
 *
 * ORDRE DE LECTURE : qui est-ce, va-t-il vraiment venir, son parcours, ce qui reste à dévoiler.
 * C'est celui de la card de la Visionneuse, et le recruteur ne change pas de logique en ouvrant.
 * Il tient sur téléphone, où tout s'empile — d'où les deux enveloppes `contents` du modèle et le
 * placement en grille explicite au-delà du seuil : le parcours saute alors dans la colonne de
 * droite sans suivre l'ordre du code.
 */
const { isOpen, profile, isLoading, hasFailed, close, retry } = useProfilePanel()

const { t, current } = useLocale()

/**
 * Une valeur d'énumération passe par le catalogue canonique du contrat, jamais par un libellé local.
 *
 * REPLI SUR LA VALEUR BRUTE quand le catalogue ne la connaît pas : les langues de la réplique
 * professionnelle ne sont pas toutes normalisées — on y trouve des variantes que l'énumération ne
 * porte pas —, et afficher `search.ai.proficiency.PROFESSIONAL` à un recruteur serait pire que
 * d'afficher le mot lui-même.
 */
const enumLabel = (catalogue: string, value: string | null): string | null => {
  if (!value) return null

  const key = `${catalogue}.${value}`
  const label = t(key)

  return label === key ? value.toLowerCase().replaceAll('_', ' ') : label
}

/**
 * Le titre du tiroir : le poste du moment, l'accroche à défaut, le nom générique en dernier recours.
 */
const title = computed(
  () =>
    profile.value?.identity.currentTitle ??
    profile.value?.identity.headline ??
    t('cockpit.profilePanel.title'),
)

/**
 * L'accroche NE SE RÉPÈTE PAS sous le titre qu'elle vient de servir.
 *
 * Les deux sont distinctes sur les profils importés, mais confondues sur les profils de
 * démonstration, où le même texte s'affichait deux fois à trois lignes d'intervalle.
 */
const tagline = computed(() => {
  const headline = profile.value?.identity.headline

  return headline && headline !== title.value ? headline : null
})

const place = computed(() => {
  const identity = profile.value?.identity
  if (!identity) return null

  return [identity.city, identity.country].filter(Boolean).join(' · ') || null
})

/**
 * Les périodes de présence se lisent en une ligne chacune : « Casablanca, du 12 au 26 septembre ».
 *
 * La langue est celle de L'APPLICATION, pas celle du navigateur : sans elle, un poste configuré en
 * anglais affichait « du September 12 » au milieu d'une phrase française.
 */
const formatPeriod = (start: string, end: string, city: string | null): string => {
  const format = (value: string) =>
    new Date(value).toLocaleDateString(current.value, { day: 'numeric', month: 'long' })

  return city
    ? t('search.profile.presenceIn', { city, start: format(start), end: format(end) })
    : t('search.profile.presence', { start: format(start), end: format(end) })
}

const year = (value: string | null): string => (value ? value.slice(0, 4) : '—')

/**
 * LA DATE OUVRE CHAQUE ÉLÉMENT DE FRISE : c'est elle qu'on balaie pour situer un parcours, le titre
 * du poste ne se lit qu'ensuite. Un poste sans date de fin est en cours, et le dit avec des mots.
 *
 * Rien à afficher quand les deux dates manquent — la réplique professionnelle en produit —, sinon
 * la frise ouvrirait sur un tiret qui ne situe rien.
 */
const period = (start: string | null, end: string | null): string | undefined => {
  if (!start && !end) return undefined

  return `${year(start)} – ${end ? year(end) : t('search.profile.ongoing')}`
}

const positions = computed<TimelineItem[]>(() =>
  (profile.value?.career.workExperiences ?? []).map((job) => ({
    date: period(job.startDate, job.endDate),
    title: job.title ?? t('search.profile.untitled'),
    description: job.companyName ?? undefined,
    icon: 'i-lucide-briefcase',
  })),
)

const diplomas = computed<TimelineItem[]>(() =>
  (profile.value?.career.educations ?? []).map((education) => ({
    date: education.endDate ? year(education.endDate) : undefined,
    title: education.title ?? t('search.profile.untitled'),
    description: education.school ?? undefined,
    icon: 'i-lucide-graduation-cap',
  })),
)

/**
 * LES MOTS DU PROFIL, chacun sous son libellé — un métier exercé, un secteur et une compétence ne se
 * lisent pas de la même façon, et ce sont trois critères de recherche distincts. Fondus en une seule
 * rangée, ils feraient perdre ce que la personne a fait, où elle l'a fait, et ce qu'elle sait faire.
 *
 * Une rangée sans mot ne s'affiche pas : 411 profils du vivier n'ont aucun métier, 851 aucun secteur.
 */
const wordRows = computed(() =>
  [
    { key: 'jobs', words: profile.value?.expertise.jobs ?? [] },
    {
      // Les SECTEURS arrivent en codes d'énumération et non en libellés — `CONSEIL_AUDIT` s'affichait
      // tel quel. Les métiers, eux, sont déjà des intitulés lisibles.
      key: 'sectors',
      words: (profile.value?.expertise.sectors ?? []).map(
        (code) => enumLabel('search.sectorExperience', code) ?? code,
      ),
    },
    { key: 'skills', words: profile.value?.expertise.skills ?? [] },
  ].filter((row) => row.words.length),
)

/**
 * Le focus ENTRE dans le tiroir à l'ouverture, et il faut le lui dire.
 *
 * Le composant rend bien le reste de la page inerte, mais laisse le focus sur le bouton qui vient
 * d'ouvrir — il désactive la mise au point automatique pour ne pas gêner le glissement au doigt.
 * Mesuré : sans ce qui suit, un utilisateur au clavier ouvre le tiroir et sa touche de tabulation
 * continue de parcourir la page derrière, hors d'un contenu qu'il ne peut plus atteindre.
 *
 * On vise le contenu lui-même, pas son premier bouton : le lecteur d'écran annonce alors le nom du
 * dialogue avant son contenu, au lieu de démarrer sur « fermer ».
 */
const panel = useTemplateRef<HTMLElement>('panel')

watch(isOpen, async (opened) => {
  if (!opened) return

  await nextTick()
  panel.value?.focus()
})
</script>

<template>
  <UDrawer
    v-model:open="isOpen"
    class="profile-panel"
    :title="$t('cockpit.profilePanel.title')"
    :description="$t('cockpit.profilePanel.title')"
    :ui="{
      description: 'sr-only',
      content: 'h-[calc(100dvh-6rem)]',
      container: 'overflow-hidden gap-block lg:mx-auto lg:max-w-3/4',
      body: 'min-h-0 overflow-y-auto',
    }"
    @update:open="(opened: boolean) => !opened && close()"
  >
    <!-- LES SLOTS `title` ET `actions`, JAMAIS `header` : c'est l'en-tête du kit qui rend le titre
         du dialogue et le rattache par `aria-labelledby`. L'écraser entier retire ce lien, et le
         tiroir s'annonce alors sans nom — mesuré. -->
    <template #title>
      <span class="block min-w-0 truncate text-h3">{{ title }}</span>
    </template>

    <template #actions>
      <!-- Balise native plutôt que rôle ARIA posé à la main : la région vivante est alors annoncée
           par tous les lecteurs d'écran, y compris ceux qui ignorent le rôle. -->
      <output v-if="isLoading" class="text-small text-muted">
        {{ $t('search.profile.loading') }}
      </output>
      <UButton
        icon="i-lucide-x"
        color="neutral"
        variant="ghost"
        :aria-label="$t('cockpit.profilePanel.close')"
        @click="close()"
      />
    </template>

    <template #body>
      <!-- `tabindex="-1"` : la cible du focus d'ouverture. Il ne rend pas le bloc atteignable à la
           tabulation, il le rend focalisable par programme — c'est exactement ce qu'il faut ici. -->
      <!-- LA GRILLE NE S'OUVRE QUE S'IL Y A DE QUOI REMPLIR LA DROITE : sans parcours daté, deux
           colonnes laisseraient un demi-tiroir vide, pire que la colonne unique qu'on quitte. -->
      <div
        v-if="profile"
        ref="panel"
        tabindex="-1"
        class="grid gap-block pb-block outline-none"
        :class="
          profile.career.isEmpty
            ? undefined
            : 'lg:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] lg:grid-rows-[auto_1fr] lg:items-start'
        "
      >
        <!-- `contents` SOUS LE SEUIL : l'enveloppe disparaît et ses sections retombent dans le flux
             du tiroir, ce qui garde l'ordre de décision sur téléphone. Au-delà, elle redevient une
             boîte et se range dans la première colonne. -->
        <div class="contents lg:col-start-1 lg:row-start-1 lg:flex lg:flex-col lg:gap-block">
          <!-- QUI — l'identité, avec l'employeur et le profil public, tous deux gratuits. -->
          <section class="flex flex-col gap-element">
            <div class="flex gap-block">
              <!-- Le portrait du kit et non une balise nue : il porte déjà le repli sur les
                   initiales quand la photo est morte, ce qui arrive — le stockage distant peut
                   l'annoncer alors que le fichier a disparu. La photo échappe au dévoilement, le
                   masquage portant sur les moyens de contact. -->
              <UiAvatar
                :given-name="profile.identity.givenName ?? ''"
                :family-name="profile.identity.familyName ?? ''"
                :photo-url="profile.identity.photoUrl"
                :size="80"
                square
              />

              <div class="flex min-w-0 flex-1 flex-col gap-element">
                <div class="flex flex-wrap items-baseline gap-element">
                  <p class="text-h4">
                    {{
                      [profile.identity.givenName, profile.identity.familyName]
                        .filter(Boolean)
                        .join(' ')
                    }}
                  </p>
                  <p v-if="place" class="text-small text-muted">{{ place }}</p>
                </div>

                <p v-if="profile.identity.company" class="text-small">
                  {{ profile.identity.company }}
                </p>

                <dl class="flex flex-wrap gap-element text-small">
                  <div v-if="null !== profile.identity.experienceYears" class="flex gap-element">
                    <dt class="text-muted">{{ $t('search.profile.facts.experience') }}</dt>
                    <dd>{{ $t('search.profile.years', profile.identity.experienceYears) }}</dd>
                  </div>
                  <div v-if="profile.identity.educationLevel" class="flex gap-element">
                    <dt class="text-muted">{{ $t('search.profile.facts.education') }}</dt>
                    <dd>{{ enumLabel('search.education', profile.identity.educationLevel) }}</dd>
                  </div>
                </dl>

                <a
                  v-if="profile.identity.linkedinUrl"
                  :href="profile.identity.linkedinUrl"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-small text-accent underline"
                >
                  {{ $t('search.profile.linkedin') }}
                </a>

                <!-- CE QUE LE PROFIL EST, dans le vocabulaire des deux autres surfaces qui le
                     montrent — le tiroir était le seul à n'en rien dire. Près du nom, là où le
                     regard commence : « rencontré par showcase-recruiter-webapp-saas » et « au Maroc en ce moment » sont
                     les deux signaux qui changent une décision. -->
                <ul v-if="profile.states.length" class="flex flex-wrap gap-element">
                  <li v-for="state in profile.states" :key="state.key">
                    <UiBadge :tint="state.tint" :icon="state.icon">
                      {{ $t(state.labelKey) }}
                    </UiBadge>
                  </li>
                </ul>
              </div>
            </div>

            <!-- DANS SES MOTS. L'accroche est ce que 99,7 % des profils portent, le résumé ce qu'un
                 sur cent porte : c'est elle qui remplit cette colonne, lui qui la complète. -->
            <p v-if="tagline" class="max-w-readable text-small">{{ tagline }}</p>
            <p v-if="profile.identity.summary" class="max-w-readable text-small text-muted">
              {{ profile.identity.summary }}
            </p>
          </section>

          <!-- VA-T-IL VENIR — les différenciants, ceux qu'aucun profil importé ne porte. -->
          <section v-if="!profile.morocco.isEmpty" class="flex flex-col gap-element">
            <h2 class="text-label text-muted">{{ $t('search.profile.section.morocco') }}</h2>

            <dl class="flex flex-col gap-element text-small">
              <div v-if="profile.morocco.returnHorizon" class="flex gap-element">
                <dt class="text-muted">{{ $t('search.criterion.facet.returnHorizon') }}</dt>
                <dd>{{ enumLabel('search.horizon', profile.morocco.returnHorizon) }}</dd>
              </div>
              <div v-if="profile.morocco.availability" class="flex gap-element">
                <dt class="text-muted">{{ $t('search.criterion.facet.availability') }}</dt>
                <dd>{{ enumLabel('search.availability', profile.morocco.availability) }}</dd>
              </div>
              <div v-if="profile.morocco.desiredFunction" class="flex gap-element">
                <dt class="text-muted">{{ $t('search.criterion.facet.function') }}</dt>
                <dd>{{ profile.morocco.desiredFunction }}</dd>
              </div>
              <div v-if="profile.morocco.desiredSector" class="flex gap-element">
                <dt class="text-muted">{{ $t('search.criterion.facet.sector') }}</dt>
                <dd>{{ profile.morocco.desiredSector }}</dd>
              </div>
              <div v-if="profile.morocco.hasPiedATerre" class="flex gap-element">
                <dt class="text-muted">{{ $t('search.criterion.facet.hasPiedATerre') }}</dt>
                <dd>{{ profile.morocco.piedATerreCity ?? $t('search.criterion.value.yes') }}</dd>
              </div>
              <div v-if="profile.morocco.geoMobility" class="flex gap-element">
                <dt class="text-muted">{{ $t('search.criterion.facet.geoMobility') }}</dt>
                <dd>{{ $t('search.criterion.value.yes') }}</dd>
              </div>
            </dl>

            <ul
              v-if="profile.morocco.presencePeriods.length"
              class="flex flex-col gap-element text-small"
            >
              <li v-for="stay in profile.morocco.presencePeriods" :key="stay.startDate">
                {{ formatPeriod(stay.startDate, stay.endDate, stay.city) }}
              </li>
            </ul>

            <p v-if="profile.morocco.returnProject" class="text-small italic text-muted">
              {{ profile.morocco.returnProject }}
            </p>
          </section>
        </div>

        <!-- SON PARCOURS — la colonne de droite, et elle ne porte QUE du daté.
             ELLE S'ÉTEND SUR LES DEUX RANGÉES de gauche, sans quoi la grille la couperait au niveau
             du bloc suivant. C'est ce qui impose `auto 1fr` comme hauteurs de rangées : sans elles,
             la grille répartit la hauteur de la frise entre les deux, et un trou de quatre cents
             pixels s'ouvre au milieu de la colonne de gauche — mesuré à la capture. -->
        <section
          v-if="!profile.career.isEmpty"
          class="flex flex-col gap-block lg:col-start-2 lg:row-span-2 lg:row-start-1"
        >
          <h2 class="text-label text-muted">{{ $t('search.profile.section.career') }}</h2>

          <div v-if="positions.length" class="flex flex-col gap-element">
            <h3 class="text-small text-muted">{{ $t('search.profile.experiences') }}</h3>
            <UTimeline :items="positions" color="neutral" size="xs" />
          </div>

          <div v-if="diplomas.length" class="flex flex-col gap-element">
            <h3 class="text-small text-muted">{{ $t('search.profile.educations') }}</h3>
            <UTimeline :items="diplomas" color="neutral" size="xs" />
          </div>
        </section>

        <div class="contents lg:col-start-1 lg:row-start-2 lg:flex lg:flex-col lg:gap-block">
          <!-- CE QU'IL SAIT FAIRE — sans date, donc hors de la frise. Les mots portent le dièse des
               compétences de la card : une même famille visuelle pour les mots d'un profil, à
               distinguer des badges d'état juste au-dessus.
               `bdi` et non `span` : l'élément isole le sens de lecture. Dans une page en arabe, un
               dièse latin collé à un mot latin saute à l'autre bout du mot, et les noms de métiers
               importés ne sont pas traduits. -->
          <section v-if="!profile.expertise.isEmpty" class="flex flex-col gap-element">
            <div v-for="row in wordRows" :key="row.key" class="flex flex-col gap-1">
              <h2 class="text-label text-muted">{{ $t(`search.profile.${row.key}`) }}</h2>
              <ul class="flex flex-wrap gap-x-element gap-y-1 text-small">
                <li v-for="word in row.words" :key="word">
                  <bdi class="text-muted">#{{ word }}</bdi>
                </li>
              </ul>
            </div>

            <div v-if="profile.expertise.languages.length" class="flex flex-col gap-1">
              <h2 class="text-label text-muted">{{ $t('search.profile.languages') }}</h2>
              <ul class="flex flex-wrap gap-element text-small">
                <li
                  v-for="language in profile.expertise.languages"
                  :key="String(language.language)"
                >
                  {{ language.language }}
                  <span v-if="language.proficiency" class="text-muted">
                    · {{ enumLabel('search.ai.proficiency', language.proficiency) }}
                  </span>
                </li>
              </ul>
            </div>
          </section>

          <!-- À DÉVOILER — chaque ligne garde sa place, verrouillée ou non : c'est ce qui évite de
               payer pour découvrir qu'il n'y avait rien. -->
          <section class="flex flex-col gap-element">
            <h2 class="text-label text-muted">{{ $t('search.profile.section.locked') }}</h2>

            <dl class="flex flex-col gap-element text-small">
              <div
                v-for="entry in [
                  { key: 'email', value: profile.locked.email },
                  { key: 'phone', value: profile.locked.phone },
                  { key: 'resume', value: profile.locked.resume },
                  { key: 'report', value: profile.locked.report },
                ]"
                :key="entry.key"
                class="flex gap-element"
              >
                <dt class="text-muted">{{ $t(`search.profile.locked.${entry.key}`) }}</dt>
                <dd v-if="'revealed' === entry.value.state">
                  {{
                    'string' === typeof entry.value.value
                      ? entry.value.value
                      : $t('search.profile.locked.available')
                  }}
                </dd>
                <dd v-else-if="'locked' === entry.value.state" class="text-muted">
                  {{ $t('search.profile.locked.hidden') }}
                </dd>
                <dd v-else class="text-muted">{{ $t('search.profile.locked.none') }}</dd>
              </div>
            </dl>
          </section>

          <!-- L'ÉCHEC NE VIDE RIEN : tout ce qui précède vient de la vignette et reste lisible. -->
          <p
            v-if="hasFailed"
            class="flex items-center gap-element text-small text-alert"
            role="alert"
          >
            {{ $t('search.profile.loadFailed') }}
            <UButton size="xs" variant="subtle" @click="retry()">
              {{ $t('search.viewer.stack.retry') }}
            </UButton>
          </p>
        </div>
      </div>
    </template>
  </UDrawer>
</template>
