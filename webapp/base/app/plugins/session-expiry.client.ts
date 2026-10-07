/**
 * Ramène au login dès qu'un appel au BFF annonce une session close, d'où qu'il parte.
 *
 * Un plugin et non un traitement au point d'appel : la panne ne se déclare pas qu'à l'ouverture
 * d'un écran. Elle attend aussi le geste — enregistrer une recherche, dévoiler un profil — et ces
 * gestes vivent dans les espaces, chacun avec sa propre gestion d'erreur. Les couvrir un par un
 * laisserait un trou à chaque nouvel appel écrit ailleurs ; envelopper le client HTTP les couvre
 * tous, y compris ceux qui n'existent pas encore.
 *
 * `.client` : au rendu serveur, les appels internes passent par l'instance liée à la requête, hors
 * de portée d'ici. Un écran dont le chargement passe par les paliers du socle y redirige avant tout
 * affichage (`useAppBootstrap`) ; ailleurs, la page part telle quelle et c'est le réveil de
 * l'application qui redirige, aussitôt. Le premier rendu ne paie donc aucun contrôle : le contrôler
 * coûterait un appel avant CHAQUE page servie, pour un incident rare, et la session est de toute
 * façon refermée dès le premier appel — l'écran trompeur ne peut pas se répéter.
 */
export default defineNuxtPlugin((nuxtApp) => {
  const { handleSessionExpiry } = useSessionExpiry()

  globalThis.$fetch = globalThis.$fetch.create({
    async onResponseError({ response }) {
      // Le corps composé par le BFF, tel qu'il arrivera dans l'erreur levée juste après : la
      // reconnaissance du motif lit toujours la même forme, quel que soit son point d'entrée.
      await nuxtApp.runWithContext(() => handleSessionExpiry({ data: response._data }))
    },
  })
})
