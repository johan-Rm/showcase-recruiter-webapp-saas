<script setup lang="ts">
/**
 * La recherche par critères, écrite comme on la dicterait.
 *
 * « Je cherche un profil de **Développeur Symfony** avec **5 ans** d'expérience, niveau **Master +** »
 * — les libellés de champs disparaissent parce que la grammaire les remplace : « avec 5 ans
 * d'expérience » n'a besoin d'aucun titre au-dessus de lui.
 *
 * LA PHRASE EST ASSEMBLÉE DE SEGMENTS, jamais écrite d'un bloc : chaque morceau porte sa clé, et
 * l'ordre des morceaux appartient à la langue. C'est la seule construction qui survive à l'arabe,
 * qui ne se lit pas dans le même sens.
 *
 * Trois propositions, en trois BLOCS et non un paragraphe qui se coupe où il peut : chaque critère
 * ouvre sa ligne. Laissée au retour à la ligne automatique, la coupure se déplaçait à chaque
 * caractère tapé dans le champ métier, et la phrase se réorganisait sous les yeux.
 *
 * La phrase et les mots-clés se tiennent CÔTE À CÔTE dès qu'il y a la place, et l'un sous l'autre
 * sinon. Empilés sur toute la largeur, ils laissaient la phrase s'étirer sur une seule ligne
 * démesurée pendant que les mots-clés traînaient un vide à leur droite.
 *
 * Composant PASSIF sur la recherche : il collecte et émet, il ne lance rien. Il fait en revanche ses
 * propres appels d'AUTO-COMPLÉTION — proposer pendant la frappe n'a de sens qu'ici, et remonter
 * chaque touche à la page pour qu'elle interroge à sa place n'aurait rien clarifié. Même parti pris
 * que `SearchFiltersPrimary`, qui sert toujours la console de la Visionneuse.
 */
import type { PrimarySearchForm } from '#services/search/criteria'
import type { SearchSuggestion } from '#shared/types/search'

/**
 * Les critères de DÉPART, quand on rouvre une recherche pour la retoucher.
 *
 * Lus une seule fois, à la construction : ce sont des valeurs initiales, pas une liaison. Les
 * resuivre écraserait la frappe en cours à chaque réponse du serveur.
 */
const { initial } = defineProps<{ initial?: PrimarySearchForm }>()

const emit = defineEmits<{
  /** À chaque frappe : l'appelant compte, nomme et lance ; la phrase ne fait que collecter. */
  change: [filters: PrimarySearchForm]
  /**
   * Entrée dans un champ : l'utilisateur demande le lancement, l'appelant décide et exécute.
   * Nommé `launch` — le mot du bouton — et non `submit` : la règle `pages-no-interactive-template`
   * scanne le template des pages ligne à ligne, un `@submit` d'écoute y rejouerait la violation
   * que ce composant existe pour lever.
   */
  launch: []
  /**
   * Les critères sont dans un état NOMMABLE : le poste n'est plus un mot en cours de frappe.
   *
   * Distinct de `change`, qui part à chaque touche parce que le décompte, lui, doit suivre la
   * frappe. Nommer la recherche « arc » pendant qu'on écrit « Architecte » donnait un titre qui se
   * réécrivait lettre à lettre, à l'endroit le plus visible de l'écran.
   */
  settle: []
}>()

/**
 * Les paliers d'expérience — resserrés en bas où une année compte, espacés en haut où elle ne
 * discrimine plus. Le curseur continu de 0 à 15 tombe avec : personne ne cherche « 11 ans et plus »
 * plutôt que 10, et seize lignes dans une phrase se parcourent mal.
 */
const EXPERIENCE_STEPS = [1, 2, 3, 5, 7, 10, 15] as const

/** Miroir de l'enum API `EducationLevel`, dans l'ordre des rangs. */
const EDUCATION_LEVELS = [
  'BAC',
  'BAC_PLUS_2',
  'LICENCE',
  'MASTER',
  'INGENIEUR',
  'DOCTORAT',
] as const

