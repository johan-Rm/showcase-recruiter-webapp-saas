<script setup lang="ts">
// Une card = un profil = un plein écran de la pile de parcours.
//
// Ce n'est PAS une fiche : le profil complet est un écran à part. C'est un RÉSUMÉ DE DÉCISION, qui
// porte ce qui permet de trancher garder ou passer, et rien de plus. Sans onglets — ils existaient
// pour ranger un profil entier dans un écran, il n'y a plus de profil entier à ranger.
//
// Elle répond à deux questions, dans cet ordre : est-ce le bon professionnel, va-t-il vraiment
// venir. Les compétences ferment la marche. POURQUOI ce profil est là — ce que la recherche trouve
// et ce qui manque — appartient au VERDICT, qui vit à côté de la pile sur grand écran et retombe
// en pied de fiche en dessous.
//
// ELLE NE PORTE PLUS DE GESTE. Retenir et écarter ont rejoint le verdict : les trois se lisent et
// se posent au même endroit, et sur grand écran cet endroit ne bouge plus quand la pile défile.
// Ce qu'il reste ici du verdict est sa TRACE — la bordure d'un profil retenu.
//
// ELLE PORTE EN REVANCHE CE QUE LE PROFIL EST — vu, retenu, nouveau. Ce sont des attributs du
// candidat et non un verdict sur des critères : leur place est contre l'identité qu'ils qualifient,
// et non dans une colonne dont l'objet est de juger des critères.
import { roleOf } from '#services/search/candidate'
import { statesOf } from '#services/search/states'

import type { CandidateCard } from '#shared/types/candidate-card'
import type { RevealType } from '#shared/types/reveal'
import type { SearchCriterion } from '#shared/types/search'

type Props = {
  candidate: CandidateCard
  /** Relayés au verdict de pied de fiche, qui les confronte au profil de CETTE card. */
  criteria: readonly SearchCriterion[]
  /** Faux sur la recherche d'un collègue : on la lit, on n'y retient rien. */
  editable?: boolean
}

const { candidate, criteria, editable = true } = defineProps<Props>()

const emit = defineEmits<{
  toggleFavorite: [candidate: CandidateCard]
  dismiss: [candidate: CandidateCard]
  seen: [candidate: CandidateCard]
  reveal: [candidate: CandidateCard, type: RevealType]
}>()

/**
 * Ce que le profil EST — vu, retenu, nouveau, au Maroc.
 *
 * Chacun porte sa teinte, et un seul la signature de la marque : « Favori », le seul état que le
 * recruteur PRODUIT. Ce qui les rend lisibles ensemble est la recette du badge — fond dilué, texte
 * mélangé à l'encre de la page —, pas la retenue sur la couleur.
 *
 * Un badge nomme toujours un attribut ACQUIS : « Non rencontré » n'existe pas, et l'absence dit
 * déjà ce qu'elle a à dire.
 */
const states = computed(() => statesOf(candidate))

/**
 * Le tiroir est une SURCOUCHE, pas une donnée de cette card : elle l'ouvre par l'état partagé plutôt
 * que d'émettre un évènement qui traverserait la pile jusqu'à la page. Les évènements de cette card
 * portent des décisions à enregistrer ; ouvrir une lecture n'en est pas une.
 */
const { open: openFullProfile } = useProfilePanel()

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
 * C'est le SEUL usage que le portrait fait des correspondances : l'énoncé de ce qui est trouvé et de
 * ce qui manque appartient à la colonne des critères, qui peut le dire en entier. Ici, la
 * correspondance se montre là où elle est — dans la compétence elle-même.
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

/**
 * SOUS 1280 px, LA FICHE CHANGE DE CORPS DÉFILANT — les deux colonnes entières au lieu des seuls
 * blocs du bas. Deux zones défilantes, donc deux endroits où le clavier doit pouvoir entrer, et une
 * seule des deux défile à la fois : le `tabindex` bascule de l'une à l'autre plutôt que d'offrir un
 * arrêt de tabulation qui ne mène à rien.
 *
 * Interrogé par `useMediaQuery` et non par une classe : un attribut ne se pose pas en CSS. Le seuil
 * est celui de `xl`, écrit ici en toutes lettres faute de pouvoir lire un point de rupture Tailwind
 * depuis le script.
 *
 * ⚠️ IL RESTE FAUX JUSQU'AU MONTAGE, ET C'EST VOULU. Le serveur n'a pas de largeur de fenêtre :
 * interrogé directement, `useMediaQuery` répond `false` au rendu serveur et la vraie valeur au
 * premier rendu client — Vue signale alors une incohérence d'hydratation sur l'attribut, constatée
 * en console. En partant de `false` des deux côtés et en ne suivant la fenêtre qu'après le montage,
 * les deux rendus coïncident et le navigateur corrige ensuite en silence.
 */
