<script setup lang="ts">
// Coque « dashboard » de l'espace admin : SHELL plein écran (sidebar + topbar) + contenu.
// Layout Nuxt STANDARD → les pages font `definePageMeta({ layout: 'dashboard' })`.
//
// La structure des zones A/B/C/D (fixe/scroll) vit dans <DashboardBody>, rendu par chaque page
// dans le slot par défaut. La navigation vit dans <SidebarNav>, partagée par la sidebar
// desktop et le tiroir mobile (<MobileDrawer>).
//
// PLEIN ÉCRAN : `h-dvh` + `overflow-hidden`, aucun scroll de page. Coque bornée
// (`max-w-shell`) puis centrée. < lg (1024px) : sidebar masquée → accès nav par le tiroir mobile.
//
// LE REPÈRE PRINCIPAL EST ICI, pas dans les pages — cf. `base/app/layouts/README.md`.
//
// La coque POSSÈDE le repli de la colonne de navigation, et <SidebarNav> ne fait que le recevoir et
// demander sa bascule : c'est la coque qui décide de sa propre largeur, et un composant n'a pas à
// muter un état global. Le repli ne vaut qu'ici — le tiroir mobile rend la même navigation, toujours
// dépliée.
const { collapsed, toggle } = useSidebar()
</script>

<template>
  <!-- ESPACEMENT : l'échelle du socle, comme partout ailleurs — `p-element`, `gap-element`.
       Cette coque a longtemps porté 4 px fixes et un `--ui-radius` rabaissé, sur l'argument qu'une
       COUTURE — deux panneaux à peine détachés — n'a pas de raison de s'écarter sur un grand écran,
       là où une gouttière le doit. L'argument tient ; sa mise en œuvre non. `design-system.md`
       pose que le rythme d'espacement est FERMÉ, et qu'un besoin non couvert s'obtient en ajoutant
       un palier à `sizing.css`, jamais en improvisant dans un écran. Ce qui a été écrit ici était
       une improvisation, et elle faisait diverger l'admin de l'entreprise sur un même produit.
       Si le besoin d'une couture plus serrée revient, il passera par un palier du socle, partagé.
       Le rayon suivait cet écart et disparaît avec lui : Nuxt UI dérive toute l'échelle de rayons
       de `--ui-radius` (`--radius-lg: calc(--ui-radius * 2)`), donc l'écrasement ne touchait pas
       que les panneaux — il rabaissait aussi boutons, champs et menus, de 8 px à 6. -->
  <div class="mx-auto flex h-dvh w-full max-w-shell gap-element overflow-hidden p-element">
    <!-- SIDEBAR (desktop ≥ lg) -->
    <aside
      class="hidden flex-none overflow-y-auto rounded-lg bg-surface p-block lg:block"
      :class="collapsed ? 'w-nav-rail' : 'w-nav'"
    >
      <SidebarNav collapsible :collapsed="collapsed" @toggle="toggle" />
    </aside>

    <!-- COLONNE PRINCIPALE : topbar + contenu (<DashboardBody>) -->
    <div class="flex min-w-0 flex-1 flex-col gap-element">
      <Topbar />

      <!-- LE REPÈRE PRINCIPAL, autour du seul contenu de route : la barre latérale et la barre
           haute n'en font pas partie. `min-h-0 flex-1` parce que la coque est bornée à la fenêtre
           et que <DashboardBody> s'étire dans ce qui reste — sans eux, ce niveau supplémentaire
           casse la chaîne et les zones défilantes s'effondrent. `min-h-0` neutralise du même coup
           le `min-h` de thème d'`UMain`, qui suppose un header Nuxt UI de 4 rem et ferait déborder
           une coque `h-[100dvh]` — cf. `base/app/layouts/README.md`. -->
      <UMain :ui="{ base: 'flex min-h-0 flex-1 flex-col' }">
        <slot />
      </UMain>
    </div>

    <!-- Tiroir de navigation mobile (< lg) -->
    <MobileDrawer />
  </div>
</template>