// En deçà, on n'interroge pas : sur une lettre la liste ne renseigne pas, elle occupe l'écran.
const MIN_TERM_LENGTH = 2
const DEBOUNCE_MS = 220

// `useLocale()` et non `useI18n()` : le module i18n est encapsulé derrière ce wrapper, seul point
// autorisé à le toucher, et un verrou CI le tient. `$t` reste l'exception, dans les templates.
const { t } = useLocale()
const { suggest } = useSuggestions()

const filters = reactive<PrimarySearchForm>({
  jobTitle: initial?.jobTitle ?? '',
  keywords: [...(initial?.keywords ?? [])],
  experienceMin: initial?.experienceMin ?? 0,
  educationLevel: initial?.educationLevel ?? '',
})

// `useId` et non des identifiants en dur : la phrase ne peut pas jurer d'être seule sur la page, et
// un identifiant en double casse le lien label ↔ champ pour tout le monde.
const uid = useId()
const fieldId = (name: string) => `${uid}-${name}`

/** Un vocabulaire interrogé pendant la frappe, débouncé, dernière réponse gagnante. */
const useVocabulary = (type: 'JOB' | 'SKILL') => {
  const term = ref('')
  const items = ref<SearchSuggestion[]>([])
  const loading = ref(false)
  const failed = ref(false)

  const run = useDebounceFn(async (input: string) => {
    const results = await suggest(type, input)

    if (input === term.value.trim()) {
      items.value = results.items
      failed.value = results.failed
      loading.value = false
    }
  }, DEBOUNCE_MS)

  watch(term, (value) => {
    const input = value.trim()

    if (input.length < MIN_TERM_LENGTH) {
      items.value = []
      failed.value = false
      loading.value = false

      return
    }

    loading.value = true
    run(input)
  })

  return { term, items, loading, failed }
}

const jobs = useVocabulary('JOB')
const skills = useVocabulary('SKILL')

// Le nombre de profils que chacune ramènerait ne s'affiche PAS : il révélerait la composition du
// portefeuille sans qu'un seul profil ait été dévoilé.
const jobSuggestions = computed(() => jobs.items.value.map((item) => ({ value: item.value })))
const keywordSuggestions = computed(() => skills.items.value.map((item) => ({ value: item.value })))

/** Les paliers, tels qu'ils se lisent DANS la phrase : « peu importe », « 5 ans ». */
const experienceOptions = computed(() => [
  { label: t('search.sentence.experienceAny'), value: '0' },
  ...EXPERIENCE_STEPS.map((years) => ({
    label:
      15 === years
        ? t('search.sentence.experienceMax', { count: years })
        : t('search.sentence.experienceValue', { count: years }),
    value: String(years),
  })),
])

/**
 * Le « + » n'est pas décoratif : le serveur filtre en `atLeast`, donc « Master » ramène aussi les
 * ingénieurs et les docteurs. Sans lui, l'écran laisse croire à une sélection exacte — c'est ce que
 * les six étiquettes disaient à tort.
 *
 * Le doctorat n'en porte pas : rien ne le surplombe.
 */
const educationOptions = computed(() => [
  { label: t('search.sentence.educationAny'), value: '' },
  ...EDUCATION_LEVELS.map((level) => ({
    label:
      'DOCTORAT' === level
        ? t(`search.education.${level}`)
        : t('search.sentence.educationAtLeast', { level: t(`search.education.${level}`) }),
    value: level,
  })),
])

/**
 * Le gabarit d'un mot qu'on CHOISIT : l'option la plus longue de sa liste, jamais la valeur en cours.
 *
 * Un champ calé sur sa valeur du moment se rétrécirait en passant de « peu importe » à « 5 ans », et
 * toute la fin de la phrase se déplacerait à chaque choix. On réserve donc une fois pour toutes la
 * place du plus long libellé : la phrase ne bouge plus, et la liste déroulante — qui prend la largeur
 * de son champ — reste assez large pour se lire.
 */
