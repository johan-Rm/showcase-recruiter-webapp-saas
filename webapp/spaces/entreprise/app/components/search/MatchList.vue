<script setup lang="ts">
// CE PROFIL FACE AUX CRITÈRES. Ce qui a été demandé, le verdict, et ce que le profil porte en face.
//
// ELLE NE RETOUCHE RIEN : on juge des profils autour d'elle — on retient, on écarte —, et un geste
// qui rejoue la recherche n'a pas sa place au milieu d'un tri. Une croix de retrait y porterait en
// prime le même dessin que la croix du verdict « non trouvé », deux centimètres plus loin : un
// constat et une action irréversible dessinés pareil. On retire un critère là où l'on modifie la
// recherche, pas ici.
//
// ELLE NE CONNAÎT PAS SA PLACE, et c'est le verdict qui la lui donne : en colonne détachée elle juge
// le profil qu'on REGARDE, en pied de fiche celui qui la PORTE. Les deux cas coexistent selon la
// largeur, et aucun code ici ne les distingue.
//
// C'est le seul endroit de l'écran qui dise une ABSENCE, là où le reste de la fiche ne peut
// surligner que ce que le profil porte.
//
// Le libellé reste FACTUEL — la donnée est déclarative, et affirmer une absence ferait écarter de
// bons profils. C'est la note de fin de liste qui porte cette réserve.
import { criterionText, renderValue } from '#services/search/criteria'

import type { CriterionOutcome } from '#services/search/criteria'

type Props = {
  /** Déjà confrontés et ordonnés : le composant affiche, il ne compare pas. */
  outcomes: CriterionOutcome[]
}

const props = defineProps<Props>()

const { t } = useLocale()

/**
 * La marque du neutre — tout ce qui n'est pas satisfait.
 *
 * `bg-text/10` et non `bg-muted` : ce dernier nom appartient à Nuxt UI, qui le branche sur la
 * surface douce — c'est-à-dire sur le fond de la cartouche elle-même. Le rond se peindrait dans la
 * couleur de ce qu'il est censé trancher, sans que rien ne le signale.
 */
const NEUTRE = 'bg-text/10 text-muted'

/**
 * Le rond qui ouvre la ligne — et, depuis que la ligne est unique, LE SEUL À DIRE LE VERDICT.
 *
 * Les mots « trouvé » et « non trouvé » ont disparu de la ligne : posés à côté d'une coche et d'une
 * croix, ils écrivaient une seconde fois ce que le signe disait déjà, et cette redite coûtait
 * l'étage qu'on vient de rendre.
 *
 * UNE SEULE COULEUR DANS CETTE COLONNE, celle de la marque, et du gris pour tout le reste. Il n'y a
 * pas deux teintes qui se répondent mais **une couleur et son absence** : rien n'a échoué quand un
 * critère manque, le profil ne coche simplement pas la case, et la note de fin de liste dit que
 * l'absence peut tenir au CV public plutôt qu'au candidat.
 *
 * LE DISQUE RESTE SOMBRE ET C'EST LE SIGNE QUI S'ÉCLAIRE. Un aplat plein par critère satisfait pèse
 * autant que le geste « Retenir » posé juste au-dessus, et six lignes font six taches. Ici le fond
 * dilué tient la forme, la coche porte le signal.
 *
 * UN POSTE PASSÉ N'EST PAS UNE COCHE. Le critère est satisfait — il compte dans le score —, mais le
 * candidat n'exerce plus ce métier aujourd'hui : le tiret dit « plus maintenant » là où la coche
 * dirait « oui ».
 */
const discOf = (outcome: CriterionOutcome): { icon: string; tint: string } | null => {
  if (null === outcome.matched) {
    return null
  }

  if ('PAST' === outcome.tenure) {
    return { icon: 'i-lucide-minus', tint: NEUTRE }
  }

  return outcome.matched
    ? { icon: 'i-lucide-check', tint: 'bg-accent/15 text-accent' }
    : { icon: 'i-lucide-x', tint: NEUTRE }
}

/**
 * Le mot qui ferme la ligne — le « quand » du métier, et lui seul.
 *
 * C'est la seule information de la ligne qu'aucun rond ne porte : le tiret dit « plus maintenant »
 * de façon allusive, le mot le dit. Tout le reste du verdict se lit dans le disque.
 */
const tenureOf = (outcome: CriterionOutcome): { text: string; tint: string } | null =>
  outcome.tenure
    ? {
        text: t(`search.viewer.panel.tenure.${outcome.tenure}`),
        tint: 'CURRENT' === outcome.tenure ? 'text-accent' : 'text-muted',
      }
    : null

