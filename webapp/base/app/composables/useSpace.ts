import type { Space } from '#shared/utils/space'

// Constante de build (SHOWCASE_RECRUITER_WEBAPP_SAAS_SPACE), validée contre SPACES dans le nuxt.config racine.
export const useSpace = (): Space => useRuntimeConfig().public.space as Space
