<script setup lang="ts">
// L'agencement suit l'AXE DU CONTENEUR, pas l'espace : le rail admin empile parce qu'il est vertical
// et étroit, la topbar du cockpit aligne parce qu'elle est une bande. Indexer sur `useSpace()`, qui
// est pourtant à portée, aurait posé la décision sur la mauvaise clé — le même rail sert aussi le
// tiroir mobile de l'admin.
// Une prop et non deux composants : ce qui varie est l'agencement de deux enfants, quand le lien
// lui-même — destination, nom accessible, anneau de focus, `--logo-h` — ne varie pas. C'est la
// moitié coûteuse, et un dédoublement la condamnerait à diverger.
type BrandOrientation = 'horizontal' | 'vertical'

const AXIS: Record<BrandOrientation, { root: string; badge: string }> = {
  // Gouttière en pas brut, seule de son espèce ici : `gap-element` déplacerait le rendu de l'admin,
  // du public et du candidat, que cette variante doit laisser intact.
  vertical: { root: 'flex-col items-start gap-1.5', badge: 'ml-[calc(var(--logo-h)*1.375)]' },
  horizontal: { root: 'flex-row items-center gap-element', badge: '' },
}

// COMPACT : le picto seul, sans le mot-symbole ni le badge — ce que réclame une colonne réduite à
// la largeur d'une icône. Une prop et non un composant à part, pour la raison ci-dessus : le lien,
// son nom accessible et son anneau de focus ne varient pas, et c'est la moitié coûteuse.
// Le picto reste décoratif : c'est le LIEN qui porte le nom, comme dans la variante complète — son
// propre commentaire réclamait qu'on lui en donne un le jour où il servirait seul, c'est fait ici.
const props = defineProps<{ orientation: BrandOrientation; compact?: boolean }>()

const space = useSpace()
const { localePath } = useLocale()

const axis = computed(() => AXIS[props.orientation])
</script>

<template>
  <!-- Le logo EST le retour à l'accueil, et depuis qu'« Accueil » a quitté le menu du compte il en
       est le seul chemin. Il doit donc se comporter en lien : ce qu'il annonce dit sa DESTINATION et
       pas seulement la marque, `aria-current` marque la page courante, et l'anneau de focus le rend
       atteignable au clavier — un logo qu'on ne peut pas tabuler n'est pas une navigation. -->
  <NuxtLink
    :to="localePath('/')"
    class="layout-brand inline-flex rounded-lg text-text no-underline [--logo-h:1.5rem] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent"
    :class="axis.root"
    :aria-label="$t('nav.brandHome', { app: $t('app.name') })"
  >
    <!-- La hauteur du logo est une VARIABLE parce que le badge en dépend : les deux ne peuvent plus
         diverger sans qu'on le voie. -->
    <LayoutLogomark v-if="props.compact" class="h-[var(--logo-h)] w-auto" />
    <LayoutLogo v-else class="h-[var(--logo-h)] w-auto" />

    <!-- Le mot commence à x=44 dans un logo haut de 32 : le badge vertical suit ce rapport.
         L'alignement est physique car le logo garde sa composition dans les langues RTL. -->
    <UiBadge v-if="!props.compact" :class="axis.badge">{{ $t(`space.${space}`) }}</UiBadge>
  </NuxtLink>
</template>
