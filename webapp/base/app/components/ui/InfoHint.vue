<script setup lang="ts">
// Bulle d'aide accessible : s'ouvre au survol, au focus clavier ET au tap — jamais hover-only,
// inutilisable au tactile.
//
// Le PLACEMENT est confié à la bibliothèque, et JAMAIS écrit à la main. Ancré au bord gauche de
// l'icône, il pousse vers la droite sans regarder si la place existe — 19 bulles sur 27 sortent de
// l'écran sur un téléphone ; et rendue DANS le contenu, la bulle se fait rogner par le premier
// conteneur qui défile verticalement, puisqu'en CSS `overflow-y: auto` fait calculer
// `overflow-x: auto`. `UPopover` traite les deux : il se retourne au contact d'un bord
// (`collisionPadding`), et il se rend hors du flux, à la racine du document.
//
// `mode="hover"` et non le mode clic par défaut : le survol est le geste attendu sur un libellé, et
// `enableTouch` rend le même composant utilisable au doigt. Le RATTACHEMENT au déclencheur, lui,
// reste écrit ici — une carte de survol n'en pose aucun, et un lecteur d'écran perdrait le lien
// entre le bouton et sa définition.
interface Props {
  /** Texte de l'infobulle, déjà résolu (p. ex. depuis le registre des métriques). */
  text: string
}

defineProps<Props>()

const tipId = useId()
</script>

<template>
  <UPopover
    class="ui-info-hint inline-flex align-middle"
    mode="hover"
    enable-touch
    :content="{ side: 'bottom', align: 'start' }"
  >
    <template #default="{ open }">
      <button
        type="button"
        class="inline-flex size-4 cursor-help items-center justify-center rounded-full text-muted transition-colors hover:text-accent focus-visible:text-accent focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
        :aria-label="$t('a11y.moreInfo')"
        :aria-describedby="open ? tipId : undefined"
      >
        <svg
          viewBox="0 0 16 16"
          class="size-3.5"
          fill="none"
          stroke="currentColor"
          stroke-width="1.4"
          aria-hidden="true"
        >
          <circle cx="8" cy="8" r="6.25" />
          <path d="M8 7.25v3.5" stroke-linecap="round" />
          <circle cx="8" cy="4.75" r="0.6" fill="currentColor" stroke="none" />
        </svg>
      </button>
    </template>

    <template #content>
      <p :id="tipId" role="tooltip" class="ui-info-hint-tip p-element text-caption text-text">
        {{ text }}
      </p>
    </template>
  </UPopover>
</template>

<style scoped>
/* LA LARGEUR est bornée deux fois, et il faut les deux.
   `--container-readable` est la mesure de lecture du socle (~65-75 caractères) : au-delà, l'œil
   perd la ligne. `--reka-popper-available-width` est la place que la bibliothèque a réellement
   trouvée après s'être retournée — sans elle, une bulle à la mesure de lecture fait 620 px et
   déborde d'un téléphone, ce que le retournement seul ne corrige pas : il déplace, il ne rétrécit
   pas. Mesuré : 16 bulles sur 27 encore hors écran à 390 px avec la seule première borne.

   En CSS et non en utilitaire : `min()` de deux variables n'a pas d'écriture Tailwind qui ne soit
   pas une valeur arbitraire — exactement ce que l'interdit n°3 refuse. Aucune VALEUR n'est écrite
   ici, les deux bornes sont lues. */
.ui-info-hint-tip {
  max-width: min(var(--container-readable), var(--reka-popper-available-width, 100vw));
}
</style>
