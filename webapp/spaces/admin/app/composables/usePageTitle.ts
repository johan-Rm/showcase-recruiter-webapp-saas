import type { MaybeRefOrGetter } from 'vue'

/**
 * Le titre de l'écran courant : la page le déclare, la coque l'affiche.
 *
 * Le libellé de la barre latérale ne suffisait pas. Sur huit écrans, SIX portent un titre plus riche
 * que leur entrée de navigation — « Entreprises » y devient « Entreprises — file de revue » — et
 * trois écrans ont un titre que la navigation ne connaît pas du tout : le nom d'un candidat, le nom
 * d'une métrique.
 *
 * LE TITRE EST RANGÉ AVEC SA ROUTE, et c'est tout le mécanisme. Le piège d'un état partagé est le
 * titre rémanent : une page le pose, la suivante l'oublie, et l'utilisateur lit un nom d'écran faux
 * sans qu'aucune erreur ne le signale. Le vider à la sortie de page ne suffirait pas — pendant une
 * navigation, la page qui arrive peut poser son titre AVANT que celle qui part ne libère le sien, et
 * le nettoyage effacerait alors le bon. En rangeant le titre avec le chemin auquel il appartient, un
 * titre d'ailleurs est simplement ignoré : il n'y a plus d'ordre à respecter.
 */
interface PageTitle {
  path: string
  title: string
}

export const usePageTitle = (title?: MaybeRefOrGetter<string>) => {
  const state = useState<PageTitle | null>('page-title', () => null)
  const route = useRoute()

  // Déclarer : `watchEffect` et non une affectation, pour qu'un titre chargé après coup — le nom
  // d'un candidat — remonte de lui-même.
  if (title !== undefined) {
    watchEffect(() => {
      state.value = { path: route.path, title: toValue(title) }
    })
  }

  // Lire : null si le titre rangé n'est pas celui de la route affichée. L'appelant décide de son
  // repli — la coque retombe sur le libellé de navigation.
  const pageTitle = computed(() => (state.value?.path === route.path ? state.value.title : null))

  return { pageTitle }
}
