<script setup lang="ts">
// Une card = un profil = un plein écran de la pile de parcours.
//
// Ce n'est PAS une fiche : le profil complet est un écran à part. C'est un RÉSUMÉ DE DÉCISION, qui
// porte ce qui permet de trancher garder ou passer, et rien de plus. Sans onglets — ils existaient
// pour ranger un profil entier dans un écran, il n'y a plus de profil entier à ranger.
//
// Elle répond à deux questions, dans cet ordre : est-ce le bon professionnel, va-t-il vraiment
// venir. Les compétences ferment la marche. POURQUOI ce profil est là — ce que la recherche a
// trouvé, et ce qui lui manque — appartient à la colonne : une absence n'a nulle part où être
// surlignée dans une fiche.
//
// Le geste de la card : RETENIR. Elle l'émet, elle ne l'exécute pas — elle reste passive, la page
// décide quoi en faire.
import { roleOf } from '#services/search/candidate'
import { isRetained, statesOf } from '#services/search/states'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { RevealType } from '#shared/types/reveal'

type Props = {
  candidate: CandidateCard
  /** Faux sur la recherche d'un collègue : on la lit, on n'y retient rien. */
  editable?: boolean
}

const { candidate, editable = true } = defineProps<Props>()

const emit = defineEmits<{
  toggleFavorite: [candidate: CandidateCard]
  dismiss: [candidate: CandidateCard]
  seen: [candidate: CandidateCard]
  reveal: [candidate: CandidateCard, type: RevealType]
}>()

/**
 * Les DEUX DOCUMENTS achetables — les coordonnées ne sont pas ici.
 *
 * Trois achats distincts, un crédit chacun. Mais les coordonnées ont leur ligne, juste au-dessus,
 * CONTRE la valeur qu'elles débloquent : un achat et son résultat au même endroit. Un bouton en bas
 * de card, la valeur nulle part, et l'on paie un crédit pour voir un badge.
 *
 * La préséance se dit toujours par la PLACE — le contact devant, les documents ensuite — et jamais
 * par la couleur : un aplat accent ici retirerait son sens à celui qui clôt la séance, dans la
 * colonne de droite.
 */
const DOCUMENT_ACCESSES: readonly { type: RevealType; icon: string }[] = [
  { type: 'RESUME', icon: 'i-lucide-file-text' },
  { type: 'INTERVIEW_REPORT', icon: 'i-lucide-notebook-pen' },
]

const { cost, hasAccess, affords } = useReveal()

/**
 * VU = la carte est RESTÉE à l'écran, pas simplement traversée.
 *
 * Dans une pile qui défile, tout ce qu'on croise est techniquement vu : un défilement rapide
 * marquerait le jeu entier en quelques secondes, et l'information mentirait — elle sert justement à
 * savoir ce qu'il reste à examiner.
 *
 * Le seuil est arbitraire et assumé : il correspond à ce que le mot veut dire pour l'utilisateur.
 * Il vit ICI, dans ce qui observe la carte, et pas dans l'API — le serveur enregistre un verdict, il
 * ne l'invente pas.
 */
const DWELL_MS = 1200

const root = ref<HTMLElement | null>(null)

const { start: startDwell, stop: cancelDwell } = useTimeoutFn(
  () => emit('seen', candidate),
  DWELL_MS,
  { immediate: false },
)

// Une carte « à l'écran » est celle qui l'occupe vraiment : le seuil de moitié écarte les voisines
// qu'un défilement laisse dépasser.
useIntersectionObserver(root, ([entry]) => (entry?.isIntersecting ? startDwell() : cancelDwell()), {
  threshold: 0.5,
})

// Au-delà, la liste cesse de se balayer d'un regard ; la liste complète vit sur la fiche.
const SKILLS_SHOWN = 8

// LES ÉTATS, produits par la couche pure et non recomposés ici : la Table montre le même profil et
// doit le décrire avec les mêmes mots. La rangée disparaît quand la liste est vide — un badge nomme
// un attribut ACQUIS, jamais son absence, et le vide est déjà le signal.
const states = computed(() => statesOf(candidate))

// Le titre du bandeau : le poste, l'accroche en repli — et le repli se signale.
// PAS `role` : la pile passe un attribut `role="listitem"` au composant, et deux noms identiques
// dans un même template sont une ambiguïté qu'on ne veut pas laisser derrière soi.
const roleLine = computed(() => roleOf(candidate))

