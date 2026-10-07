import type { Space } from '#shared/utils/space'

/**
 * URL absolue d'un autre espace (protocole relatif, port conservé en dev).
 * Aucun domaine en dur : tout dérive de NUXT_PUBLIC_BASE_DOMAIN.
 */
export const useSpaceUrl = (): ((space: Space) => string) => {
  const { baseDomain } = useRuntimeConfig().public
  const requestUrl = useRequestURL()
  const port = requestUrl.port ? `:${requestUrl.port}` : ''

  return (space: Space): string => {
    const host = space === 'public' ? baseDomain : `${space}.${baseDomain}`

    return `//${host}${port}/`
  }
}
