/**
 * D'où vient le visiteur, mesuré — à ne pas confondre avec la provenance qu'il DÉCLARE.
 *
 * Les deux ne disent pas la même chose et ne doivent jamais se remplacer : la provenance répond
 * « comment nous avez-vous connus ? », ce referrer répond « par où êtes-vous passé ? ». Quelqu'un
 * arrivé par recommandation qui cherche « showcase-recruiter-webapp-saas » sur Google produit un referrer `google.com` —
 * pré-remplir la provenance avec ça écraserait le seul canal qui avait de la valeur pour l'admin
 * (cf. cadrage : « une recommandation nominative est un signal de légitimité »).
 *
 * MESURE PARTIELLE PAR NATURE. Le referrer n'identifie que le web : depuis une application mobile,
 * un client mail ou une URL tapée, il est vide. Sur six canaux, il n'en voit que deux — LinkedIn web
 * et la recherche. C'est une donnée d'acquisition, pas une vérité.
 *
 * POURQUOI UN COOKIE, et pas juste `document.referrer` au moment voulu : le parcours nominal passe
 * par le site public puis saute sur le sous-domaine entreprise (`useSpaceUrl` rend une URL absolue).
 * Ce saut RECHARGE le document et réinitialise `document.referrer` — qui vaut alors `showcase-recruiter-webapp-saas.example`,
 * notre propre site. LinkedIn n'existe plus dès le deuxième écran.
 */

/** Posé sur le domaine parent : il doit survivre au saut vers un sous-domaine. */
const COOKIE = 'showcase-recruiter-webapp-saas_referrer'

// Trente jours : le temps qu'une inscription traîne entre la découverte et le formulaire. Au-delà,
// rattacher une visite d'il y a un mois à une inscription relèverait de la superstition.
const MAX_AGE = 60 * 60 * 24 * 30

export const useAcquisitionReferrer = () => {
  const { baseDomain } = useRuntimeConfig().public

  const cookie = () =>
    useCookie<string | null>(COOKIE, {
      // Même règle que le cookie de session : scope parent en prod/preprod, host-only en dev où
      // `localhost` n'accepte pas de domaine parent.
      domain: 'localhost' === baseDomain ? undefined : `.${baseDomain}`,
      maxAge: MAX_AGE,
      sameSite: 'lax',
      // Lisible côté client : c'est de la mesure, pas un secret — et c'est le client qui l'écrit.
      httpOnly: false,
    })

  /**
   * À appeler au premier rendu client, sur n'importe quel espace.
   *
   * N'écrit QU'UNE FOIS : sans ce verrou, le saut public → entreprise réécrirait le cookie avec
   * notre propre domaine et effacerait le vrai referrer, ce qui est exactement le défaut qu'on
   * cherche à contourner.
   */
  const capture = (): void => {
    const stored = cookie()

    if (stored.value) {
      return
    }

    const raw = document.referrer

    if (!raw) {
      return
    }

    let origin: string
    try {
      origin = new URL(raw).origin
    } catch {
      return
    }

    // Un referrer interne n'apprend rien : il dit d'où l'on vient CHEZ NOUS, pas comment on nous a
    // trouvés. `window.location.origin` ne suffit pas — il vaut le sous-domaine courant, alors que
    // le saut vient d'un autre. C'est le domaine racine qu'il faut écarter, et tous ses enfants.
    if (origin.endsWith(baseDomain)) {
      return
    }

    // L'origine seule, jamais l'URL complète : les navigateurs la réduisent déjà à ça par défaut
    // (`strict-origin-when-cross-origin`), et une URL de recherche transporterait la requête tapée
    // — donc potentiellement des données personnelles qu'on n'a aucune raison de garder.
    stored.value = origin
  }

  const referrer = computed(() => cookie().value ?? null)

  return { capture, referrer }
}
