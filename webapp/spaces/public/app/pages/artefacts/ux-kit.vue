<script setup lang="ts">
import type { TableColumn } from '@nuxt/ui'

// Galerie des composants UX du socle (kitchen sink) — référence vivante du design system.
// La fermeture en production est portée par `middleware/artefacts.global.ts`, pour tout le dossier :
// la garde `import.meta.dev` qui vivait ici aurait aussi fermé la preprod, qui builde en production.

definePageMeta({ layout: false })
useHead({ title: 'UX Kit' })

const colorMode = useColorMode()
const toggleTheme = () => {
  colorMode.preference = 'dark' === colorMode.value ? 'light' : 'dark'
}

// États des démos interactives
const checkbox = ref(true)
const switchOn = ref(false)
const inputText = ref('')
const selectValue = ref('primary')
const activeTab = ref('0')

const buttonVariants = ['solid', 'outline', 'soft', 'subtle', 'ghost', 'link'] as const
const semanticColors = ['primary', 'neutral', 'success', 'warning', 'error'] as const
const buttonSizes = ['xs', 'sm', 'md', 'lg', 'xl'] as const

const tabItems = [
  { label: 'Aperçu', value: '0' },
  { label: 'Détails', value: '1' },
  { label: 'Historique', value: '2' },
]

const selectItems = ['primary', 'neutral', 'error']

const tableData = [
  { name: 'Alice Martin', role: 'Designer', active: true },
  { name: 'Bob Durand', role: 'Développeur', active: false },
]
const tableColumns: TableColumn<(typeof tableData)[number]>[] = [
  { accessorKey: 'name', header: 'Nom' },
  { accessorKey: 'role', header: 'Rôle' },
  { id: 'status', header: 'Statut' },
]
</script>

