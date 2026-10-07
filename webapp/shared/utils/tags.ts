// Ce qui reste à prendre dans une liste de propositions, quand certaines sont déjà retenues.
//
// Isolé ici, pur et sans framework, parce que c'est la seule partie du geste d'ajout groupé qui
// puisse se tromper en silence : le composant qui l'appelle n'a aucun banc capable de le rendre, et
// un doublon posé ne se verrait qu'à l'usage.

/**
 * Les propositions qu'il reste à poser, dans l'ordre où elles s'affichent.
 *
 * DÉDOUBLONNE DES DEUX CÔTÉS : contre ce qui est déjà retenu, et contre la liste elle-même. Le
 * vocabulaire proposé est recopié de ce que les profils écrivent, sans normalisation — la même
 * valeur peut y figurer deux fois, et la poser deux fois créerait deux étiquettes identiques que
 * rien ne permettrait de distinguer pour en retirer une.
 *
 * L'ORDRE EST CELUI DE L'AFFICHAGE, jamais un tri : le recruteur doit retrouver dans ses étiquettes
 * la suite qu'il vient de lire.
 */
export const pendingTags = (suggested: readonly string[], held: readonly string[]): string[] => {
  const seen = new Set(held)
  const pending: string[] = []

  for (const value of suggested) {
    if (seen.has(value)) {
      continue
    }

    seen.add(value)
    pending.push(value)
  }

  return pending
}
