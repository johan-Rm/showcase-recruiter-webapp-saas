/**
 * Quelle carte de la pile est SOUS LES YEUX.
 *
 * Trois choses en dépendent, et la troisième change la nature de ce fichier : le rang affiché dans
 * la barre haute, le verdict de la colonne de droite, et **les deux gestes de cette colonne**.
 * Depuis qu'ils y vivent, ce composable ne décide plus seulement de ce qu'on lit mais de ce sur quoi
 * on ÉCRIT — se tromper de carte n'affiche plus un mauvais verdict, ça retient ou écarte le mauvais
 * profil.
 *
 * On lit la POSITION DE DÉFILEMENT plutôt que d'observer chaque carte : comparer les `offsetTop`
 * reste juste même si les cartes n'ont pas toutes la même hauteur, et un observateur par carte
 * demanderait de suivre des références au fil d'un `v-for` — plus de mécanique pour le même
 * résultat.
 *
 * `useScroll` de VueUse et non un `addEventListener` : SSR-safe, retiré tout seul, et déjà
 * l'attendu du projet.
 */
export const useVisibleProfile = (container: Ref<HTMLElement | null>) => {
  /**
   * Rang de la carte courante, à partir de zéro. Reste à zéro tant que rien n'a défilé — la première
   * carte EST la carte courante, il n'y a pas d'état « aucune ».
   */
  const index = ref(0)

  const { y } = useScroll(container, { throttle: 120 })

  /**
   * LA CARTE QUI COUVRE LE MILIEU DE LA ZONE, et non celle dont le haut a franchi son bord.
   *
   * La règle du bord ne basculait qu'à la toute fin du défilement : la carte suivante occupait déjà
   * les trois quarts de la vue pendant que la colonne parlait encore de la précédente. Sans
   * conséquence tant que celle-ci ne faisait qu'afficher ; depuis qu'elle porte « retenir » et
   * « écarter », c'est un clic sur le profil qu'on vient de quitter.
   *
   * MESURÉ À L'ÉCRAN (`getBoundingClientRect`) et non par `offsetTop` : ce dernier se compte depuis
   * l'ancêtre POSITIONNÉ, qui se trouve être la pile elle-même — lui soustraire en plus la position
   * de la pile dans la page décalait le seuil de la hauteur de l'en-tête, et la bascule arrivait
   * deux cents pixels trop tôt. Le rectangle affiché ne suppose rien de la mise en page.
   *
   * Aucune hypothèse non plus sur la hauteur des cartes : celle qui couvre le milieu est celle qu'on
   * regarde, qu'elles fassent un écran ou deux.
   */
  const update = (): void => {
    const element = container.value
    if (!element) {
      return
    }

    const zone = element.getBoundingClientRect()
    const mediane = zone.top + zone.height / 2
    const cards = [...element.children] as HTMLElement[]

    let found = 0
    for (const [position, card] of cards.entries()) {
      // Les cartes sont dans l'ordre : la première qui commence sous la médiane clôt la recherche.
      if (card.getBoundingClientRect().top > mediane) break
      found = position
    }

    index.value = found
  }

  watch(y, update)

  return { index: readonly(index) }
}
