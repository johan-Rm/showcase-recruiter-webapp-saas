<script setup lang="ts">
// Taux de remplissage du vivier : part des profils pour lesquels on DÉTIENT chaque champ. Données
// agrégées (aucun profil nommé) fournies par /admin/vivier — mêmes chiffres que le rapport de
// remplissage. Lecture seule.
//
// UNE PROPORTION SE LIT EN LONGUEUR, pas en chiffres alignés : dix nombres obligent à comparer de
// tête, dix barres montrent d'un coup où le vivier est plein et où il est vide. Le pourcentage
// reste écrit à côté — la barre ne porte jamais l'information seule.
//
// La bande est FIXE dans la coque : elle réserve sa hauteur même sans données, sinon le tableau
// démarre plus haut au premier affichage puis redescend quand l'agrégat arrive.
import type { components } from '#shared/types/api'

type FillRate = components['schemas']['AdminVivier']['fillRate']

defineProps<{ items: FillRate }>()
</script>

<template>
  <section class="fill-rate-section flex flex-col gap-element">
    <h2 class="text-label text-muted">{{ $t('admin.candidates.fillRate.title') }}</h2>

    <!-- Les mesures ne se replient JAMAIS sur deux rangées : la bande est fixe, une seconde rangée
         la doublerait au détriment du tableau. Faute de place elles défilent latéralement, et le
         défilement reste dans la bande. Sans `min-w-0`, chaque mesure garde la largeur de son
         libellé — c'est ce qui déclenche le défilement au lieu de les écraser. -->
    <dl class="flex gap-block overflow-x-auto">
      <div
        v-for="item in items"
        :key="item.key"
        class="flex flex-1 flex-col gap-element"
        :title="$t('admin.candidates.fillRate.detail', { filled: item.filled, total: item.total })"
      >
        <!-- LE LIBELLÉ ET SA VALEUR SE TOUCHENT, et l'écart se met ENTRE les mesures : espacés à
             l'intérieur, ils se lisent de travers — le pourcentage se rattache au libellé suivant,
             qui est alors le plus proche. Mesuré : 46 px du sien contre 10 px du voisin. -->
        <div class="flex items-baseline gap-element">
          <dt class="text-caption whitespace-nowrap text-muted">
            {{ $t(`admin.candidates.fillRate.field.${item.key}`) }}
          </dt>
          <dd class="text-h3 font-semibold tabular-nums text-text">
            {{ fillRatePercent(item.filled, item.total) }}&nbsp;%
          </dd>
        </div>
        <!-- La barre est DÉCORATIVE : le pourcentage juste au-dessus porte l'information, et un
             lecteur d'écran l'annoncerait deux fois. Une seule couleur pour les dix — c'est la
             longueur qui compare, la seule lecture qui tienne sans distinguer les couleurs. -->
        <dd class="fill-rate-section__gauge">
          <UProgress
            :model-value="fillRatePercent(item.filled, item.total)"
            size="sm"
            aria-hidden="true"
            tabindex="-1"
          />
        </dd>
      </div>
    </dl>
  </section>
</template>

<style scoped>
/* LA HAUTEUR EST RÉSERVÉE, pas imposée : l'agrégat arrive par le registre de démarrage et non par
   le rendu de la page, donc la bande existe un instant sans contenu. Sans ce plancher elle
   s'effondre, et le tableau saute de soixante pixels à l'arrivée des chiffres.
   Le palier de bande d'actions, et non celui de gouttière : on y lit trois niveaux superposés —
   étiquette, pourcentage, barre — là où une gouttière ne porte qu'une ligne. */
.fill-rate-section {
  min-height: var(--band-action);
}

/* LA JAUGE SUIT LE SENS DE LECTURE. Le composant de la bibliothèque remplit toujours depuis la
   gauche — il retourne son animation indéterminée en écriture arabe, mais pas son remplissage. Une
   jauge qui part du mauvais bord se lit à l'envers : 86 % y ressemble à 14 %.

   Le miroir est posé sur NOTRE conteneur, et pas sur la piste de la bibliothèque : celle-ci porte sa
   transformation EN LIGNE, qu'aucune feuille de style ne peut reprendre sans `!important`. Le
   contenu retourné est un aplat uni, le miroir ne se voit donc pas. */
[dir='rtl'] .fill-rate-section__gauge {
  transform: scaleX(-1);
}
</style>
