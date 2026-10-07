<script setup lang="ts">
// LA COLONNE de la visionneuse — elle ASSEMBLE, elle n'affiche presque rien elle-même : où j'en
// suis dans le tri, puis la sortie vers l'autre outil.
//
// CE QU'ELLE N'ASSEMBLE PAS, et pourquoi : le verdict d'un profil face aux critères se lit dans la
// FICHE de ce profil, seul endroit où il désigne sans ambiguïté celui dont il parle ; la liste des
// retenus vit dans la Table ; le sélecteur d'ordre gouverne l'écran entier et vit dans la gouttière —
// ici, il s'arrogerait un rang de section pour le geste le plus rare des trois ; le nom de la
// recherche vit dans la barre haute, où il se lit en entier et survit au masquage de cette colonne
// sur petit écran.
//
// Passive : elle reçoit tout par props et ne fait que relayer les intentions de ses enfants. Les
// compteurs viennent de `useSearch` — un composant ne combine pas une source globale avec une
// transformation (`base/app/README.md` — « Logique métier dans les composants »).
//
// L'`<aside>` et sa place dans la grille appartiennent à la page ; l'aplat et les coins à la console
// qui l'onglette. Ce qui reste ici est à elle : remplir la hauteur qu'on lui donne, et coller son
// pied au bord.
//
// ELLE ASSEMBLE AUSSI L'AFFINAGE, et c'est le seul endroit de l'écran où il pouvait vivre : il se
// consulte en parcourant, entre l'avancement qu'il ne change pas et la sortie qu'il prépare. Ce
// qu'il pose ne rejoint jamais la recherche — les critères se changent sur l'écran de modification.
import { emptyRefineForm, isRefined } from '#services/search/refine'

import type { RefineForm } from '#services/search/refine'
import type { CandidateCard } from '#shared/types/candidate-card'

type Props = {
  seenCount: number
  dismissedCount: number
  /** Le nombre de retenus : la LISTE vit dans la Table, la console n'en garde que l'écho. */
  favoriteCount: number
  /** L'adresse de l'autre outil — la colonne l'affiche, elle ne la construit pas. */
  tableTo: string
  /** Le jeu ENTIER, sur quoi l'affinage compte — jamais la seule page chargée. */
  cards: readonly CandidateCard[]
  today: Date
  /** Ce que l'affinage laisse, et ce que le jeu compte : « 7 sur 32 ». */
  refinedCount: number
}

defineProps<Props>()

const emit = defineEmits<{ review: [] }>()

const refine = defineModel<RefineForm>('refine', { required: true })

const refined = computed(() => isRefined(refine.value))
const clear = (): void => {
  refine.value = emptyRefineForm()
}
</script>

