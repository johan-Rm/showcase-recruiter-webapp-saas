/**
 * État replié de la colonne de navigation, conservé d'une page à l'autre.
 *
 * Un COOKIE et non le stockage local, et ce n'est pas un détail de goût : le stockage local ne se
 * lit qu'APRÈS le rendu serveur, si bien que la page arriverait dépliée puis se replierait sous les
 * yeux. Le cookie part avec la requête, donc le serveur rend déjà le bon état.
 *
 * Ne vaut qu'au-dessus de `lg`, seule largeur où la colonne existe : le tiroir mobile rend la même
 * navigation et reste toujours déplié.
 */
export const useSidebar = () => {
  const collapsed = useCookie<boolean>('showcase-recruiter-webapp-saas_nav_collapsed', {
    default: () => false,
    sameSite: 'lax',
    path: '/',
    // Un réglage d'affichage, pas une donnée : il se garde longtemps et n'a rien de sensible.
    maxAge: 60 * 60 * 24 * 365,
  })

  return {
    collapsed,
    toggle: (): void => {
      collapsed.value = !collapsed.value
    },
  }
}
