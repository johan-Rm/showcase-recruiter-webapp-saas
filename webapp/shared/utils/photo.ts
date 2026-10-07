// Photo de profil : l'API sert l'URL publique du storage Jarvi telle quelle, c'est au front de
// demander la bonne taille. Pur (aucun import framework) — utilisable partout, testable seul.

/** Qualité JPEG demandée au storage : au-delà, le gain visuel ne paie plus les octets. */
const PHOTO_QUALITY = 80

/**
 * Vignette carrée dimensionnée à la source. Sur une photo réelle, `size: 96` ramène ~3 Ko là où
 * l'original pèse ~111 Ko — d'où l'usage systématique sur les listes.
 *
 * Retourne `null` quand il n'y a pas de photo : profil masqué ou profil sans photo chez Jarvi
 * (14 % du vivier). Les deux cas se replient sur les initiales.
 */
export const photoThumbnail = (url: string | null | undefined, size: number): string | null => {
  if (!url) {
    return null
  }

  const separator = url.includes('?') ? '&' : '?'

  return `${url}${separator}w=${size}&h=${size}&q=${PHOTO_QUALITY}`
}

/** Repli des vignettes : initiales du prénom et du nom, jamais un visage générique. */
export const nameInitials = (givenName: string, familyName: string): string =>
  `${givenName.at(0) ?? ''}${familyName.at(0) ?? ''}`.toUpperCase()