// Masqué, le nom se réduit à des initiales — c'est le SERVEUR qui les réduit, la card n'a rien à
// décider ici. Les deux morceaux se joignent tels qu'ils arrivent : un profil sans nom de famille
// n'est pas un profil sans nom.
const fullName = computed(
  () => [candidate.givenName, candidate.familyName].filter(Boolean).join(' ') || null,
)
const location = computed(() => [candidate.city, candidate.country].filter(Boolean).join(', '))

const { t } = useLocale()

/** La situation en une ligne, sous le nom : ce qui cadre le profil sans encore le décrire. */
const situation = computed(() =>
  [
    candidate.experienceYears > 0
      ? t('search.profile.years', { count: candidate.experienceYears })
      : null,
    location.value || null,
  ]
    .filter(Boolean)
    .join(' · '),
)

/**
 * Ce qu'on affiche d'une coordonnée : sa VALEUR une fois payée, son EXISTENCE avant.
 *
 * Dire « Téléphone indisponible » avant l'achat est ce qui évite de brûler un crédit sur un profil
 * injoignable — `hasEmail` et `hasPhone` descendent exactement pour ça, et la card les ignorait.
 *
 * En toutes lettres, là où la Table floute un placeholder : une colonne de tableau doit tenir dans
 * sa largeur sur vingt lignes, une card occupe un plein écran et a la place d'une phrase. Deux
 * vocabulaires du masquage cohabitent donc dans le produit, le temps qu'on tranche.
 */
const contactLabel = (value: string | null, present: boolean, kind: string): string =>
  value ?? t(`search.contact.${kind}.${present ? 'available' : 'none'}`)

const contacts = computed(() => [
  {
    key: 'email',
    type: 'EMAIL' as RevealType,
    icon: 'i-lucide-at-sign',
    label: contactLabel(candidate.contactEmail, candidate.hasEmail, 'email'),
    // Truthiness assumée : masquée, la coordonnée arrive `null` — mais elle peut aussi être
    // absente de la charge utile, et une comparaison stricte à `null` prendrait alors l'absence
    // pour une valeur.
    known: Boolean(candidate.contactEmail),
    // ACHETABLE : le canal n'est pas payé, et il y a quelque chose à acheter. Un profil sans
    // e-mail connu n'offre aucun geste — laisser cliquer, c'est laisser payer pour rien, et
    // c'est le défaut que cette card vient de corriger.
    buyable: !hasAccess(candidate, 'EMAIL') && candidate.hasEmail,
  },
  {
    key: 'phone',
    type: 'PHONE' as RevealType,
    icon: 'i-lucide-phone',
    label: contactLabel(candidate.phone, candidate.hasPhone, 'phone'),
    known: Boolean(candidate.phone),
    buyable: !hasAccess(candidate, 'PHONE') && candidate.hasPhone,
  },
])

/**
 * LES QUATRE FAITS de la situation, en grille.
 *
 * Ils remplacent la ligne en prose de l'en-tête : une phrase se lit, une grille se balaie — et sur
 * un écran où l'on tranche cent profils à la suite, ce n'est pas la même fatigue.
 *
 * Ce ne sont PAS les quatre du mockup (poste actuel, entreprise, depuis, secteur) : trois d'entre
 * eux n'existent pas dans le contrat. On reprend le dispositif, pas son contenu.
 *
 * TOUJOURS QUATRE, tiret compris. Une grille dont les cases apparaissent et disparaissent change de
 * largeur d'une carte à l'autre — ce n'est plus une grille, et il n'y a plus rien à balayer. C'est
 * ce qui fait que le niveau d'études ne dépend pas de la recherche qui l'interroge : il occupe sa
 * case, vide s'il le faut.
 */
const facts = computed(() => [
  {
    key: 'experience',
    value:
      candidate.experienceYears > 0
        ? t('search.profile.years', { count: candidate.experienceYears })
        : null,
  },
  { key: 'location', value: location.value || null },
  {
    key: 'availability',
    value: candidate.availability ? t(`search.availability.${candidate.availability}`) : null,
  },
  {
    key: 'education',
    value: candidate.educationLevel ? t(`search.education.${candidate.educationLevel}`) : null,
  },
])

/**
 * Les compétences trouvées par un mot-clé, pour les distinguer des autres.
 *
 * C'est le SEUL usage que la card fait des correspondances : l'énoncé de ce qui est trouvé et de ce
 * qui manque appartient à la colonne, qui peut le dire en entier. Ici, la correspondance se montre
 * là où elle est — dans la compétence elle-même.
 *
 * On compare sur `matchedOn`, qui porte ce que le SERVEUR a reconnu — pas sur le terme cherché, qui
 * peut n'en être qu'un fragment (« postgres » trouve « PostgreSQL »).
 */
