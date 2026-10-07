import type { CandidateCard } from '#shared/types/candidate-card'
import type { RevealType } from '#shared/types/reveal'

/**
 * Quel accès payé ouvre quel champ du profil.
 *
 * Une correspondance de quatre lignes, et pourtant l'endroit du produit où une inversion coûte le
 * plus cher : lire l'e-mail sous l'accès téléphone servirait gratuitement ce qui se paie, et aucune
 * capture d'écran ne le montrerait — les deux champs s'affichent au même endroit, dans la même
 * colonne. D'où sa place ici, dans la couche pure, plutôt qu'au fond d'un composable.
 *
 * Chaque canal commande le SIEN et rien d'autre : payer l'e-mail n'ouvre pas le téléphone. L'identité, elle, ne se paie plus du tout — elle n'a donc aucun accès à consulter.
 */
export const hasAccess = (profile: CandidateCard, type: RevealType): boolean => {
  switch (type) {
    case 'EMAIL':
      return profile.revealedEmail
    case 'PHONE':
      return profile.revealedPhone
    case 'RESUME':
      return profile.revealedResume
    case 'INTERVIEW_REPORT':
      return profile.revealedReport
  }
}

/**
 * Y a-t-il quelque chose à ouvrir sur ce canal ?
 *
 * **Absent ne veut pas dire vide.** Un serveur qui ne renvoie pas encore le signal ne dit pas qu'il
 * n'y a rien, il ne dit rien : on répond alors « oui », pour ne pas éteindre un canal qui a peut-être
 * de quoi servir. C'est le sens de la garde, pas une tolérance : éteindre sur une absence
 * d'information rendrait un contenu payant inatteignable partout où le déploiement a pris du retard.
 *
 * L'argent, lui, n'en dépend pas — le serveur refuse de facturer un canal vide, que l'écran l'ait
 * proposé ou non.
 */
export const hasDataOn = (profile: CandidateCard, type: RevealType): boolean => {
  switch (type) {
    case 'EMAIL':
      return profile.hasEmail
    case 'PHONE':
      return profile.hasPhone
    case 'RESUME':
      return profile.hasResume ?? true
    case 'INTERVIEW_REPORT':
      return profile.hasReport ?? true
  }
}

/**
 * Combien de profils d'un lot RESTENT à payer sur ce canal.
 *
 * DEUX exclusions, et elles se cumulent : ce qui est déjà dévoilé ne se refacture pas, et ce qui n'a
 * rien à servir ne se facture pas du tout — le serveur les ignore l'un comme l'autre. Compter les
 * profils sélectionnés au lieu des accès à payer annoncerait un prix trop haut, et ferait renoncer à
 * un lot qui tenait dans le solde.
 *
 * La seconde exclusion pèse lourd : sur ce vivier, 1 % des profils ont un e-mail. Sans elle, le prix
 * annoncé porte presque toujours sur des adresses qui n'existent pas.
 *
 * Ici, dans la couche pure, parce que ce compte est ce que le bouton AFFICHE avant le clic, et pas
 * seulement ce que la fenêtre de confirmation récapitule après.
 *
 * Il se compte, jamais ne se déduit d'une division du coût par le prix unitaire : sur un chemin
 * d'affichage, cette division demande de se protéger d'un prix nul, et rendrait `NaN` le jour où
 * elle ne le fait pas.
 */
export const payableCount = (profiles: readonly CandidateCard[], type: RevealType): number =>
  profiles.filter((profile) => !hasAccess(profile, type) && hasDataOn(profile, type)).length

/**
 * Pourquoi un canal ne coûte rien — parce que tout est acquis, ou parce qu'il n'y a rien.
 *
 * Sous un même zéro, les deux se confondent — et un écran qui ne dit pas ce qu'il sait laisse le
 * recruteur devant un chiffre muet. « Déjà obtenu » est une bonne nouvelle ; « aucun » dit qu'il
 * faudra joindre la personne autrement.
 *
 * Un lot MIXTE n'a pas de libellé spécial : il reste des profils à payer, donc le canal affiche son
 * coût. Les deux autres réponses ne valent que pour les cas purs. Un canal à la fois vide et déjà
 * payé répond « aucun » : c'est l'information utile pour la suite.
 */
type ChannelState = 'payable' | 'owned' | 'empty'

export const channelState = (
  profiles: readonly CandidateCard[],
  type: RevealType,
): ChannelState => {
  if (payableCount(profiles, type) > 0) return 'payable'

  return profiles.every((profile) => hasAccess(profile, type)) ? 'owned' : 'empty'
}

/**
 * Ce profil est-il « dévoilé » ?
 *
 * La question a cessé d'être binaire quand les canaux se sont séparés. La réponse retenue : dévoilé
 * = **au moins un canal de contact payé**. C'est ce que le recruteur suit du regard — combien de
 * personnes il a engagées — et ça reste comparable au compteur d'avant.
 *
 * Les documents n'y entrent pas : acheter un CV ne rend joignable personne.
 */
export const isRevealed = (profile: CandidateCard): boolean =>
  profile.revealedEmail || profile.revealedPhone