/**
 * D'OÙ VIENT LA LIGNE, en un seul repère : la catégorie du critère, puis l'endroit du profil où le
 * terme a été vu quand il y en a un.
 *
 * Fusionné et non ajouté en pied de bulle : posée après l'extrait, la provenance arrivait derrière
 * le texte le plus long de la bulle, c'est-à-dire précisément là où on ne la lirait plus. Et un
 * second repère gris sous le premier fait deux fois le même travail.
 *
 * LE LIBELLÉ RESTE NON EXCLUSIF — « une compétence », jamais « uniquement dans une compétence » : le
 * serveur s'arrête au premier endroit qui répond, et un terme présent ailleurs aussi n'en dirait
 * rien. Sous-vendre un profil est le seul risque que cette ligne porte.
 */
const originOf = (outcome: CriterionOutcome): string | null => {
  const parts = [
    outcome.labelKey ? t(outcome.labelKey) : null,
    outcome.source ? t(`search.viewer.panel.source.${outcome.source}`) : null,
  ].filter((part): part is string => null !== part)

  return parts.length ? parts.join(' · ') : null
}

/**
 * Chaque ligne, prête pour le gabarit.
 *
 * Composée ici et non dans le template : une fonction appelée dans le gabarit se rejoue à chaque
 * rendu et à chaque branche qui l'interroge — et la bulle redemande les mêmes valeurs que la ligne.
 */
const rows = computed(() =>
  props.outcomes.map((outcome) => ({
    key: `${outcome.criterion.facet}-${outcome.criterion.value}`,
    disc: discOf(outcome),
    tenure: tenureOf(outcome),
    // D'OÙ VIENT LA LIGNE — « Mot-clé », « Mot-clé · une compétence ». Elle sert la bulle et
    // l'écoute, pas la ligne : cf. le gabarit.
    label: originOf(outcome),
    // CE QUE LA LIGNE ÉCRIT, et pour un critère qui ne vaut qu'un oui c'est son LIBELLÉ : une
    // cartouche qui n'afficherait que « Oui » ne dit pas de quoi elle parle. `criterionText` porte
    // déjà cette exception pour les puces de la barre haute ; elle vaut ici depuis que la ligne n'a
    // plus de place pour le couple libellé + valeur.
    wanted: criterionText(outcome.criterion, t),
    // CE QUE LA BULLE ÉCRIT SOUS LA CATÉGORIE — la valeur brute, donc « Oui » là où la ligne a mis
    // le libellé. Sans quoi la bulle dirait « Pied-à-terre » deux fois de suite.
    asked: renderValue(outcome.wanted, t),
    found: outcome.found ? renderValue(outcome.found, t) : null,
    // CE QUI MANQUE SE RETIRE, ce que le profil porte reste en encre franche : une hiérarchie à
    // l'intérieur de la liste, qui ne coûte aucune couleur. Sans profil à confronter, rien à
    // hiérarchiser — les critères se lisent tous pareil.
    nameTint: false === outcome.matched ? 'text-muted' : '',
  })),
)

/**
 * La note ne paraît QUE quand il manque quelque chose.
 *
 * Elle répond à une question qu'on ne se pose que dans ce cas ; sur un profil qui coche tout, elle
 * expliquerait une absence qui n'existe pas.
 */
const incomplete = computed(() => props.outcomes.some((outcome) => false === outcome.matched))

/**
 * Le temps d'arrêt avant que la bulle sorte.
 *
 * Le défaut de la bibliothèque approche les huit dixièmes de seconde — mesuré : un survol de lecture
 * la traverse sans rien voir, et on croit qu'il n'y a rien. Deux dixièmes suffisent à écarter le
 * simple passage de souris sans faire attendre celui qui s'arrête.
 */
const DELAI_BULLE = 200
</script>

