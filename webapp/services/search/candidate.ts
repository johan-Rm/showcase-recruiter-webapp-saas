import type { CandidateCard } from '#shared/types/candidate-card'

/**
 * De quoi reconnaître une DÉCISION sans identifier une personne.
 *
 * Les listes de la visionneuse — les favoris, les écartés — affichaient des noms masqués : douze
 * retenus donnaient douze lignes identiques, et l'on ne pouvait ni se relire ni en retirer un en
 * connaissance de cause. Le poste, l'ancienneté et la ville suffisent à se souvenir de qui l'on
 * parle, et ne désignent personne.
 *
 * Ici plutôt que dans un composant : la corbeille s'en sert pour nommer ce qu'on a écarté, et elle
 * est la SEULE de son genre — la Table nomme les siens autrement, et deux formulations du même
 * profil sur un même écran se remarquent.
 */
/**
 * Le métier cherché est-il ENCORE exercé par ce profil ?
 *
 * La Table balaie cent profils : savoir qu'un métier reconnu a été quitté se lit à la ligne, sans
 * ouvrir la fiche. Le panneau de la Visionneuse dit la même chose par une autre voie — la
 * confrontation critère par critère —, mais la Table n'a pas de critère sous la main : elle a une
 * ligne, et cherche dans ce qu'elle porte.
 *
 * ⚠️ `null` ne veut pas dire « passé », il veut dire qu'on ne sait pas : le serveur omet le champ
 * quand le poste du moment est inconnu ou ambigu, et l'écran doit se taire avec lui.
 *
 * Un profil peut porter PLUSIEURS critères de métier ; on rend le premier qui a matché, parce que
 * c'est celui que la cellule montre.
 */
export const jobTenureOf = (candidate: CandidateCard): 'CURRENT' | 'PAST' | null =>
  (candidate.matches ?? []).find((match) => 'job' === match.facet && match.matched)?.matchedWhen ??
  null

/**
 * Ce qu'on affiche là où l'écran annonce un POSTE — et ce que vaut ce qu'on affiche.
 *
 * La Table et la Visionneuse titraient sur `headline`, l'accroche que le candidat s'écrit sur son
 * réseau : jusqu'à 752 caractères de démarchage là où le lecteur cherche un métier. `currentTitle`
 * dit le poste en 25 caractères de médiane, mais il manque sur 27 % du vivier — d'où le repli sur
 * l'accroche plutôt qu'un tiret, qui aurait vidé un quart du tableau.
 *
 * `isFallback` existe pour que ce repli SE VOIE : sans lui, rien ne distinguerait un intitulé
 * vérifié d'une phrase de vente, et on reconduirait la confusion en plus court. Les deux appelants
 * l'atténuent et le mettent en italique — l'italique, parce qu'un écart de teinte seul échappe à
 * qui distingue mal les nuances.
 *
 * ⚠️ Tester la valeur, jamais sa nullité : le serveur OMET `currentTitle` quand il n'a pas de
 * poste, il ne l'envoie pas à `null`.
 */
export interface CandidateRole {
  /** `null` quand le profil n'a ni poste ni accroche — l'écran pose alors son tiret d'absence. */
  text: string | null
  /** `true` quand c'est l'accroche qui bouche le trou, pas un intitulé de poste. */
  isFallback: boolean
}

export const roleOf = (candidate: CandidateCard): CandidateRole => {
  const title = candidate.currentTitle?.trim()
  if (title) return { text: title, isFallback: false }

  const headline = candidate.headline?.trim()

  return { text: headline || null, isFallback: Boolean(headline) }
}

/** Ce qu'une ligne de tableau peut porter sans passer sur une troisième ligne. */
export const SKILLS_ON_ROW = 3

/**
 * Les compétences à montrer sous l'intitulé de poste, dans une LIGNE de tableau.
 *
 * La ligne dit ce que le profil est ; elle ne dit pas pourquoi il est là. Sur une recherche à
 * quatre critères dont le profil n'en satisfait que deux, aucune colonne ne nomme lesquels — il faut
 * ouvrir la fiche, profil par profil. Trois mots sous le poste répondent en balayant.
 *
 * D'où l'ordre : ce qui a été TROUVÉ d'abord, dans l'ordre où le serveur l'a reconnu, complété par
 * les premières compétences du profil. Les afficher dans l'ordre de Jarvi — ce que fait la maquette
 * — donnerait trois mots dont l'ordre n'a aucun rapport avec la recherche, et sur un critère précis
 * les trois peuvent être hors sujet : la seconde ligne aurait coûté une hauteur pour du bruit.
 *
 * Le complément existe parce que la correspondance peut être vide : un profil entre parfois sur un
 * pays ou une fenêtre de présence, et il a quand même des compétences à montrer.
 *
 * On lit `matchedOn`, qui porte ce que le SERVEUR a reconnu et non le terme cherché — « postgres »
 * trouve « PostgreSQL ». Et seulement la facette du mot-clé : c'est la seule qui désigne une
 * compétence, les autres nomment un pays, une langue ou un poste. Le portrait de la Visionneuse lit
 * les mêmes correspondances de la même façon ; deux surfaces qui montrent un profil le décrivent
 * avec les mêmes mots.
 */
export const rowSkillsOf = (candidate: CandidateCard): string[] => {
  const skills = candidate.skills ?? []
  const found = (candidate.matches ?? [])
    .filter((match) => match.matched && 'q' === match.facet)
    .map((match) => match.matchedOn)
    .filter((label): label is string => 'string' === typeof label && skills.includes(label))

  return [...new Set([...found, ...skills])].slice(0, SKILLS_ON_ROW)
}

export const describeDecision = (candidate: CandidateCard): string =>
  [
    candidate.headline,
    candidate.experienceYears > 0 ? `${candidate.experienceYears} ans` : null,
    candidate.city,
  ]
    .filter(Boolean)
    .join(' · ')
