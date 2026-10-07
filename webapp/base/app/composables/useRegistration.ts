import { SPACES_WITH_REGISTRATION } from '#shared/utils/space'

/**
 * État d'inscription de l'écran d'auth : connexion et première étape d'inscription partagent la
 * page `/login`, c'est l'URL qui les distingue (`?inscription`).
 *
 * Partagé entre la page et la coque `auth` — la coque doit savoir de quel côté poser le panneau
 * de marque. Recopier la règle des deux côtés la ferait dériver au premier changement.
 */
export const useRegistration = () => {
  const route = useRoute()
  const { localePath } = useLocale()

  // Tous les espaces n'ouvrent pas de tunnel : un candidat est invité, un admin créé en interne.
  const available = SPACES_WITH_REGISTRATION.includes(useSpace())

  // `?inscription` est un drapeau sans valeur : Vue Router le rend en `null`, jamais en `true`.
  const registering = computed(() => available && 'inscription' in route.query)

  const registerPath = computed(() => localePath({ path: '/login', query: { inscription: null } }))

  return { available, registering, registerPath }
}