<template>
  <!-- ELLE PREND LA HAUTEUR QU'ON LUI DONNE ET ELLE DÉFILE : `min-h-0` avec `flex-1`, jamais
       `flex-none` sans hauteur maximale — sous cette forme, une recherche à six critères pousse la
       colonne au-delà de la fiche qui la porte.
       LE TITRE NE S'AFFICHE PAS : l'en-tête de score dit de quoi parle ce bloc, et le redire
       au-dessus des lignes ferait deux titres pour une seule liste. Il reste À L'ÉCOUTE, parce qu'un
       plan de page sans titre de section n'a plus de repère de navigation. -->
  <div v-if="outcomes.length" class="search-match-list flex min-h-0 flex-1 flex-col gap-element">
    <h3 class="sr-only">{{ $t('search.viewer.panel.matches') }}</h3>

    <!-- LA LISTE PREND TOUT L'ESPACE QUI RESTE, et elle est la seule à défiler. La note, elle, est
         COLLÉE AU BAS DE LA COLONNE, hors du défilement.
         ⚠️ CE CHOIX TIENT À LA HAUTEUR DE LA LISTE, et il est à reprendre si elle la perd. Une note
         punaisée prend sa hauteur aux cartouches : sur une liste à l'étroit, elle TRANCHE la
         dernière — cinq critères annoncés par le score, trois visibles, et rien qui dise où sont les
         autres. La liste ne s'en accommode que parce que la cartouche tient en 28 pixels et que la
         zone des états n'occupe pas le pied de la colonne. Ces deux réglages changent, et le
         défilement se relit comme une fin de liste. -->
    <div class="-mx-1 min-h-0 flex-1 overflow-y-auto px-1">
      <ul class="flex flex-col gap-1">
        <li v-for="row in rows" :key="row.key">
          <!-- LA BULLE S'OUVRE AU SURVOL **ET À L'APPUI**, et c'est le mode « survol » du panneau
               qui donne les deux : une infobulle ne répond qu'à la souris et au clavier, or sous
               1280 px cette colonne retombe en pied de fiche, sur des écrans qui n'ont pas de
               souris. Un seul composant sert les deux cas — donc le même rendu au serveur et dans
               le navigateur, sans bascule sur le type de pointeur.
               TOUTE CARTOUCHE EN A UNE, sans exception : la bulle redonne d'abord la ligne entière,
               que la largeur coupe souvent. Une cartouche sur laquelle il ne se passe rien se lit
               comme un écran cassé, et l'ancien pointillé n'existait que pour désigner d'avance la
               minorité qui répondait.
               ELLE N'AJOUTE AUCUN ARRÊT CLAVIER : la cartouche reste un bloc, que rien ne rend
               focalisable. Huit critères feraient huit tabulations à traverser avant « Retenir »,
               sur chaque profil d'une pile qui en compte des centaines — et le texte à l'écoute,
               lui, porte déjà tout. -->
          <!-- ELLE S'ALIGNE SUR LE BORD DE LA CARTOUCHE, et sa largeur est celle de la cartouche
               (cf. la feuille de style) : une bulle centrée et plus étroite flotte à côté de la
               ligne, celle-ci se lit comme lui appartenant. Le RETOURNEMENT haut/bas selon la place
               restante est le comportement d'usine de la bibliothèque — il ne se code pas, et
               surtout il ne se réécrit pas.
               La mise en forme du contenu descend d'un cran, sur l'élément de ce gabarit : la boîte
               de la bulle appartient à la bibliothèque, et nos jetons ne l'atteignent qu'en la
               laissant épouser ce qu'on lui donne. -->
          <UPopover
            mode="hover"
            enable-touch
            :open-delay="DELAI_BULLE"
            :close-delay="0"
            :content="{ align: 'start' }"
          >
            <!-- LA LIGNE UNIQUE : le rond, d'où vient le critère, le critère, et le « quand » du
                 métier. Le fond reste NEUTRE — la charte réserve la couleur aux états et l'interdit
                 sur les données, « vingt lignes colorées ne hiérarchisent rien, elles saturent ».
                 TROIS PALIERS QUI S'ENFONCENT, et c'est ce qui donne l'ordre de lecture : la fiche
                 du profil est posée, la colonne du verdict recule d'un demi-ton, la cartouche
                 descend au fond. Elle est CREUSÉE dans la colonne, pas soulevée dessus — un
                 renfoncement se lit comme une case à remplir, un relief comme un objet à saisir, et
                 ici on relève un constat.
                 AU SURVOL ELLE REMONTE AU NIVEAU DE LA COLONNE, et c'est le seul signe qu'elle
                 réagit depuis que le pointillé est parti. `data-state` prend le relais au doigt, où
                 il n'y a pas de survol à montrer. -->
            <div
              class="flex items-center gap-element rounded-lg bg-bg px-2 py-1 transition-colors hover:bg-surface data-[state=open]:bg-surface"
            >
              <!-- Sans profil à confronter, pas de rond : il n'y a rien à trancher. -->
              <span
                v-if="row.disc"
                class="flex size-5 flex-none items-center justify-center rounded-full"
                :class="row.disc.tint"
              >
                <UIcon :name="row.disc.icon" class="size-3" aria-hidden="true" />
              </span>

              <!-- D'OÙ VIENT LA LIGNE — « Mot-clé », « Poste recherché » —, À L'ÉCOUTE SEULEMENT.
                   Écrite devant le critère, elle ne repérait plus rien : une recherche pose ses
                   mots-clés par paquets, et quatre lignes sur six annonçaient le même mot avant de
                   dire quoi que ce soit. Elle reste dans la bulle, où on la consulte quand le doute
                   existe, et ici pour qui écoute la page — sans quoi « scrum » s'énonce sans qu'on
                   sache de quoi il s'agit. Un texte caché ne coûte aucun pixel.
                   INTERLIGNE SERRÉ : au corps du texte, la ligne porte l'interligne d'un paragraphe
                   — six pixels de vide pour une ligne unique, six fois de suite. -->
              <span v-if="row.label" class="sr-only">{{ row.label }}</span>

              <bdi class="min-w-0 flex-1 truncate font-medium leading-tight" :class="row.nameTint">
                {{ row.wanted }}
              </bdi>

              <!-- MÊME INTERLIGNE QUE LE CRITÈRE : à l'interligne d'un paragraphe, ce mot rend la
                   cartouche trois pixels plus haute que ses voisines, et la liste ondule selon les
                   lignes qui portent un « quand ». -->
              <span
                v-if="row.tenure"
                class="flex-none text-small leading-tight"
                :class="row.tenure.tint"
              >
                {{ row.tenure.text }}
              </span>

              <!-- CE QUE LE PROFIL PORTE, pour qui n'a ni souris ni écran tactile : la bulle sert le
                   confort, elle ne détient pas l'information — et le panneau de survol, à la
                   différence d'une infobulle, n'est pas annoncé comme la description de sa cible. -->
              <span v-if="row.found" class="sr-only">{{ row.found }}</span>
            </div>

            <!-- LA BULLE DIT D'ABORD D'OÙ VIENT LA LIGNE — le seul endroit visible où ce repère
                 subsiste —, puis ce qu'on cherchait, puis ce que le profil porte en face. C'est ce
                 qui rend chaque cartouche survolable, y compris celles dont le critère manque : il
                 reste toujours la provenance à donner, et le critère à rendre entier quand la
                 largeur l'a coupé. -->
            <template #content>
              <div class="search-match-list-tip flex flex-col gap-0.5 p-element">
                <p v-if="row.label" class="text-caption text-muted">{{ row.label }}</p>
                <p class="font-medium">{{ row.asked }}</p>

                <!-- L'EXTRAIT SE COUPE, et c'est le corollaire d'une largeur figée : ce que le
                     profil porte peut être une accroche de trois phrases — 49 caractères en médiane,
                     752 au maximum —, et sans coupe la bulle se déplie en colonne de texte au milieu
                     de l'écran. Le retournement ne sauve pas de ça : il déplace, il ne rétrécit pas.
                     Trois lignes et non une : sur une accroche, le terme cherché arrive en milieu de
                     phrase, et une ligne unique le laisse hors champ une fois sur deux. -->
                <p v-if="row.found" class="line-clamp-3 text-small text-muted">{{ row.found }}</p>
              </div>
            </template>
          </UPopover>
        </li>
      </ul>
    </div>

    <!-- CE QUE L'ABSENCE VEUT DIRE, et c'est ce qui rend le gris tenable : un critère non trouvé ne
         dit pas que le candidat ne sait pas faire, il dit que son CV public n'en parle pas. Sans
         cette phrase, six lignes sans coche se lisent comme un mauvais profil.
         EN PIED DE COLONNE : c'est une réserve sur tout ce qui précède, pas une ligne de liste. Elle
         reste CONDITIONNELLE — absente quand le profil coche tout —, et la liste qui la précède
         récupère alors sa hauteur. -->
    <p
      v-if="incomplete"
      class="flex flex-none items-start gap-element rounded-lg border border-dashed border-line px-2 py-1.5 text-caption text-muted"
    >
      <UIcon name="i-lucide-info" class="mt-0.5 size-4 flex-none" aria-hidden="true" />
      {{ $t('search.viewer.panel.publicResumeOnly') }}
    </p>
  </div>
</template>

<style scoped>
/* LA BULLE FAIT LA LARGEUR DE LA CARTOUCHE, et la bibliothèque la donne elle-même :
   `--reka-popper-anchor-width` est la largeur mesurée de l'élément survolé, posée sur la bulle à
   chaque ouverture. Rien n'est calculé ici, la valeur est lue.

   Écrit en CSS et non en utilitaire : une largeur qui lit une variable n'a pas d'écriture Tailwind
   qui ne soit pas une valeur arbitraire — même raison qu'`UiInfoHint`, qui borne la sienne pareil.

   POSÉ SUR CE BLOC ET NON SUR LA BOÎTE DE LA BULLE : la boîte appartient à la bibliothèque, elle ne
   porte pas la portée de ce composant et le style ne l'atteindrait pas. Elle épouse donc son unique
   enfant, qui porte aussi le rythme intérieur — sans quoi la bulle dépasserait la cartouche de deux
   fois son propre remplissage. */
.search-match-list-tip {
  width: var(--reka-popper-anchor-width);
}
</style>
