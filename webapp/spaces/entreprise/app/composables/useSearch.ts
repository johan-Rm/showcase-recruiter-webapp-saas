import { isBroadSearch } from '#services/search/card'
import { cardsInSearch } from '#services/search/normalize'

import {
  loadSearchDismissed,
  loadSearchScreen,
  loadSearchSet,
  reloadSearchScreen,
} from '../bootstrap/loaders'
import { useCandidatesStore } from '../stores/candidates'
import { useReferentialsStore } from '../stores/referentials'
import { useSearchesStore } from '../stores/searches'
import { useUsersStore } from '../stores/users'

import type { BroadState } from '#services/search/card'
import type { CandidateCard } from '#shared/types/candidate-card'

/**
 * LA recherche ouverte : sa fiche et son jeu de résultats, servis aux deux outils.
 *
 * C'est un composable et non un store parce que ce n'est pas un domaine mais un ÉCRAN : il compose
 * trois domaines — la recherche, les candidats, les personnes — et porte l'appel BFF que ni l'un ni
 * l'autre n'a le droit de faire.
 *
 * Il fait aussi le chemin inverse de la normalisation : les stores gardent un pool de candidats et
 * des listes d'identifiants, les composants veulent des cartes complètes, avec leur favori et leur
 * nouveauté. La recomposition a lieu ICI, au dernier moment — les vues n'ont pas à connaître le
 * découpage des stores.
 *
 * Le `searchId` est un getter et non une valeur : passer d'une recherche à l'autre reste la MÊME
 * route, Vue ne rejoue pas le `setup` — sans `watch`, l'écran garderait le jeu précédent.
 */