const matchedSkills = computed(
  () =>
    new Set(
      (candidate.matches ?? [])
        .filter((match) => match.matched && 'q' === match.facet && undefined !== match.matchedOn)
        .map((match) => match.matchedOn),
    ),
)

const skills = computed(() => candidate.skills.slice(0, SKILLS_SHOWN))

/** Ce que le candidat annonce sur son retour — le différenciant, quand il dit quelque chose. */
const hasReturnPlan = computed(
  () =>
    Boolean(candidate.returnHorizon) ||
    candidate.hasPiedATerre ||
    candidate.geoMobility ||
    Boolean(candidate.returnProject),
)
</script>

<template>
  <!-- `snap-start` + `min-h-full` : la molette déplace la pile de card en card, jamais dans une card.

       RETENU se voit de LOIN, par la bordure : sur un écran où l'on tranche cent profils à la suite,
       ce qu'on a gardé doit se repérer sans lire. Une bordure INTÉRIEURE (`inset`) et non un contour
       : posée en dehors, elle décalerait le contenu d'un pixel à chaque bascule de l'étoile, et la
       card sauterait sous le curseur. -->
  <article
    ref="root"
    class="search-v1-profile-card flex min-h-full snap-start flex-col gap-block rounded-lg bg-surface p-block"
    :class="isRetained(candidate) ? 'inset-ring-2 inset-ring-accent' : ''"
  >
    <!-- ① LE BANDEAU : le poste, et les deux gestes.
         LE POSTE EST LE TITRE, dévoilé comme masqué. Obtenu par la MISE EN PAGE et non par une
         condition sur les données : le serveur renvoie une initiale là où une telle condition
         attendrait `null`, ce qui la rend inopérante. Le poste est un critère, le nom une
         étiquette ; l'un se lit pour décider, l'autre pour s'adresser. -->
    <div class="flex flex-wrap items-start justify-between gap-element">
      <!-- LE VRAI INTITULÉ : `currentTitle` et NON `headline`. L'accroche que le candidat s'écrit
           sur son réseau monte à 752 caractères sur le vivier ; elle s'arrogerait tout le bandeau et
           repousserait les gestes sous elle.
           DEUX LIGNES, et elles ne mordent pas : le poste le plus long du vivier
           (100 caractères) y entre en entier. Sur la fiche on LIT — rien n'y justifie de couper un
           intitulé, contrairement à la Table qui compare et paie chaque caractère en largeur. -->
      <h2
        class="line-clamp-2 min-w-0 flex-1 font-display text-h2"
        :class="roleLine.isFallback ? 'italic text-muted' : undefined"
      >
        {{ roleLine.text ?? $t('search.profile.untitled') }}
      </h2>

      <!-- LES DEUX VERDICTS, ancrés à droite à toute largeur d'écran : c'est le titre qui passe à
           la ligne, jamais eux. Un geste qui change de place selon la largeur est un geste qu'il
           faut chercher, et c'est le plus répété de l'écran. -->
      <div v-if="editable" class="flex flex-none items-center gap-element">
        <!-- UNE SEULE ÉTOILE, deux couleurs. Le contraire d'une étoile n'est pas une étoile barrée,
             c'est une étoile éteinte : un signe nomme un attribut ACQUIS, jamais son absence. -->
        <UButton
          :variant="candidate.isFavorite ? 'soft' : 'outline'"
          :color="candidate.isFavorite ? 'primary' : 'neutral'"
          icon="i-lucide-star"
          @click="emit('toggleFavorite', candidate)"
        >
          {{ candidate.isFavorite ? $t('search.action.retained') : $t('search.action.retain') }}
        </UButton>

        <!-- ÉCARTER sort le profil de la pile : c'est ce qui rend un tri de plusieurs centaines de
             profils fini plutôt qu'infini. Réversible depuis la colonne, sans quoi on n'oserait
             pas s'en servir. -->
        <UButton
          icon="i-lucide-eye-off"
          color="neutral"
          variant="ghost"
          :aria-label="$t('search.action.dismiss')"
          @click="emit('dismiss', candidate)"
        />
      </div>
    </div>

    <!-- ② LES DEUX COLONNES. À gauche COMMENT LE JOINDRE, à droite QUI C'EST.
         Sous `lg` elles s'empilent : la carte de visite d'abord, la substance ensuite. -->
    <div class="grid flex-1 grid-cols-1 gap-block lg:grid-cols-[minmax(240px,3fr)_minmax(0,9fr)]">
      <!-- LA CARTE DE VISITE : la photo, les moyens de joindre, et ce qu'ils coûtent — tout ce qui
           sert à ATTEINDRE quelqu'un, au même endroit et dans l'ordre où l'on s'en sert.
           C'est aussi ce qui met l'achat CONTRE la valeur qu'il débloque : tant que le bouton était
           en bas de card et la valeur nulle part, on payait un crédit pour voir un badge. -->
      <div class="flex flex-col gap-element">
        <!-- Carré à initiales quand la photo manque, sur un profil sur sept — et quand elle est
             morte, ce qui arrive : le storage Jarvi peut l'annoncer alors que le fichier a disparu.
             La photo échappe au dévoilement, le masquage porte sur les moyens de contact. -->
        <UiAvatar
          :given-name="candidate.givenName"
          :family-name="candidate.familyName ?? ''"
          :photo-url="candidate.photoUrl"
          :size="120"
          square
        />

        <!-- LA COORDONNÉE EST LE BOUTON. Cliquer sur « E-mail disponible » achète CE canal, et la
             ligne cliquée devient l'adresse : l'achat se fait là où sa valeur apparaîtra.
             Une coordonnée inexistante n'est pas cliquable — il n'y a rien à acheter, et laisser
             payer pour le découvrir serait le défaut qu'on vient de corriger. -->
        <dl class="flex flex-col gap-1 text-small">
          <div v-for="contact in contacts" :key="contact.key">
            <dt class="sr-only">{{ $t(`search.profile.${contact.key}`) }}</dt>
            <dd>
              <UButton
                v-if="editable && contact.buyable"
                :icon="contact.icon"
                color="neutral"
                variant="outline"
                size="sm"
                block
                class="justify-start"
                :disabled="!affords(cost)"
                :title="affords(cost) ? undefined : $t('search.reveal.insufficient')"
                @click="emit('reveal', candidate, contact.type)"
              >
                <span class="truncate">{{ contact.label }}</span>
                <span class="ms-auto tabular-nums text-muted">{{
                  $t('search.reveal.cost', { count: cost })
                }}</span>
              </UButton>

              <span v-else class="flex items-center gap-1">
                <UIcon :name="contact.icon" class="shrink-0 text-muted" />
                <span class="truncate" :class="contact.known ? undefined : 'italic text-muted'">
                  {{ contact.label }}
                </span>
              </span>
            </dd>
          </div>
        </dl>

        <div v-if="editable" class="flex flex-col items-start gap-element">
          <template v-for="access in DOCUMENT_ACCESSES" :key="access.type">
            <!-- Déjà acquis : ce n'est plus une action mais un ÉTAT. Un bouton resté actif
                 laisserait croire à une seconde dépense, alors que l'API ne refacture pas. -->
            <UBadge
              v-if="hasAccess(candidate, access.type)"
              color="primary"
              variant="soft"
              :icon="access.icon"
            >
              {{ $t(`search.reveal.granted.${access.type}`) }}
            </UBadge>

            <UButton
              v-else
              :icon="access.icon"
              color="neutral"
              variant="outline"
              :disabled="!affords(cost)"
              :title="affords(cost) ? undefined : $t('search.reveal.insufficient')"
              @click="emit('reveal', candidate, access.type)"
            >
              {{ $t(`search.reveal.action.${access.type}`) }}
              <span class="tabular-nums text-muted">{{
                $t('search.reveal.cost', { count: cost })
              }}</span>
            </UButton>
          </template>
        </div>
      </div>
      <!-- LA SUBSTANCE : qui c'est, pourquoi il est là, et s'il va vraiment venir. -->
      <div class="flex flex-col gap-block">
        <!-- Le nom ouvre la colonne, il n'ouvre plus la card : masqué il ne dit rien, dévoilé il
             ne dit qu'à qui l'on s'adresse. Le poste, lui, se lit pour décider. -->
        <div class="flex flex-col">
          <p class="max-w-readable font-display text-h3">
            {{ fullName ?? $t('search.profile.untitled') }}
          </p>
          <p v-if="situation" class="text-muted">{{ situation }}</p>
        </div>

        <!-- LES QUATRE FAITS, en grille d'encadrés. Toujours quatre, tiret compris : une grille dont
         les cases apparaissent et disparaissent change de largeur d'une carte à l'autre, et il n'y
         a plus rien à balayer. Une RANGÉE, jamais deux — la card tient dans un plein écran. -->
        <dl class="grid grid-cols-2 gap-element xl:grid-cols-4">
          <div
            v-for="fact in facts"
            :key="fact.key"
            class="rounded-lg border border-line p-element"
          >
            <dt class="truncate text-label text-muted">
              {{ $t(`search.profile.facts.${fact.key}`) }}
            </dt>
            <dd class="mt-0.5 text-small font-semibold">
              <template v-if="fact.value">{{ fact.value }}</template>
              <template v-else>
                <span class="text-muted" aria-hidden="true">—</span>
                <span class="sr-only">{{ $t('search.profile.unknown') }}</span>
              </template>
            </dd>
          </div>
        </dl>

        <!-- ② VA-T-IL VRAIMENT VENIR ? Le différenciant du produit : ce que showcase-recruiter-webapp-saas sait et qu'aucun
         réseau professionnel ne dit. -->
        <section v-if="hasReturnPlan" class="flex flex-col gap-element">
          <h3 class="text-label text-muted">{{ $t('search.return.title') }}</h3>

          <dl class="flex flex-wrap gap-element text-small">
            <div v-if="candidate.returnHorizon" class="flex items-center gap-1">
              <dt class="text-muted">{{ $t('search.return.horizon') }}</dt>
              <dd>{{ $t(`search.horizon.${candidate.returnHorizon}`) }}</dd>
            </div>
            <div v-if="candidate.hasPiedATerre" class="flex items-center gap-1">
              <dt class="text-muted">{{ $t('search.return.piedATerre') }}</dt>
              <dd>{{ candidate.piedATerreCity ?? $t('search.return.yes') }}</dd>
            </div>
            <div v-if="candidate.geoMobility" class="flex items-center gap-1">
              <dt class="text-muted">{{ $t('search.return.mobility') }}</dt>
              <dd>{{ $t('search.return.yes') }}</dd>
            </div>
          </dl>

          <!-- DANS SES MOTS : c'est ce qui sépare « il cherche un poste » de « il veut rentrer ». -->
          <blockquote
            v-if="candidate.returnProject"
            class="max-w-readable border-s-2 border-accent ps-element text-small"
          >
            {{ candidate.returnProject }}
          </blockquote>
        </section>

        <!-- ③ Les compétences, en MOTS-DIÈSE, celles qui répondent à un mot-clé mises en avant.
         Elles cessent d'être des pastilles pour cesser d'être confondues : un badge d'état, un
         critère satisfait et une compétence étaient le même objet visuel à trois endroits de la
         card. Trois familles, trois dessins.

         `bdi` et non `span` : l'élément isole le sens de lecture, ce dont on a besoin ici. Dans une
         page en arabe, un dièse latin collé à un mot latin saute à l'autre bout du mot — et les noms
         de technologies ne se traduisent pas, donc le cas est systématique, pas marginal. -->
        <ul v-if="skills.length" class="flex flex-wrap gap-x-element gap-y-1 text-small">
          <li v-for="skill in skills" :key="skill">
            <bdi :class="matchedSkills.has(skill) ? 'font-semibold text-accent' : 'text-muted'">
              #{{ skill }}
            </bdi>
          </li>
        </ul>

        <!-- ④ LES ÉTATS DU PROFIL, en SIGNATURE. En bas de la colonne, ils cessent d'être une
             accroche pour devenir ce qui clôt la lecture — c'est ce qu'ils sont : des attributs
             constatés, pas des arguments.

             CHACUN porte sa teinte, et un seul la signature de la marque : « Favori », le seul état
             que le recruteur PRODUIT. Les autres constatent un fait. Cinq aplats saturés côte à côte
             ne hiérarchisent rien — ce qui les rend lisibles ensemble est la recette du badge, fond
             dilué et texte mélangé à l'encre de la page, pas la retenue sur la couleur.

             Un badge nomme un attribut ACQUIS, jamais son absence — « Non rencontré » n'existe pas.
             Le coin reste donc vide quand le profil n'en porte aucun, et ne réserve pas de hauteur.
             L'état de dévoilement n'en fait pas partie : il se lit déjà à ce que la card montre. -->
        <div v-if="states.length" class="mt-auto flex flex-wrap justify-end gap-element">
          <UiBadge v-for="state in states" :key="state.key" :tint="state.tint" :icon="state.icon">
            {{ $t(state.labelKey) }}
          </UiBadge>
        </div>
      </div>
    </div>
  </article>
</template>
