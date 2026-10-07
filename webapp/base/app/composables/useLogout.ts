// Action de logout : ferme la session BFF, efface l'état, redirige vers le login.
//
// Cible `/login` et NON l'accueil : la déconnexion n'est offerte que sur les espaces
// protégés (cf. AuthNav), dont l'accueil sans session rebondirait sur /login. Y aller
// directement évite l'aller-retour — et le no-op de Vue Router quand l'accueil EST la
// page portant le bouton (navigation vers la route courante → middleware non rejoué).
export const useLogout = () => {
  const { clear } = useUserSession()
  const { localePath } = useLocale()

  const logout = async (): Promise<void> => {
    await $fetch('/api/auth/logout', { method: 'POST' })
    await clear()
    await navigateTo(localePath('/login'))
  }

  return { logout }
}