const longestLabel = (options: { label: string }[]) =>
  options.reduce(
    (longest, option) => (option.label.length > longest.length ? option.label : longest),
    '',
  )

const experienceGhost = computed(() => longestLabel(experienceOptions.value))
const educationGhost = computed(() => longestLabel(educationOptions.value))

/**
 * CE QUE LA DURÉE COMPTE, dit dans la phrase — parce que le moteur en change selon le poste.
 *
 * Un poste posé, et le serveur compte les mois exercés dans CE métier ; aucun poste, et il compte la
 * carrière entière. « avec 5 ans d'expérience » annoncerait alors l'un pendant que le serveur fait
 * l'autre, et l'écart ne se voit sur aucun écran.
 *
 * Le fragment change, pas la structure : « avec 5 ans **dans ce métier**, » se lit comme la phrase
 * qu'il remplace.
 */
const experienceFieldLabel = computed(() =>
  '' === filters.jobTitle.trim()
    ? t('search.primary.experience.label')
    : t('search.primary.experience.labelInJob'),
)

const experienceWording = computed(() =>
  '' === filters.jobTitle.trim()
    ? t('search.sentence.experience')
    : t('search.sentence.experienceInJob'),
)

// Le curseur est une chaîne côté sélecteur, un nombre côté critères : la conversion se fait ici, une
// fois, plutôt qu'à chaque lecture.
const experienceValue = computed({
  get: () => String(filters.experienceMin),
  set: (value: string) => {
    filters.experienceMin = Number(value)
  },
})

// Copie et non la référence : l'appelant recevrait sinon un objet qui continue de muter sous lui.
function snapshot(): PrimarySearchForm {
  return { ...filters, keywords: [...filters.keywords] }
}

/**
 * Le poste est-il EN COURS DE FRAPPE ?
 *
 * Le champ « poste » retient le terme libre à chaque touche — c'est voulu, un intitulé que le
 * vocabulaire ignore doit pouvoir passer. Mais un mot en cours n'est pas un intitulé : « arc » n'est
 * pas un critère, c'est le début de « Architecte ». Tant qu'on le compose, la valeur ne vaut que
 * pour interroger le vocabulaire.
 *
 * Redevient faux dès qu'une suggestion est choisie, et dès que le champ est vidé : il n'y a alors
 * plus rien à compléter.
 */
const jobTyping = ref(false)

const onJobTerm = (value: string) => {
  jobTyping.value = '' !== value.trim()
  jobs.term.value = value
}

// Le seul watcher du composant, et il est justifié : il n'y a pas d'événement commun aux quatre
// critères — un combobox, deux sélecteurs, une liste d'étiquettes.
watch(filters, () => {
  emit('change', snapshot())

  if (!jobTyping.value) {
    emit('settle')
  }
})
</script>

