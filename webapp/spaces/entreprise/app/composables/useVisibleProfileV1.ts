/**
 * Quelle carte de la pile est SOUS LES YEUX.
 *
 * La colonne de droite en a besoin pour deux choses qui n'ont pas d'autre source : dire où l'on en
 * est dans le jeu, et lister ce qui manque AU PROFIL COURANT. Les deux suivent le défilement, rien
 * d'autre ne les porte.
 *
 * On lit la POSITION DE DÉFILEMENT plutôt que d'observer chaque carte : la pile est en
 * `scroll-snap`, donc une carte occupe l'écran à la fois, et comparer les `offsetTop` reste juste
 * même si les cartes n'ont pas toutes la même hauteur. Un observateur par carte demanderait de
 * suivre des références au fil d'un `v-for` — plus de mécanique pour le même résultat.
 *
 * `useScroll` de VueUse et non un `addEventListener` : SSR-safe, retiré tout seul, et déjà
 * l'attendu du projet.
 */
export const useVisibleProfileV1 = (container: Ref<HTMLElement | null>) => {
  /**
   * Rang de la carte courante, à partir de zéro. Reste à zéro tant que rien n'a défilé — la première
   * carte EST la carte courante, il n'y a pas d'état « aucune ».
   */
  const index = ref(0)

  // Une carte est « courante » dès que son haut a franchi celui du conteneur. La tolérance absorbe
  // les arrondis du défilement fluide, qui laissent souvent un ou deux pixels.
  const SNAP_TOLERANCE = 4

  const { y } = useScroll(container, { throttle: 120 })

  const update = (): void => {
    const element = container.value
    if (!element) {
      return
    }

    const cards = [...element.children] as HTMLElement[]
    const scrolled = y.value + SNAP_TOLERANCE

    const current = cards.reduce(
      (found, card, position) =>
        card.offsetTop - element.offsetTop <= scrolled ? position : found,
      0,
    )

    index.value = current
  }

  watch(y, update)

  return { index: readonly(index) }
}
