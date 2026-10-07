/**
 * Capte le referrer externe au premier rendu, sur TOUS les espaces.
 *
 * Un plugin et non un appel dans `app.vue` : la capture doit avoir lieu au tout premier chargement
 * de document, quel que soit l'écran d'entrée — le site public, un login, un lien partagé. La lier
 * à une page reviendrait à ne mesurer que les visiteurs entrés par cette page-là.
 *
 * `.client` : `document.referrer` n'existe pas au rendu serveur. Il n'y a rien à capter là-bas — le
 * `Referer` HTTP y serait celui de la requête de document, que Nitro ne relaie pas jusqu'au client.
 */
export default defineNuxtPlugin(() => {
  useAcquisitionReferrer().capture()
})