<template>
  <form
    class="search-sentence grid min-h-0 w-full grid-cols-1 gap-element lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] xl:grid-cols-[minmax(0,3fr)_minmax(0,1fr)]"
    @submit.prevent="emit('launch')"
  >
    <!-- DEUX CARTES, PAS UNE COUPÉE EN DEUX. Ce qu'on cherche et les mots qu'il doit maîtriser sont
         deux questions : un filet à l'intérieur d'un même aplat ne le disait pas, il découpait un
         objet. Deux surfaces séparées par un vide le disent sans rien tracer — c'est la séparation
         qu'emploie déjà la Visionneuse entre sa colonne d'avancement et sa pile de profils.
         LE FORMULAIRE LES ENTOURE TOUTES LES DEUX : Entrée lance la recherche, et cet effet natif
         exige que la balise entoure les champs. Il vit ici et non dans la page — une page n'écrit
         pas de code interactif (règle `pages-no-interactive-template`). La soumission est prévenue
         ICI : chez l'appelant, un oubli rechargerait la page et perdrait le brouillon. -->
    <!-- LE BOUTON CACHÉ N'EST PAS UN RÉSIDU : sans bouton de soumission DANS le formulaire, la
         soumission implicite n'a lieu qu'avec un seul champ texte — la phrase en a plusieurs. Le
         bouton visible, lui, est bien dans la carte depuis qu'il a rejoint la phrase, mais il
         déclenche au clic et non par soumission : Entrée n'a donc toujours d'effet que par
         celui-ci. Masqué par l'attribut natif, il ne s'affiche pas, ne se tabule pas et n'est pas
         annoncé, mais compte toujours pour la soumission. -->
    <button type="submit" hidden></button>

    <!-- LA CARTE DE LA PHRASE : rien d'autre dedans, donc son contenu se centre en hauteur. -->
    <UiCard class="flex min-h-0 flex-col justify-center">
      <!-- LA PHRASE. Chaque valeur est isolée bidirectionnellement : un intitulé de poste en
           caractères latins posé au milieu d'une ligne arabe, ou un « + » collé à un mot, changent
           de place tout seuls sans cela — et le « + » porte tout le sens du filtre.
           Le palier de titre attend `lg` : ses lignes sont insécables, et « avec … d'expérience, »
           à cette taille dépasse la largeur d'un téléphone.
           Elle est BORNÉE puis centrée dans sa carte : étirée jusqu'au bord, elle laissait un champ
           « poste » démesuré au bout d'une ligne, et la phrase perdait son axe. Sous `lg`, la carte
           est plus étroite que la borne : le centrage n'a alors aucun effet. -->
      <p
        class="search-sentence__text mx-auto flex w-full max-w-3xl flex-col text-center font-display text-body font-semibold lg:text-h3 xl:text-h2"
      >
        <!-- Le POSTE se règle sur ce qu'on tape, comme les deux autres valeurs. Il s'étirait
             jusqu'au bout de sa ligne pour élargir le panneau de suggestions, mais cette ligne
             pleine largeur empêchait la phrase de se centrer : les deux lignes courtes en dessous
             restaient calées à gauche sous une première ligne étirée. Le panneau redevient donc
             large comme le champ, et un intitulé long y sera tronqué — contrepartie assumée.
             La ligne reste en `flex` : l'écart entre les deux mots vient de `gap`, une espace de
             texte se perdrait en fin d'élément. -->
        <span class="flex flex-wrap items-baseline justify-center gap-x-[0.28em]">
          <span>{{ $t('search.sentence.lead') }}</span>
          <span class="search-sentence__slot [unicode-bidi:isolate]">
            <span aria-hidden="true" class="search-sentence__ghost">
              {{ filters.jobTitle || $t('search.sentence.jobPlaceholder') }}
            </span>
            <span class="search-sentence__control">
              <label class="sr-only" :for="fieldId('jobTitle')">
                {{ $t('search.primary.jobTitle.label') }}
              </label>
              <UiRemoteCombobox
                :id="fieldId('jobTitle')"
                v-model="filters.jobTitle"
                :items="jobSuggestions"
                :loading="jobs.loading.value"
                :min-chars="MIN_TERM_LENGTH"
                :placeholder="$t('search.sentence.jobPlaceholder')"
                :loading-label="$t('search.primary.jobTitle.loading')"
                :empty-label="$t('search.primary.noResult')"
                @update:term="onJobTerm"
                @select="jobTyping = false"
              />
            </span>
          </span>
        </span>

        <span class="block whitespace-nowrap">
          {{ $t('search.sentence.with') }}
          <span class="search-sentence__slot [unicode-bidi:isolate]">
            <span aria-hidden="true" class="search-sentence__ghost search-sentence__ghost--choice">
              {{ experienceGhost }}
            </span>
            <span class="search-sentence__control">
              <label class="sr-only" :for="fieldId('experience')">
                {{ experienceFieldLabel }}
              </label>
              <!-- « Peu importe » s'ÉCRIT, en gris. Le champ reste vide au sens du modèle — aucun
                   critère n'est posé —, mais un blanc laissait croire à une réponse oubliée. Le
                   placeholder dit que ne rien choisir est une réponse, et sa couleur qu'elle n'est
                   pas encore celle du recruteur. -->
              <UiInlineSelect
                :id="fieldId('experience')"
                v-model="experienceValue"
                :options="experienceOptions"
                :empty-label="$t('search.primary.noResult')"
              />
            </span>
          </span>
          {{ experienceWording }}
        </span>

        <span class="block whitespace-nowrap">
          {{ $t('search.sentence.level') }}
          <span class="search-sentence__slot [unicode-bidi:isolate]">
            <span aria-hidden="true" class="search-sentence__ghost search-sentence__ghost--choice">
              {{ educationGhost }}
            </span>
            <span class="search-sentence__control">
              <label class="sr-only" :for="fieldId('education')">
                {{ $t('search.primary.education.label') }}
              </label>
              <UiInlineSelect
                :id="fieldId('education')"
                v-model="filters.educationLevel"
                :options="educationOptions"
                :empty-label="$t('search.primary.noResult')"
              />
            </span>
          </span>
        </span>
      </p>
    </UiCard>

    <!-- LA CARTE DES MOTS-CLÉS. Reliés en OU : en ajouter ÉLARGIT, ce que le libellé doit dire.
         Le bouton de lancement lui sert de PIED, PLEINE LARGEUR ET À FLEUR DE BORDS : c'est la
         dernière chose qu'on remplit, et le geste qui suit part donc d'ici.
         Elle n'emploie pas la carte du kit, qui porte son propre rembourrage : ici c'est le CONTENU
         qui le porte, pour que le pied touche les trois bords. Une marge sous lui en referait un
         bouton posé dans un cadre. Même construction que la colonne d'avancement de la Visionneuse.
         PAS DE `overflow-hidden` ICI, contrairement à cette colonne-là : le panneau des mots-clés
         s'ouvre À CÔTÉ de la carte, donc en dehors d'elle, et un débordement masqué le découpait —
         il n'en restait qu'un liseré collé au bord. C'est donc le pied qui arrondit lui-même ses
         deux coins bas, cf. la classe que l'appelant lui pose. -->
    <div class="flex min-h-0 flex-col rounded-lg bg-surface">
      <div class="flex min-h-0 flex-1 flex-col gap-element p-6">
        <!-- LE PANNEAU COUVRE EXACTEMENT LA CARTE DE LA PHRASE, et cette largeur se calcule ici
             parce qu'elle se déduit de la grille — que le composant, sans domaine, ne connaît pas.
             Son bord droit est déjà posé : il s'aligne sur la colonne, moins la gouttière. Il ne
             reste qu'à lui donner de quoi atteindre le bord gauche de la phrase.
             Les pourcentages portent sur la largeur INTÉRIEURE de cette colonne, rembourrage déduit.
             La phrase valant deux colonnes entières, il faut deux fois cet intérieur PLUS les
             rembourrages que le pourcentage a laissés de côté — quatre pour les deux colonnes, un
             cinquième pour rejoindre le bord. Trois colonnes au-delà de 80rem, donc sept.
             La gouttière, elle, s'annule : celle que le panneau saute est celle qu'il traverse. -->
        <UiTagPicker
          :id="fieldId('keywords')"
          v-model="filters.keywords"
          class="min-h-0 flex-1 [--tag-picker-beside-w:calc(200%+7.5rem)] xl:[--tag-picker-beside-w:calc(300%+10.5rem)]"
          block
          beside
          :items="keywordSuggestions"
          :loading="skills.loading.value"
          :failed="skills.failed.value"
          :min-chars="MIN_TERM_LENGTH"
          :described-by="fieldId('keywordsLabel')"
          :add-label="$t('search.sentence.keywordAdd')"
          :close-label="$t('search.primary.keywords.close')"
          :search-label="$t('search.primary.keywords.label')"
          :search-placeholder="$t('search.primary.keywords.placeholder')"
          :loading-label="$t('search.primary.keywords.loading')"
          :empty-label="$t('search.primary.noResult')"
          :unavailable-label="$t('search.primary.keywords.unavailable')"
          :added-label="$t('search.primary.keywords.added')"
          :remove-label="(value: string) => $t('search.primary.keywords.remove', { value })"
          :create-label="(value: string) => $t('search.primary.keywords.create', { value })"
          :add-all-label="(count: number) => $t('search.primary.keywords.addAll', { count })"
          :added-announcement="
            (count: number) => $t('search.primary.keywords.addedCount', { count })
          "
          @update:term="skills.term.value = $event"
        >
          <template #header>
            <span :id="fieldId('keywordsLabel')" class="flex-none text-label text-muted">
              {{ $t('search.sentence.keywords') }}
            </span>
          </template>
        </UiTagPicker>
      </div>

      <!-- L'ACTION est posée par l'appelant : c'est lui qui sait ce que « lancer » veut dire ici, et
           lui qui tient l'état du brouillon. Elle est le pied lui-même — pas un bouton DANS un
           pied —, donc rien ne l'entoure : c'est l'appelant qui la rend pleine largeur et à angles
           droits, comme le fait déjà la colonne d'avancement de la Visionneuse. -->
      <slot name="action" />
    </div>
  </form>
