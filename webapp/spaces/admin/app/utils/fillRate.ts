/**
 * Part détenue d'un champ du vivier, en pourcentage entier.
 *
 * Sert deux lectures d'un même chiffre, et c'est pour ça qu'il vit ici plutôt que dans le
 * composant : le nombre écrit à côté du libellé, et la longueur de la jauge posée dessous. Les deux
 * doivent dire la même chose au pixel près — un arrondi calculé deux fois finit par diverger.
 *
 * Un total à zéro rend 0 et non `NaN` : une base vide est un état normal au démarrage, pas une
 * anomalie, et un `NaN` propagé dans une largeur casse la jauge en silence.
 */
export const fillRatePercent = (filled: number, total: number): number =>
  total === 0 ? 0 : Math.round((100 * filled) / total)