<template>
  <div class="ux-kit min-h-screen bg-bg p-6 text-text sm:p-10">
    <UApp>
      <div class="mx-auto flex max-w-5xl flex-col gap-12">
        <!-- En-tête -->
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-6">
          <div class="flex flex-col gap-1">
            <h1 class="font-display text-h1">UX Kit</h1>
            <p class="text-muted">
              Galerie des composants du socle — référence du design system. Dev et preprod
              uniquement.
            </p>
          </div>
          <ClientOnly>
            <UButton
              :icon="'dark' === colorMode.value ? 'i-lucide-sun' : 'i-lucide-moon'"
              color="neutral"
              variant="outline"
              @click="toggleTheme"
            >
              {{ 'dark' === colorMode.value ? 'Thème clair' : 'Thème sombre' }}
            </UButton>
          </ClientOnly>
        </header>

        <!-- Identité réelle du produit, avec les composants partagés. -->
        <section class="flex flex-col gap-block">
          <h2 class="text-h2 font-semibold">Identité · {{ $t('app.name') }}</h2>
          <UiCard class="flex flex-col gap-block">
            <div class="flex flex-wrap items-center gap-block">
              <LayoutLogo class="h-8 w-auto" />
              <LayoutLogomark class="h-8 w-8 text-accent" />
              <UiBadge>Bleu · Ardoise · Geist</UiBadge>
            </div>
            <p class="text-muted">{{ $t('app.tagline', { break: ' ' }) }}</p>
            <div class="flex flex-wrap gap-element">
              <UiButton>Action principale</UiButton>
              <UiButton variant="secondary">Action secondaire</UiButton>
            </div>
          </UiCard>
        </section>

        <!-- BOUTONS -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Boutons · UButton</h2>
          <div class="flex flex-col gap-5 rounded-lg bg-surface p-5">
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Variantes (couleur primary)</span>
              <div class="flex flex-wrap items-center gap-2">
                <UButton v-for="v in buttonVariants" :key="v" :variant="v">{{ v }}</UButton>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Couleurs sémantiques (variant solid)</span>
              <div class="flex flex-wrap items-center gap-2">
                <UButton v-for="c in semanticColors" :key="c" :color="c">{{ c }}</UButton>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Tailles</span>
              <div class="flex flex-wrap items-center gap-2">
                <UButton v-for="s in buttonSizes" :key="s" :size="s">{{ s }}</UButton>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Icône · chargement · désactivé</span>
              <div class="flex flex-wrap items-center gap-2">
                <UButton icon="i-lucide-plus">Icône avant</UButton>
                <UButton trailing-icon="i-lucide-arrow-right">Icône après</UButton>
                <UButton loading>Chargement</UButton>
                <UButton disabled>Désactivé</UButton>
              </div>
            </div>
          </div>
        </section>

        <!-- BADGES & CHIPS -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Badges & Chips · UBadge · UChip</h2>
          <div class="flex flex-col gap-5 rounded-lg bg-surface p-5">
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Badge — variantes (couleur primary)</span>
              <div class="flex flex-wrap items-center gap-2">
                <UBadge variant="solid">solid</UBadge>
                <UBadge variant="outline">outline</UBadge>
                <UBadge variant="soft">soft</UBadge>
                <UBadge variant="subtle">subtle</UBadge>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Badge — couleurs (variant soft)</span>
              <div class="flex flex-wrap items-center gap-2">
                <UBadge v-for="c in semanticColors" :key="c" :color="c" variant="soft">{{
                  c
                }}</UBadge>
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">Chip — indicateur d'état sur un élément</span>
              <div class="flex flex-wrap items-center gap-4">
                <UChip color="error" inset>
                  <UButton icon="i-lucide-bell" color="neutral" variant="outline" square />
                </UChip>
                <UChip :text="3" color="primary" size="2xl">
                  <UButton icon="i-lucide-mail" color="neutral" variant="outline" square />
                </UChip>
              </div>
            </div>
          </div>
        </section>

        <!-- LIENS -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Liens · ULink</h2>
          <div class="flex flex-wrap items-center gap-4 rounded-lg bg-surface p-5">
            <ULink to="/artefacts/ux-kit">Lien interne</ULink>
            <ULink to="https://nuxt.com" target="_blank" class="text-accent">Lien externe</ULink>
            <ULink to="/artefacts/ux-kit" active-class="font-semibold text-accent"
              >Lien (route active)</ULink
            >
          </div>
        </section>

        <!-- AVATARS -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Avatars · UAvatar · UiAvatar (wrapper métier)</h2>
          <div class="flex flex-col gap-5 rounded-lg bg-surface p-5">
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted">UAvatar — tailles (repli sur initiales)</span>
              <div class="flex flex-wrap items-center gap-3">
                <UAvatar text="AM" size="xs" />
                <UAvatar text="AM" size="sm" />
                <UAvatar text="AM" size="md" />
                <UAvatar text="AM" size="lg" />
                <UAvatar icon="i-lucide-user" size="lg" />
              </div>
            </div>
            <div class="flex flex-col gap-2">
              <span class="text-sm text-muted"
                >UiAvatar — thumbnail Jarvi + initiales (masqué = initiales)</span
              >
              <div class="flex flex-wrap items-center gap-3">
                <UiAvatar given-name="Alice" family-name="Martin" :size="36" />
                <UiAvatar given-name="Bob" family-name="Durand" :size="48" />
              </div>
            </div>
          </div>
        </section>

        <!-- CARTES -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Cartes · UCard</h2>
          <div class="grid gap-4 sm:grid-cols-2">
            <UCard>
              <template #header>
                <h3 class="font-semibold">Carte avec en-tête</h3>
              </template>
              <p class="text-muted">Corps de la carte. Bord + ombre légère du thème Nuxt UI.</p>
              <template #footer>
                <UButton size="sm">Action</UButton>
              </template>
            </UCard>
            <UCard>
              <p class="text-muted">Carte simple (corps seul).</p>
            </UCard>
          </div>
        </section>

        <!-- FORMULAIRES -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Formulaires · Input · Select · Checkbox · Switch</h2>
          <div class="grid gap-5 rounded-lg bg-surface p-5 sm:grid-cols-2">
            <UFormField label="Champ texte" hint="Optionnel">
              <UInput v-model="inputText" placeholder="Saisir…" icon="i-lucide-search" />
            </UFormField>
            <UFormField label="Sélecteur">
              <USelect v-model="selectValue" :items="selectItems" />
            </UFormField>
            <UFormField label="Zone de texte">
              <UTextarea :rows="3" placeholder="Plusieurs lignes…" />
            </UFormField>
            <div class="flex flex-col gap-3">
              <UCheckbox v-model="checkbox" label="Case à cocher" />
              <USwitch v-model="switchOn" label="Interrupteur" />
            </div>
          </div>
        </section>

        <!-- ALERTES -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Alertes · UAlert</h2>
          <div class="flex flex-col gap-3">
            <UAlert
              v-for="c in semanticColors"
              :key="c"
              :color="c"
              variant="subtle"
              :title="`Alerte ${c}`"
              description="Message d'accompagnement de l'alerte."
              icon="i-lucide-info"
            />
          </div>
        </section>

        <!-- ONGLETS -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Onglets · UTabs</h2>
          <div class="rounded-lg bg-surface p-5">
            <UTabs v-model="activeTab" :items="tabItems" :content="false" />
            <p class="mt-4 text-muted">Onglet actif : {{ tabItems[Number(activeTab)]?.label }}</p>
          </div>
        </section>

        <!-- TABLE -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Table · UTable</h2>
          <UTable :data="tableData" :columns="tableColumns" class="rounded-lg bg-surface">
            <template #status-cell="{ row }">
              <UBadge :color="row.original.active ? 'success' : 'neutral'" variant="soft">
                {{ row.original.active ? 'Actif' : 'Inactif' }}
              </UBadge>
            </template>
          </UTable>
        </section>

        <!-- INFOBULLES & AIDE -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Infobulles · UTooltip · UiInfoHint (a11y)</h2>
          <div class="flex flex-wrap items-center gap-6 rounded-lg bg-surface p-5">
            <UTooltip text="Infobulle Nuxt UI (survol/focus)">
              <UButton color="neutral" variant="outline">Survole-moi</UButton>
            </UTooltip>
            <span class="flex items-center gap-2 text-muted">
              Bulle d'aide accessible
              <UiInfoHint text="Aide au survol, focus clavier ET tap tactile." />
            </span>
          </div>
        </section>

        <!-- SURCOUCHES -->
        <section class="flex flex-col gap-4">
          <h2 class="text-h2 font-semibold">Surcouches · USlideover · UModal</h2>
          <div class="flex flex-wrap items-center gap-3 rounded-lg bg-surface p-5">
            <USlideover title="Panneau latéral" description="Exemple de USlideover.">
              <UButton icon="i-lucide-panel-right">Ouvrir un slideover</UButton>
              <template #body>
                <p class="text-muted">
                  Contenu du panneau. Focus piégé, Échap ferme, backdrop cliquable.
                </p>
              </template>
            </USlideover>

            <UModal title="Fenêtre modale" description="Exemple de UModal.">
              <UButton icon="i-lucide-square" color="neutral" variant="outline"
                >Ouvrir une modale</UButton
              >
              <template #body>
                <p class="text-muted">Contenu de la modale.</p>
              </template>
            </UModal>
          </div>
        </section>
      </div>
    </UApp>
  </div>
</template>