</template>

<style scoped>
/**
 * L'interligne de la phrase, et non celui du palier typographique.
 *
 * Le palier de corps apparie 1.6, juste pour un texte qu'on lit. Ici la ligne porte des champs
 * soulignés : le filet de la ligne du dessous vient toucher les jambages de celle du dessus. C'est
 * la seule valeur du composant qui ne vienne pas d'un token, et elle est locale par nature — elle
 * dépend de ce que la ligne contient, pas du palier typographique.
 */
.search-sentence__text {
  line-height: 2.2;
}

/**
 * Un mot de la phrase qui s'écrit : il fait la largeur de son texte, pas une largeur fixe. Un champ
 * trop court coupe le mot, un champ trop long ouvre un trou au milieu de la phrase.
 *
 * La grille superpose un fantôme — le texte, invisible mais compté — et le champ lui-même. C'est le
 * fantôme qui donne sa largeur à la colonne ; le champ, en `width: 100%`, la remplit. Aucune mesure
 * en JavaScript, donc rien à recalculer et rien qui casse au rendu serveur.
 *
 * Le plancher évite qu'un champ vide se réduise à un trait de deux caractères, et que le panneau de
 * suggestions — calé sur la largeur du champ — devienne illisible.
 */
.search-sentence__slot {
  display: inline-grid;
  min-width: 8ch;
  max-width: 100%;
  vertical-align: baseline;
}

