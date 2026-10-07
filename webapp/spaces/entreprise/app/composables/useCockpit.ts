/**
 * État d'interface partagé de la coque cockpit (layout `cockpit`).
 *
 * L'inspecteur (droite) est un tiroir : le layout expose la zone, les pages
 * décident quand l'ouvrir — d'où un état partagé plutôt que local au layout.
 * `useState` le garde réactif et SSR-safe sans provide/inject.
 */
export function useCockpit() {
  const inspectorOpen = useState('cockpit:inspector-open', () => false)

  const openInspector = () => {
    inspectorOpen.value = true
  }
  const closeInspector = () => {
    inspectorOpen.value = false
  }

  return { inspectorOpen, openInspector, closeInspector }
}