<template>
  <div class="search-control-panel relative flex h-full min-h-0 flex-col">
    <!-- LA MARGE LATÉRALE APPARTIENT À CHAQUE BLOC, jamais au cadre qui les empile — et c'est la
         zone défilante qui l'impose. Portée par le cadre, elle rentrait la zone de dix-neuf pixels
         de chaque côté, et sa barre de défilement avec elle : une barre posée au milieu de rien, que
         rien à l'écran n'expliquait. Un bloc qui doit toucher le bord ne peut pas vivre dans un
         cadre qui l'en écarte — le pied « Ma sélection » l'avait déjà résolu en sortant du cadre,
         la zone défilante le résout en récupérant la marge de son contenu.
         LA MARGE VERTICALE, elle, reste ici : elle n'empêche aucune barre d'atteindre quoi que ce
         soit, et la répartir sur les blocs ferait trois réglages à tenir d'accord pour rien. -->
    <div class="flex min-h-0 flex-1 flex-col gap-block py-block">
      <SearchProgressHeader
        class="px-block"
        :seen-count="seenCount"
        :dismissed-count="dismissedCount"
        :favorite-count="favoriteCount"
        @review="emit('review')"
      />

      <!-- SEULS LES VOLETS DÉFILENT : l'avancement au-dessus et le décompte en dessous restent en
           vue, sinon le nombre qui répond au filtre qu'on vient de poser sort de l'écran au moment
           où on le pose. `min-h-0` avec `flex-1`, jamais `flex-none` — sous cette forme les volets
           chassent les autres blocs hors de la colonne dès qu'on en déplie deux. -->
      <div class="search-control-panel-scroll min-h-0 flex-1 overflow-y-auto">
        <!-- LE RETRAIT EST DEDANS, et c'est tout l'objet de la manœuvre : la zone occupe la largeur
             entière de la console, son contenu garde le même retrait qu'avant, et la barre se pose
             au bord. Le retrait est posé ICI plutôt que dans le panneau d'affinage — c'est celui qui
             PLACE un composant qui décide de sa marge, pas le composant lui-même. -->
        <div class="px-block">
          <SearchRefinePanel v-model="refine" :cards="cards" :today="today" />
        </div>
      </div>

      <!-- LE DÉCOMPTE N'EXISTE QUE SI L'ON A AFFINÉ : sans filtre, il répéterait le rang de la barre
           haute, qui dit déjà la taille du jeu.
           IL PORTE SON PROPRE RETRAIT, parce que c'est là qu'on lit le résultat : chercher dans quel
           volet on avait coché demande de se souvenir de ce qu'on vient de faire.
           SON TRAIT S'ARRÊTE AVANT LES BORDS : il souligne ce bloc, il ne coupe pas la colonne. Mené
           au bord, il se lirait comme la séparation qui pose le pied juste en dessous, et découperait
           la console en trois bandes là où il n'y a qu'un décompte souligné. -->
      <div
        v-if="refined"
        class="mx-block flex flex-none items-center justify-between gap-2 border-t border-line pt-element"
      >
        <p class="min-w-0 text-caption text-muted">
          {{ $t('search.refine.kept', { count: refinedCount, total: cards.length }) }}
        </p>
        <UButton size="xs" variant="ghost" color="neutral" @click="clear">
          {{ $t('search.refine.clear') }}
        </UButton>
      </div>
    </div>

    <!-- LA SORTIE, en PIED PLEIN collé au bord : les deux écrans sont les deux moments d'une
         recherche, et la transition appartient à l'endroit où l'on voit ce qu'on emporte. C'est le
         seul aplat accent de l'écran — les accès de la carte y ont renoncé pour que celui-ci veuille
         encore dire quelque chose.
         L'ÉTOILE, et c'est la MÊME que le geste « retenir » de la fiche profil : le lien
         se lit alors sans légende, on reconnaît l'icône du geste qu'on vient de poser cinq fois. Elle
         ne pose pas une seconde couleur — le bouton reste le seul aplat accent. -->
    <UButton
      :to="tableTo"
      icon="i-lucide-star"
      variant="ghost"
      block
      size="lg"
      :ui="{ leadingIcon: 'size-4' }"
      class="flex-none rounded-none bg-accent/12 hover:bg-accent/20"
    >
      {{ $t('page.entreprise.table.title') }}
    </UButton>
  </div>
</template>

<style scoped>
/* LA PLACE DE LA BARRE EST RÉSERVÉE, QU'ELLE S'AFFICHE OU NON.
   Là où la barre occupe une gouttière — quinze pixels, mesurés ici — son apparition décalerait
   latéralement toute la colonne, c'est-à-dire pile au moment où l'on déplie un volet, et le texte
   viendrait se coller contre elle. Là où elle flotte par-dessus le contenu, la réservation ne coûte
   rien : le mot-clé ne s'applique qu'aux barres qui prennent de la largeur.
   CE QU'ELLE COÛTE : le contenu perd cette gouttière à droite, donc son retrait y est plus grand
   qu'à gauche. Assumé — un décalage au premier déroulé se voit, une asymétrie fixe ne se voit pas.
   `stable` suit le sens de lecture : la gouttière passe à gauche en arabe, sans rien à écrire.

   En CSS et non en utilitaire : le kit n'en a pas, et aucune VALEUR n'est écrite ici — c'est un
   mot-clé, pas une mesure. */
.search-control-panel-scroll {
  scrollbar-gutter: stable;
}
</style>
