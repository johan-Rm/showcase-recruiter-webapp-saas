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
type Props = {
  total: number
  seenCount: number
  loadedCount: number
  dismissedCount: number
  /** Le nombre de retenus : la LISTE vit dans la Table, la console n'en garde que l'écho. */
  favoriteCount: number
  /** L'adresse de l'autre outil — la colonne l'affiche, elle ne la construit pas. */
  tableTo: string
}

defineProps<Props>()

const emit = defineEmits<{ review: [] }>()
</script>

<template>
  <div class="search-v2-control-panel relative flex h-full min-h-0 flex-col">
    <!-- Le CONTENU porte la marge intérieure, et pas la colonne : le pied doit toucher le bord, une
         marge sous lui le transformerait en bouton posé dans un cadre. -->
    <div class="flex min-h-0 flex-1 flex-col gap-block p-block">
      <SearchV2ProgressHeader
        :total="total"
        :seen-count="seenCount"
        :loaded-count="loadedCount"
        :dismissed-count="dismissedCount"
        :favorite-count="favoriteCount"
        @review="emit('review')"
      />
    </div>

    <!-- LA SORTIE, en PIED PLEIN collé au bord : les deux écrans sont les deux moments d'une
         recherche, et la transition appartient à l'endroit où l'on voit ce qu'on emporte. C'est le
         seul aplat accent de l'écran — les accès de la carte y ont renoncé pour que celui-ci veuille
         encore dire quelque chose.
         L'ÉTOILE, et c'est la MÊME que le geste « retenir » de la fiche profil : le lien
         se lit alors sans légende, on reconnaît l'icône du geste qu'on vient de poser cinq fois. Elle
         ne pose pas une seconde couleur — le bouton reste le seul aplat accent. -->
    <UButton :to="tableTo" icon="i-lucide-star" block size="lg" class="flex-none rounded-none">
      {{ $t('page.entreprise.table.title') }}
    </UButton>
  </div>
</template>