/* Le fantôme : le texte qui donne sa largeur à la colonne, jamais affiché mais bien mesuré. Il
   reprend le rembourrage du champ qu'il gabarie, sans quoi la largeur réservée serait fausse de la
   valeur de ce rembourrage. */
.search-sentence__ghost {
  grid-column-start: 1;
  grid-row-start: 1;
  padding-inline: 0.12em;
  visibility: hidden;
  white-space: pre;
}

/* Un mot qu'on CHOISIT réserve en plus la place du chevron, que le sélecteur du kit pose à
   l'intérieur du champ. */
.search-sentence__ghost--choice {
  padding-inline-end: 1.4em;
}

/**
 * Le champ lui-même, posé par-dessus le fantôme — et RETIRÉ du calcul de largeur.
 *
 * `width: 0` le rend sans épaisseur pour la grille, `min-width: 100%` lui rend ensuite toute la
 * colonne. Sans ce couple, c'est la largeur intrinsèque de l'`<input>` qui gagne : un champ texte
 * vaut vingt caractères par défaut, si bien que « 1 an » réservait près de trois cents pixels et
 * traînait un filet vide jusqu'au mot suivant. Le fantôme perdait sans qu'on voie pourquoi.
 */
.search-sentence__control {
  grid-column-start: 1;
  grid-row-start: 1;
  width: 0;
  min-width: 100%;
}

