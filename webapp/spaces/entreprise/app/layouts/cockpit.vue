<template>
  <div
    data-layout="cockpit"
    class="mx-auto flex h-dvh w-full max-w-shell flex-col gap-element overflow-hidden p-element"
  >
    <header
      data-section="topbar"
      class="flex h-[clamp(54px,6.5vh,76px)] flex-none items-center justify-between gap-element rounded-lg bg-surface px-block"
    >
      <LayoutBrand orientation="horizontal" />
      <Topbar />
    </header>

    <UMain data-section="content" :ui="{ base: 'flex min-h-0 flex-1 flex-col' }">
      <slot />
    </UMain>
  </div>
</template>