export const useSearch = (searchId: MaybeRefOrGetter<string>) => {
  const searches = useSearchesStore()
  const candidates = useCandidatesStore()
  const referentials = useReferentialsStore()
  const users = useUsersStore()
  // `useRequestFetch` et non `$fetch` : au SSR, ce dernier ne rejoue pas le cookie de session et le
  // BFF répondrait 401 — panne invisible côté client, qui ne casse que le premier rendu.
  const request = useRequestFetch()

  /**
   * L'ouverture, déléguée au CHARGEUR de l'espace — celui-là même que le registre de bootstrap
   * déclare critique sur cette route.
   *
   * Un seul endroit sait donc ce qu'ouvrir une recherche demande, et les deux chemins d'arrivée s'y
   * retrouvent : le rendu serveur et la navigation client passent par l'orchestrateur, ce
   * `useAsyncData` couvre le reste — un changement de recherche sans changement de route, que le
   * middleware ne voit pas.
   *
   * Le chargeur porte ses propres gardes : la fiche ne part que si la collection ne l'a pas
   * apportée, le jeu que si le plan de travail ne l'a pas déjà posé.
   */
  const { pending, error } = useAsyncData(
    () => `entreprise:search:${toValue(searchId)}`,
    async () => {
      const id = toValue(searchId)
      searches.setCurrentId(id)

      const loader = loadSearchScreen(id)
      // La garde est ce qui empêche le doublon : l'orchestrateur a déjà pu ouvrir cette recherche,
      // au rendu serveur ou à la navigation. Sans elle, chaque appel partait deux fois.
      if (!loader.isLoaded()) await loader.load()

      // Le payload SSR porte déjà l'état des stores (@pinia/nuxt) : renvoyer le jeu le sérialiserait
      // une seconde fois.
      return true
    },
    { watch: [() => toValue(searchId)] },
  )

  /**
   * Tout relire, garde comprise — ce que les écrans appellent après un dévoilement.
   *
   * Ce n'est PAS le `refresh` de la donnée asynchrone : celui-ci rejouerait le chargeur, que sa
   * garde tairait aussitôt puisque le jeu est déjà en store. Un dévoilement change la fiche du
   * profil côté serveur ; il faut la redemander, pas constater qu'on l'a déjà.
   */
  const refresh = (): Promise<void> => reloadSearchScreen(toValue(searchId))

  /**
   * Retenir un profil, ou ne plus le retenir. Le seul geste de cet écran.
   *
   * OPTIMISTE : la carte bascule avant la réponse du serveur — retenir doit être instantané, et
   * l'attente d'un aller-retour ferait douter du clic. En cas d'échec on remet la valeur d'avant :
   * mieux vaut un geste qui se rétracte visiblement qu'un état qui ment jusqu'au prochain
   * rechargement.
   *
   * Le favori vit sur la RECHERCHE et non sur la carte : le même profil peut être retenu ici et pas
   * ailleurs. Aucun rechargement du jeu — un favori ne change ni le périmètre, ni l'ordre.
   */
  const applyFavorite = async (candidateId: string, wanted: boolean): Promise<void> => {
    const id = toValue(searchId)

    searches.setFavorite(id, candidateId, wanted)
    // La liste des retenus suit le geste : c'est elle qui peint l'onglet « Favoris », et attendre un
    // rechargement pour l'y voir apparaître donnerait un compteur qui bouge sans ses lignes.
    const retained = searches.getRetainedIds(id)

    searches.setRetainedIds(
      id,
      wanted
        ? [...new Set([...retained, candidateId])]
        : retained.filter((current) => current !== candidateId),
    )

    try {
      await (wanted
        ? request(`/api/searches/${id}/favorites`, { method: 'POST', body: { candidateId } })
        : request(`/api/searches/${id}/favorites/${candidateId}`, { method: 'DELETE' }))
    } catch (failure) {
      searches.setFavorite(id, candidateId, !wanted)

      throw failure
    }
  }

  const toggleFavorite = (candidate: CandidateCard): Promise<void> =>
    applyFavorite(candidate.id, true !== candidate.isFavorite)

  const selection = computed(() => searches.getSelectedIds(toValue(searchId)))

  /**
   * Les profils vus qui n'ont pas encore été déclarés au serveur.
   *
   * Marquer à chaque carte franchie ferait un appel par profil — plusieurs centaines pour un
   * passage complet. On accumule et on pousse par paquets, et l'état local part AVANT la réponse :
   * l'écran doit refléter le parcours immédiatement, pas au rythme du réseau.
   */
  const pendingSeen = new Set<string>()

  const flushSeen = async (): Promise<void> => {
    if (0 === pendingSeen.size) {
      return
    }

    // Vidé AVANT l'appel : ce qui part n'est plus en attente, et un marquage arrivé pendant
    // l'aller-retour appartient au paquet suivant plutôt qu'à celui-ci.
    const batch = [...pendingSeen]
    pendingSeen.clear()

    try {
      await request(`/api/searches/${toValue(searchId)}/reviews`, {
        method: 'POST',
        body: { candidateIds: batch, action: 'SEEN' },
      })
    } catch {
      // Un marquage perdu n'est pas une faute à montrer : le profil ressortira comme non vu, ce qui
      // est le comportement d'avant. Bruire l'écran pour ça coûterait plus que le manque.
    }
  }

  // Une seconde et demie après le DERNIER profil franchi : un défilement continu ne fait qu'un
  // appel, une lecture posée en fait un par pause.
  const { start: scheduleFlush } = useTimeoutFn(flushSeen, 1500, { immediate: false })

  // Quitter l'écran ne doit pas perdre le dernier paquet — au mieux il part, au pire il repartira
  // au prochain passage.
  onScopeDispose(() => void flushSeen())

  /** Un profil est vu quand il est RESTÉ à l'écran — le seuil vit dans la card, pas ici. */
  const markSeen = (candidateId: string): void => {
    const id = toValue(searchId)
    if (searches.getSeenIds(id).includes(candidateId)) {
      return
    }

    searches.setSeen(id, [candidateId])
    pendingSeen.add(candidateId)
    scheduleFlush()
  }

  /**
   * Écarter, ou remettre.
   *
   * ÉCARTER est optimiste comme le favori : la pile se resserre avant la réponse — le geste doit se
   * voir immédiatement, sinon on le repose. En cas d'échec on remet le profil où il était.
   *
   * REMETTRE relit le jeu : l'API sort les écartés de `resultIds`, le profil rendu n'y figure donc
   * pas et aucun état local ne peut l'y réinsérer à la bonne place — c'est le serveur qui range. Un
   * aller-retour sur un geste rare et correctif, contre un ordre inventé côté client.
   *
   * Écarter un profil RETENU efface le favori : ce sont deux verdicts opposés, et le dernier posé
   * l'emporte. Le serveur doit l'apprendre en deux appels — l'API n'a pas d'écriture qui porte les
   * deux — et la révocation part APRÈS l'écart : si elle échoue, il reste un favori de trop, pas un
   * profil disparu de la pile sans raison.
   */
  const setDismissed = async (candidateId: string, dismissed: boolean): Promise<void> => {
    const id = toValue(searchId)
    const wasFavorite = searches.getFavoriteIds(id).includes(candidateId)

    searches.setDismissed(id, [candidateId], dismissed)
    // Écarter sort aussi de la récolte, et l'API applique la même règle dans le périmètre
    // `favorites` — les deux doivent tomber d'accord.
    if (dismissed) {
      searches.setRetainedIds(
        id,
        searches.getRetainedIds(id).filter((retained) => retained !== candidateId),
      )
    }

    try {
      await request(`/api/searches/${id}/reviews`, {
        method: 'POST',
        body: { candidateIds: [candidateId], action: dismissed ? 'DISMISS' : 'RESTORE' },
      })
    } catch (failure) {
      searches.setDismissed(id, [candidateId], !dismissed)

      throw failure
    }

    if (dismissed && wasFavorite) {
      await applyFavorite(candidateId, false)
    }

    // REMETTRE relit le jeu, et le relit VRAIMENT : les chargeurs sont appelés sans leur garde,
    // celle-ci les tairait puisque le jeu est déjà en store. La fiche et les retenus ne bougent pas
    // — remettre un profil ne les concerne pas —, d'où deux appels et non l'ouverture entière.
    if (!dismissed) {
      await Promise.all([loadSearchSet(id).load(), loadSearchDismissed(id).load()])
    }
  }

  // Le jeu recomposé : l'ordre vient du serveur (`resultIds`), les cartes du pool, l'état de travail
  // des listes de la recherche. Une carte pas encore chargée est simplement absente, et un profil
  // ÉCARTÉ ne figure pas dans la pile — c'est ce qui rend le tri fini plutôt qu'infini.
  const cards = computed(() =>
    cardsInSearch(
      searches.getResultIds(toValue(searchId)),
      candidates.getPool,
      searches.getSet(toValue(searchId)),
    ),
  )

  /** Les écartés, dans l'ordre où ils l'ont été — la corbeille, d'où l'on remet. */
  const dismissed = computed(() => {
    const pool = candidates.getPool

    return searches
      .getDismissedIds(toValue(searchId))
      .map((id) => pool[id])
      .filter((candidate): candidate is CandidateCard => undefined !== candidate)
      .map((candidate) => ({ ...candidate, isDismissed: true }))
  })

  // PAS DE RANG « OÙ REPRENDRE » ici, ni de compte de non vus pour l'annoncer : les non vus passent
  // devant par défaut, il n'y a donc aucun rang à rejoindre. Ce que la pile fait de l'état « vu »
  // est un ORDRE, et il vit dans `services/search/stack.ts`.

  /**
   * VUS SUR LA RECHERCHE ENTIÈRE, et lus sur la liste que le serveur sert avec le jeu.
   *
   * Compté sur les cartes chargées, ce nombre annonçait 30 là où le serveur en comptait 74 : juste
   * sur ce qu'il regardait, faux sur ce qu'il prétendait dire. La liste, elle, porte la recherche
   * entière.
   *
   * Elle grandit toute seule pendant le parcours — `markSeen` y ajoute chaque profil franchi —, si
   * bien que le compteur monte au rythme de la lecture sans qu'aucun delta n'ait à être tenu à côté.
   */
  const seenCount = computed(() => searches.getSeenIds(toValue(searchId)).length)

  return {
    search: computed(() => searches.getSearchById(toValue(searchId))),
    ownerName: computed(() => {
      const search = searches.getSearchById(toValue(searchId))

      return search ? users.getUserName(search.ownerId) : ''
    }),
    toggleFavorite,
    selection,
    toggleSelected: (candidateId: string) =>
      searches.toggleSelected(toValue(searchId), candidateId),
    clearSelection: () => searches.clearSelection(toValue(searchId)),
    markSeen,
    dismiss: (candidateId: string) => setDismissed(candidateId, true),
    restore: (candidateId: string) => setDismissed(candidateId, false),
    dismissed,
    seenCount,
    /**
     * RETENUS et ÉCARTÉS, sur le jeu entier eux aussi — et pris sur les LISTES D'IDENTIFIANTS du
     * store, non sur les cartes.
     *
     * Les deux listes arrivent du serveur sans pagination et vivent dans le store, donc traversent
     * le rendu serveur. Les compter en cartes les limitait à ce que le pool contient : « Favoris »
     * annonçait 0 sur une recherche qui en portait 14, parce que les cartes correspondantes
     * n'étaient chargées que dans le navigateur, et le chargeur d'ouverture ne rejoue pas là.
     */
    favoriteCount: computed(() => searches.getFavoriteIds(toValue(searchId)).length),
    dismissedCount: computed(() => searches.getDismissedIds(toValue(searchId)).length),
    candidates: cards,
    // CE QU'ON PARCOURT — les identifiants servis, cent au plus depuis que le jeu est borné. C'est
    // le dénominateur du rang, celui qui décrit la pile qu'on traverse.
    total: computed(() => searches.getResultIds(toValue(searchId)).length),
    /**
     * CE QUE LA RECHERCHE RAMÈNE VRAIMENT, borne non comprise.
     *
     * Les deux nombres diffèrent dès qu'une recherche déborde, et c'est tout l'intérêt : sans
     * celui-ci, le recruteur croirait le vivier plus pauvre qu'il n'est. Il vient de la fiche, seul
     * endroit où le compte réel voyage — le jeu servi, lui, s'arrête à la borne.
     */
    matchCount: computed(() => searches.getSearchById(toValue(searchId))?.resultCount ?? 0),
    /**
     * TROP LARGE POUR SE TRIER — un conseil, jamais un blocage, EN TROIS VALEURS.
     *
     * Il porte sur ce que la recherche RAMÈNE et non sur ce qui est chargé : c'est le travail
     * restant qu'on annonce, pas la taille de la pile du moment.
     *
     * Trois valeurs et non un oui/non, parce que ce conseil se RETIENT d'une fois sur l'autre. Un
     * drapeau binaire suffit à décider d'un affichage — tant qu'on ne sait pas, on n'affiche pas —,
     * mais il confond « non » et « je ne sais pas encore » : le premier doit effacer la mémoire,
     * le second la laisser intacte. Les confondre, c'est réannoncer chaque recherche au rechargement
     * de la page, le temps que le seuil arrive.
     */
    broadState: computed((): BroadState => {
      const search = searches.getSearchById(toValue(searchId))

      if (null === search || 0 === referentials.getSearchLoadLimit) return 'unknown'

      return isBroadSearch(search, referentials.getSearchLoadLimit) ? 'broad' : 'narrow'
    }),
    /**
     * Les retenus, DANS LEUR ENTIER — et non ceux que la page chargée contient.
     *
     * Recomposés depuis le pool comme la pile l'est : l'ordre vient du serveur, l'état de travail
     * des listes de la recherche.
     */
    favorites: computed(() =>
      cardsInSearch(
        searches.getRetainedIds(toValue(searchId)),
        candidates.getPool,
        searches.getSet(toValue(searchId)),
      ),
    ),
    pending,
    error,
    refresh,
  }
}
