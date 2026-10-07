/**
 * État partagé du tiroir de navigation mobile de l'espace admin.
 * Le hamburger (Topbar) l'ouvre, le tiroir (MobileDrawer) le consomme.
 */
export const useDrawer = () => {
  const isOpen = useState('mobile-drawer-open', () => false)

  return {
    isOpen,
    open: () => {
      isOpen.value = true
    },
    close: () => {
      isOpen.value = false
    },
  }
}
