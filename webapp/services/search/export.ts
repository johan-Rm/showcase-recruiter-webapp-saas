import { isRevealed } from './reveal'

import type { CandidateCard } from '../../shared/types/candidate-card'

/**
 * Ce qu'un export emporte, et ce qu'il laisse.
 *
 * Un profil dont AUCUN canal n'est payé n'a rien à donner — l'écran doit le dire AVANT l'appel,
 * sans quoi le recruteur reçoit un fichier plus court que sa sélection, sans savoir pourquoi.
 *
 * Le seuil est « au moins un canal », le même que celui du serveur : exiger les deux ferait payer
 * deux crédits ce qu'un seul ouvrait, et le fichier laisse de toute façon vide la colonne du canal
 * non acheté.
 *
 * Métier pur : c'est un partage de liste, il se teste sans runtime.
 */
export interface ExportScope {
  /** Ce qui sortira réellement. */
  exportable: string[]
  /** Ce qui sera ignoré, faute d'être dévoilé. */
  ignored: number
}

export const exportScope = (selected: readonly CandidateCard[]): ExportScope => {
  const exportable = selected.filter(isRevealed)

  return {
    exportable: exportable.map((candidate) => candidate.id),
    ignored: selected.length - exportable.length,
  }
}

/**
 * Le nom du fichier, composé du nom de la recherche.
 *
 * Miroir de `SearchCsvWriter::filename` : minuscules, et tout ce qui n'est ni lettre ni chiffre
 * devient un tiret — les accents RESTENT, contrairement à ce qu'on pourrait croire. Recopié ici
 * parce que le relais ne transmet pas l'en-tête qui le porte ; si les deux divergent, c'est le nom
 * proposé au téléchargement qui change, rien de plus.
 */
export const exportFileName = (searchName: string): string => {
  const slug = searchName
    .toLowerCase()
    .replace(/[^\p{L}\p{N}]+/gu, '-')
    .replace(/^-+|-+$/g, '')

  return `profils-${'' === slug ? 'sans-nom' : slug}.csv`
}
