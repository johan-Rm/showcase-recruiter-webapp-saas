<script setup lang="ts">
// CE PROFIL FACE AUX CRITÈRES. Ce qui a été demandé, le verdict, et ce que le profil porte en face.
//
// ELLE NE RETOUCHE RIEN : la console est un outil de CONSULTATION — on y juge des profils, on
// retient, on écarte —, et un geste qui rejoue la recherche n'a pas sa place au milieu d'un tri. Une
// croix de retrait y porterait en prime le même dessin que la croix du verdict « non trouvé », deux
// centimètres plus loin : un constat et une action irréversible dessinés pareil. On retire un
// critère là où l'on modifie la recherche, pas ici.
//
// C'est le seul endroit de l'écran qui dise la correspondance : la carte, elle, surligne ce qu'elle
// trouve là où il est, et n'a pas de place pour une absence.
//
// Le libellé reste FACTUEL — « non trouvé », jamais un jugement de compétence : la donnée est
// déclarative, et affirmer une absence ferait écarter de bons profils.
import { renderValue } from '#services/search/criteria'

import type { CriterionOutcome } from '#services/search/criteria'

type Props = {
  /** Déjà confrontés et ordonnés : le composant affiche, il ne compare pas. */
  outcomes: CriterionOutcome[]
}

defineProps<Props>()

const { t } = useLocale()

/**
 * La teinte du verdict — et elle ne touche QUE le signe et le mot du verdict.
 *
 * La charte réserve la couleur aux **états** et l'interdit sur les données : « vingt lignes colorées
 * ne hiérarchisent rien, elles saturent ». Un signe et un mot sont deux éléments ponctuels, proches
 * d'un badge ; la ligne entière serait une colonne de teintes. C'est ce qui permet de teinter sans
 * amender la charte.
 *
 * `null` — aucun profil à confronter — ne se teinte pas : il n'y a pas de verdict à colorer.
 */
const verdictTint = (matched: boolean | null): string =>
  null === matched ? 'text-muted' : matched ? 'text-verdict-met' : 'text-verdict-missed'
</script>

<template>
  <!-- ELLE PREND LE RESTE ET ELLE DÉFILE. Trois blocs se disputent la hauteur de la colonne et
       c'est celui-ci qui absorbe : `min-h-0` avec `flex-1`, jamais `flex-none` sans hauteur
       maximale — sous cette forme elle chasse les autres blocs hors de la colonne dès six
       critères. -->
  <div v-if="outcomes.length" class="search-v1-match-list flex min-h-0 flex-1 flex-col gap-element">
    <h2 class="flex-none text-label text-muted">{{ $t('search.viewer.panel.matches') }}</h2>

    <ul class="-mx-1 min-h-0 flex-1 overflow-y-auto px-1">
      <li
        v-for="outcome in outcomes"
        :key="`${outcome.criterion.facet}-${outcome.criterion.value}`"
        class="group flex items-baseline gap-element py-0.5 text-small"
      >
        <span class="flex min-w-0 flex-1 items-baseline gap-1">
          <span v-if="outcome.labelKey" class="flex-none text-muted">
            {{ $t(outcome.labelKey) }}
          </span>
          <bdi class="truncate font-medium">{{ renderValue(outcome.wanted, t) }}</bdi>
        </span>

        <!-- CE QUE LE PROFIL PORTE en face — absent quand la carte n'a rien de comparable, et le
             verdict prend alors sa place plutôt que de laisser la ligne muette.
             Il reste NEUTRE : une valeur de profil est une donnée, et la charte ne colore pas une
             donnée. Seul le signe la qualifie.
             TRONQUÉ : un mot-clé peut être trouvé dans l'intitulé entier du profil. -->
        <bdi v-if="outcome.found" class="max-w-[45%] truncate text-muted">
          {{ renderValue(outcome.found, t) }}
          <span class="sr-only">
            · {{ $t(`search.viewer.panel.${outcome.matched ? 'found' : 'notFound'}`) }}
          </span>
        </bdi>

        <!-- Le verdict s'efface devant le « quand » : « poste actuel » dit déjà que le critère est
             satisfait, et le redire mangeait le libellé dans une colonne étroite. -->
        <span
          v-else-if="null !== outcome.matched && !outcome.tenure"
          class="flex-none"
          :class="verdictTint(outcome.matched)"
        >
          {{ $t(`search.viewer.panel.${outcome.matched ? 'found' : 'notFound'}`) }}
        </span>

        <!-- QUAND il l'exerce — la précision temporelle du métier reconnu. Elle reste
             NEUTRE : ce n'est pas un verdict, c'est un fait sur le profil. -->
        <span v-if="outcome.tenure" class="flex-none text-muted">
          {{ outcome.found ? '· ' : '' }}{{ $t(`search.viewer.panel.tenure.${outcome.tenure}`) }}
        </span>

        <!-- LE SIGNE FERME LA LIGNE, il ne l'ouvre pas : en tête, il annoncerait son verdict avant
             qu'on sache de quel critère il parle, et comme le mot se lit à l'autre bout, la même
             information paraîtrait deux fois, aux deux extrémités.
             EN COLONNE, parce que le mot « Trouvé » ne paraît qu'une ligne sur trois — ailleurs la
             ligne finit par une valeur tronquée ou par « poste actuel ». Collé au mot, le signe
             changerait de place à chaque ligne ; `flex-none` en bout de `flex-1` l'aligne, et on
             compte encore les ✓ d'un coup d'œil.
             Il porte la teinte, jamais elle seule le sens : le verdict reste écrit en toutes
             lettres, ou lisible par les lecteurs d'écran quand une valeur prend sa place.
             Sans profil à confronter, pas de signe : il n'y a rien à trancher. -->
        <UIcon
          v-if="null !== outcome.matched"
          :name="outcome.matched ? 'i-lucide-check' : 'i-lucide-x'"
          :class="verdictTint(outcome.matched)"
          class="h-3.5 w-3.5 flex-none translate-y-0.5"
          aria-hidden="true"
        />
      </li>
    </ul>
  </div>
</template>
