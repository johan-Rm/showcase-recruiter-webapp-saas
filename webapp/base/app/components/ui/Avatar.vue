<script setup lang="ts">
import { nameInitials, photoThumbnail } from '#shared/utils/photo'

// Vignette d'identité. La photo vient du storage Jarvi, jamais de nos serveurs : l'image est
// chargée à distance, dimensionnée par l'URL. Sans photo (profil masqué ou absente à la source),
// on affiche les initiales — un visage générique mentirait sur ce qu'on détient.
interface Props {
  givenName: string
  familyName: string
  photoUrl?: string | null
  /** Côté du carré en pixels CSS ; l'image est demandée en 2× pour les écrans à forte densité. */
  size?: number
  /**
   * Coins arrondis plutôt que rond — pour les grands formats, où le rond rogne un visage cadré en
   * portrait et fait perdre au portrait ce qu'il a de reconnaissable.
   */
  square?: boolean
}

const { givenName, familyName, photoUrl = null, size = 40, square = false } = defineProps<Props>()

const initials = computed(() => nameInitials(givenName, familyName))
const fullName = computed(() => `${givenName} ${familyName}`.trim())

/**
 * Une URL de photo n'est pas une photo.
 *
 * Elle vient du storage Jarvi : le profil peut l'annoncer alors que le fichier a disparu. Le
 * navigateur dessine alors son icône d'image brisée et déborde le texte alternatif hors du carré —
 * le repli sur les initiales ne couvrait que l'URL ABSENTE, pas l'URL morte.
 *
 * D'où cet état local : la première erreur de chargement fait basculer sur les initiales, et le
 * gabarit tient quoi qu'il arrive à la source.
 */
const broken = ref(false)

// Un changement de profil réarme la tentative : sans ça, une carte recyclée par la pile garderait
// l'échec du profil précédent.
watch(
  () => photoUrl,
  () => (broken.value = false),
)

const source = computed(() => (broken.value ? null : photoThumbnail(photoUrl, size * 2)))
</script>

<template>
  <span
    class="ui-avatar inline-flex shrink-0 items-center justify-center overflow-hidden bg-accent-soft font-semibold text-accent"
    :class="square ? 'rounded-lg' : 'rounded-full'"
    :style="{ width: `${size}px`, height: `${size}px`, fontSize: `${Math.round(size / 2.5)}px` }"
  >
    <img
      v-if="source"
      :src="source"
      :alt="fullName"
      :width="size"
      :height="size"
      loading="lazy"
      decoding="async"
      class="size-full object-cover"
      @error="broken = true"
    />
    <span v-else aria-hidden="true">{{ initials }}</span>
  </span>
</template>
