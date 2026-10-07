import type { SearchCriteria, UnderstoodFilter } from '#shared/types/ai-search'

/**
 * Recherche assistée : l'aller-retour serveur (phrase → critères) que <SearchFiltersAi> n'a pas à
 * porter. Le composant émet `search`/`remove`, cette couche appelle le BFF et tient l'état.
 *
 * `run` remplace les critères courants ; retirer un critère ne le supprime pas mais le masque
 * (`removed`) — la recherche à venir n'appliquera que ceux restés visibles. `errorKey` est une clé
 * i18n, traduite au dernier moment par le template (règle du socle : `$t` en template uniquement).
 */
export const useAiSearch = () => {
  const criteria = ref<SearchCriteria | null>(null)
  const pending = ref(false)
  const errorKey = ref('')
  const removed = ref<Set<string>>(new Set())

  const run = async (query: string): Promise<void> => {
    pending.value = true
    errorKey.value = ''
    removed.value = new Set()

    try {
      criteria.value = await $fetch<SearchCriteria>('/api/ai/parse-search', {
        method: 'POST',
        body: { query },
      })
    } catch (error) {
      criteria.value = null
      const reason = (error as { data?: { reason?: string } })?.data?.reason
      errorKey.value =
        'not_configured' === reason ? 'search.ai.error.notConfigured' : 'search.ai.error.generic'
    } finally {
      pending.value = false
    }
  }

  const remove = (id: string): void => {
    removed.value = new Set(removed.value).add(id)
  }

  const reset = (): void => {
    criteria.value = null
    errorKey.value = ''
    removed.value = new Set()
  }

  return { criteria, pending, errorKey, removed, run, remove, reset }
}

type Translate = (key: string, params?: Record<string, unknown>) => string

/**
 * Projette les critères sur des étiquettes prêtes à afficher. Pure — reçoit `t` en argument (pas
 * d'`useI18n`), ce qui la garde testable et respecte la règle du socle. Les identifiants sont
 * stables (`kw:0`, `country`, `lang:1`…) : c'est par eux que `remove` masque un critère.
 */
export const aiCriteriaToFilters = (
  criteria: SearchCriteria | null,
  t: Translate,
  removed: ReadonlySet<string> = new Set(),
): UnderstoodFilter[] => {
  if (!criteria) {
    return []
  }

  const filters: UnderstoodFilter[] = []

  criteria.keywords.forEach((group, index) => {
    const term = group.terms[0]
    if (term) {
      filters.push({ id: `kw:${index}`, label: term })
    }
  })

  if (criteria.country) {
    filters.push({
      id: 'country',
      label: t('search.ai.criteria.country', { value: criteria.country }),
    })
  }

  if (criteria.city) {
    filters.push({ id: 'city', label: t('search.ai.criteria.city', { value: criteria.city }) })
  }

  criteria.languages.forEach((lang, index) => {
    const label = lang.minProficiency
      ? t('search.ai.criteria.languageLevel', {
          value: lang.language,
          level: t(`search.ai.proficiency.${lang.minProficiency}`),
        })
      : t('search.ai.criteria.language', { value: lang.language })
    filters.push({ id: `lang:${index}`, label })
  })

  if (criteria.availability) {
    filters.push({
      id: 'availability',
      label: t(`search.availability.${criteria.availability}`),
    })
  }

  if (criteria.returnHorizon) {
    filters.push({
      id: 'returnHorizon',
      label: t(`search.horizon.${criteria.returnHorizon}`),
    })
  }

  if (criteria.geoMobility) {
    filters.push({ id: 'geoMobility', label: t('search.ai.criteria.geoMobility') })
  }

  return filters.filter((filter) => !removed.has(filter.id))
}