/**
 * CE QU'ON A CHOISI porte la couleur de la marque, le reste de la phrase reste neutre.
 *
 * La phrase se relit alors d'un coup d'œil : le fixe en blanc, le variable en vert. Les champs du
 * kit sont neutres par défaut — c'est leur bon réglage partout ailleurs, où rien ne distingue une
 * valeur d'un libellé. Ici la grammaire le fait déjà, la couleur ne fait que le confirmer.
 *
 * L'invite (« poste… ») garde sa couleur muette : elle dit ce qu'on peut écrire, pas ce qu'on a
 * choisi. Le chevron aussi, il appartient au contrôle et non à la valeur.
 */
.search-sentence__control :deep(.ui-remote-combobox__input),
.search-sentence__control :deep(.ui-inline-select__input) {
  color: var(--accent);
}

/**
 * LES CHAMPS DE LA PHRASE N'ONT PAS DE FILET — au repos et au survol.
 *
 * Le soulignement dit « il y a un champ ici ». Il gagne son droit dans un formulaire, où un champ
 * vide ne se distingue sinon pas du papier. Ici, aucun champ n'est jamais vide : l'intitulé porte
 * son invite, et les deux choix affichent « peu importe » tant qu'on n'a rien posé. Le filet ne
 * renseignait donc plus, et il hachait en trois traits une phrase qui doit se lire d'un trait.
 *
 * AU FOCUS, IL REVIENT, et c'est délibéré. Le repère clavier repose ici sur deux marques — cette
 * ligne et un fond teinté ; n'en garder qu'une affaiblirait le seul signal qui dit où l'on tape.
 * Un champ sans bordure au repos qui en prend une sous le curseur ne surprend pas : c'est l'inverse
 * qui manquerait.
 *
 * Le retrait est porté PAR LA PHRASE, pas par le kit : les mêmes composants servent l'inscription,
 * le compte et l'équipe, où un champ vide reste possible et où le filet garde tout son sens.
 */
.search-sentence__control :deep(.ui-remote-combobox__input),
.search-sentence__control :deep(.ui-inline-select__input) {
  border-bottom-color: transparent;
}

.search-sentence__control :deep(.ui-remote-combobox__input:hover),
.search-sentence__control :deep(.ui-inline-select__input:hover) {
  border-bottom-color: transparent;
}

/**
 * Un mot qu'on CHOISIT se clique — le curseur le dit.
 *
 * Ces deux champs s'ouvrent au focus et se comportent d'abord comme des boutons ; la barre de saisie
 * annonçait un texte à écrire. Ils acceptent bien la frappe pour filtrer une longue liste, mais ce
 * n'est pas le geste courant sur cinq et sept options.
 */
.search-sentence__control :deep(.ui-inline-select__input) {
  cursor: pointer;
}

/**
 * Les panneaux de suggestions retombent au corps de texte courant.
 *
 * `font: inherit` fait bien son travail dans le champ — un mot tapé doit s'écrire comme la phrase
 * qui le porte. Mais la LISTE héritait du même gabarit : des intitulés en gras, à la taille d'un
 * titre, dans un panneau large comme son champ, ne pouvaient que se couper en « Respo… ».
 */
.search-sentence__control :deep(.ui-remote-combobox__list),
.search-sentence__control :deep(.ui-inline-select__list) {
  font-family: var(--font-ui);
  font-size: var(--text-body);
  font-weight: 400;
  line-height: var(--text-body--line-height);
}
</style>