const viewportIsCompact = useMediaQuery('(max-width: 1279.98px)')
const isCompact = ref(false)

onMounted(() => watchEffect(() => (isCompact.value = viewportIsCompact.value)))

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
  <!-- `snap-start` : la molette déplace la pile de card en card, jamais dans une card.

       ELLE TIENT DANS UN ÉCRAN, ET UNE SEULE ZONE ENCAISSE LA VARIATION. Tout ce qu'elle porte a une
       hauteur prévisible sauf deux blocs : le projet de retour, cité dans les mots du candidat sur un
       texte libre, et les compétences. Réunis dans le corps défilant de la colonne de droite, ils
       absorbent à eux deux l'écart entre un profil bavard et un profil sec. Le bas de la card est
       vide en conséquence — les états et l'ouverture du profil sont remontés contre l'identité —, et
       c'est ce vide qui rend la hauteur tenable, pas un rangement d'esthète.

       `h-full` ET NON `min-h-full`, ET C'EST TOUTE LA MÉCANIQUE. Un plancher ne comprime rien : sous
       `min-h-full` la card grandit à son contenu, si bien que le corps défilant n'a jamais de
       trop-plein à encaisser — il n'absorbe que du vide. Mesuré : à 1280 × 700, une fiche dépassait
       encore de 21 px avec une zone souple à 246 px, très au-dessus de son plancher. C'est une
       hauteur DÉFINIE qui met le contenu sous pression et fait céder la zone.

       `min-h-min` DERRIÈRE, ET LA RÈGLE PLIE PLUTÔT QUE DE ROGNER. La hauteur minimale du contenu
       compte le plancher du corps défilant : quand la part fixe et ce plancher dépassent à eux seuls
       l'écran — fenêtre très basse, titre sur deux lignes —, la card repasse au-dessus d'un plein
       écran au lieu de rendre un bouton d'achat inatteignable. Elle se lit alors en deux temps, ce
       qui est laid ; rogner serait faux.

       TRANSPARENTE, ET SANS COINS. L'aplat, les coins et l'anneau du profil retenu appartiennent au
       bloc qui réunit le portrait et son verdict : une card qui porterait les siens deviendrait un
       objet POSÉ à côté du verdict, alors que les deux sont deux colonnes d'une même carte.

       UN FILET EN HAUT, sauf sur la première. C'est tout ce qui reste pour dire où un profil finit
       et où le suivant commence : sans aplat propre, deux fiches se touchent sans rien entre elles.
       Il ne se voit pas au repos — une card occupe exactement un écran, sa limite tombe hors champ —
       et n'apparaît que pendant le défilement, quand deux profils se partagent la vue. -->
  <article
    ref="root"
    class="search-profile-card flex h-full min-h-min shrink-0 snap-start flex-col gap-block border-t border-line p-block first:border-t-0"
  >
    <!-- ① LE BANDEAU : le poste, seul.
         LE POSTE EST LE TITRE, dévoilé comme masqué. Obtenu par la MISE EN PAGE et non par une
         condition sur les données : le serveur renvoie une initiale là où une telle condition
         attendrait `null`, ce qui la rend inopérante. Le poste est un critère, le nom une
         étiquette ; l'un se lit pour décider, l'autre pour s'adresser.
         LE VRAI INTITULÉ : `currentTitle` et NON `headline`. L'accroche que le candidat s'écrit sur
         son réseau monte à 752 caractères sur le vivier, et s'arrogerait tout le bandeau.
         DEUX LIGNES, et elles ne mordent pas : le poste le plus long du vivier (100 caractères) y
         entre en entier. Sur la fiche on LIT — rien n'y justifie de couper un intitulé,
         contrairement à la Table qui compare et paie chaque caractère en largeur. -->
    <h2
      class="line-clamp-2 min-w-0 font-display text-h2"
      :class="roleLine.isFallback ? 'italic text-muted' : undefined"
    >
      {{ roleLine.text ?? $t('search.profile.untitled') }}
    </h2>

    <!-- ② LES DEUX COLONNES. À gauche COMMENT LE JOINDRE, à droite QUI C'EST.
         Sous `lg` elles s'empilent : la carte de visite d'abord, la substance ensuite.
         ELLES S'ÉTIRENT jusqu'au bas de la card, et c'est ce qui donne au corps défilant une hauteur
         à occuper. Elles ne s'étiraient pas tant que des blocs les suivaient — étirées, elles les
         plaquaient au bord inférieur de l'écran, à quatre cents pixels de ce qu'ils commentaient. Ces
         blocs sont remontés dans la colonne de droite, et le motif tombe avec eux.
         `min-h-0` : un élément de grille ne descend jamais sous la hauteur de son contenu sans lui,
         et le corps défilant pousserait la card au lieu de défiler.

         SOUS 1280 px, C'EST ELLE QUI DÉFILE, et plus le bloc du bas. À cette largeur le verdict
         retombe dans la fiche : même replié en barre, il prend sa place en pied, et ce qui reste à
         la lecture ne suffit plus pour que les compétences seules encaissent tout. Les deux colonnes
         entières deviennent donc le corps défilant, sous un bandeau qui, lui, ne bouge pas.
         `contain-size` la suit pour la même raison qu'il suit l'autre : sans lui, la hauteur
         minimale de la card compterait tout son contenu et interdirait la compression. -->
    <div
      class="search-profile-card-scroll grid min-h-0 flex-1 grid-cols-1 gap-block max-xl:overflow-y-auto max-xl:contain-size lg:grid-cols-[minmax(240px,3fr)_minmax(0,9fr)]"
      :tabindex="isCompact ? 0 : -1"
    >
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
      <!-- LA SUBSTANCE : qui c'est, pourquoi il est là, et s'il va vraiment venir.
           `min-h-0` : sans lui, cette colonne prend la hauteur de son contenu et le corps défilant
           n'a plus rien à encaisser — la contrainte s'arrête une couche trop haut. -->
      <div class="flex min-h-0 flex-col gap-block">
        <!-- L'IDENTITÉ, ET CE QUI S'Y RATTACHE. Le nom ouvre la colonne, il n'ouvre plus la card :
             masqué il ne dit rien, dévoilé il ne dit qu'à qui l'on s'adresse. Le poste, lui, se lit
             pour décider. -->
        <div class="flex flex-col gap-element">
          <div class="flex flex-col">
            <!-- LIRE N'EST PAS DÉCIDER, et l'ouverture du profil complet n'est pas un verdict : elle
                 ne change aucun état, ne coûte aucun crédit, et reste offerte sur la recherche d'un
                 collègue. Elle vit SUR LA LIGNE DU NOM — une ligne déjà occupée, donc zéro hauteur
                 prise —, et c'est ce qui la sort du pied de card où elle tombait hors champ.
                 EN NEUTRE : l'aplat accent reste au seul geste de décision de l'écran, « Retenir »,
                 dans la colonne du verdict. Deux aplats pleins, et aucun des deux ne se distingue.
                 C'EST LE NOM QUI CÈDE, pas le bouton : `min-w-0` + `truncate` sur le nom, `shrink-0`
                 sur le bouton — sans quoi un nom long pousse l'ouverture hors de la colonne. -->
            <div class="flex items-start gap-element">
              <p class="min-w-0 max-w-readable flex-1 truncate font-display text-h3">
                {{ fullName ?? $t('search.profile.untitled') }}
              </p>

              <UButton
                class="shrink-0"
                color="neutral"
                variant="subtle"
                size="sm"
                trailing-icon="i-lucide-arrow-up"
                @click="openFullProfile(candidate)"
              >
                {{ $t('search.profile.open') }}
              </UButton>
            </div>

            <p v-if="situation" class="text-muted">{{ situation }}</p>
          </div>

          <!-- CE QUE LE PROFIL EST — vu, retenu, nouveau —, contre l'identité qu'il qualifie. Sans
               filet : le trait qui les introduisait refermait une lecture en pied de card, il n'a
               plus rien à refermer ici.
               LA RANGÉE DISPARAÎT QUAND IL N'Y A RIEN À DIRE, et rien ne réserve sa hauteur : ce qui
               la suit est un corps défilant, qui reprend la place sans que rien ne saute. -->
          <div v-if="states.length" class="flex flex-wrap gap-element">
            <UiBadge v-for="state in states" :key="state.key" :tint="state.tint" :icon="state.icon">
              {{ $t(state.labelKey) }}
            </UiBadge>
          </div>
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

        <!-- LE CORPS DÉFILANT — le SEUL de la card, et il tient les deux blocs à hauteur variable.
             POURQUOI LES DEUX ENSEMBLE : les compétences sautent aux yeux, mais le projet de retour
             cite le candidat dans ses mots, sur un texte sans longueur maximale — il peut manger le
             budget de hauteur à lui seul, et les compétences n'auraient alors plus rien à céder.
             `flex-1` : il prend ce qui reste et pas un pixel de plus. `min-h-18` tient la place du
             `min-h-0` habituel et devient le PLANCHER — environ trois lignes de compétences — sous
             lequel il cesse de céder ; c'est lui qui fait plier la card plutôt que rogner son
             contenu, sur une fenêtre trop basse.
             RIEN QUAND IL N'Y A RIEN : pas de cadre, pas de gouttière, pas de vide réservé pour un
             candidat sans compétence ni projet de retour.
             `contain-size`, ET C'EST LUI QUI FAIT MARCHER LE PLANCHER DE LA CARD. Sans confinement,
             la hauteur minimale de contenu de la card compte ce corps ENTIER — 246 px mesurés là où
             son plancher en vaut 72 —, si bien que `min-h-min` empêchait toute compression : la card
             restait à 546 px sur une fenêtre qui lui en offrait 525. Confiné, ce bloc ne pèse plus
             que son plancher dans ce calcul, et la card ne grandit que lorsqu'il ne peut vraiment
             plus céder. Son propre défilement, lui, est intact — le confinement porte sur ce qu'il
             impose au-dehors, pas sur ce qu'il contient.
             ATTEIGNABLE AU CLAVIER — `tabindex="0"` : une zone qui défile sans pouvoir recevoir le
             focus rend son contenu inaccessible à qui n'a pas de souris. -->
        <div
          v-if="hasReturnPlan || skills.length"
          class="search-profile-card-scroll flex min-h-18 flex-1 flex-col gap-block overflow-y-auto contain-size max-xl:contain-none max-xl:block max-xl:min-h-0 max-xl:overflow-visible"
          :tabindex="isCompact ? -1 : 0"
        >
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
        </div>
      </div>
    </div>

    <!-- ③ LE VERDICT, EN PIED ET SEULEMENT SOUS `xl`. Au-dessus, il vit à côté de la pile en colonne
         pleine hauteur et n'a rien à faire ici — l'y laisser le montrerait deux fois.
         REPLIÉ EN BARRE (`compact`) : la note et les deux gestes sur une rangée, le détail des
         critères rangé dans une feuille que la note ouvre. Déplié, il pesait plus de trois cents
         pixels en pied de fiche et aucune contrainte de hauteur ne tenait.
         `flex-none` : c'est le seul bloc de la fiche qui ne cède jamais. « Retenir » est la raison
         d'être de cet écran ; il reste sous le pouce quelle que soit la position de lecture. -->
    <SearchVerdict
      class="flex-none xl:hidden"
      compact
      :candidate="candidate"
      :criteria="criteria"
      :editable="editable"
      @toggle-favorite="emit('toggleFavorite', $event)"
      @dismiss="emit('dismiss', $event)"
    />
  </article>
</template>

<style scoped>
/* LA PLACE DE LA BARRE EST RÉSERVÉE, QU'ELLE S'AFFICHE OU NON — la recette de la console, appliquée
   au seul autre endroit de l'écran qui défile pour de bon. Sans elle, l'apparition de la barre
   décale le texte du corps au moment précis où l'on passe au profil suivant, c'est-à-dire cent fois
   dans une séance. `stable` suit le sens de lecture : la gouttière passe à gauche en arabe.

   En CSS et non en utilitaire : le kit n'en a pas, et aucune VALEUR n'est écrite ici — c'est un
   mot-clé, pas une mesure. */
.search-profile-card-scroll {
  scrollbar-gutter: stable;
}
</style>
