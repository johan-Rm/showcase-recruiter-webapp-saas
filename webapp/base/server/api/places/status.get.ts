import { placesConfigured } from '../../utils/places'

/**
 * Dit au client si l'autocomplétion d'adresse est disponible — c.-à-d. si une clé Places est posée.
 * Le formulaire s'en sert pour choisir son mode D'EMBLÉE (recherche d'adresse vs saisie manuelle),
 * sans « flip » après quelques frappes. Source unique : la clé serveur, jamais exposée elle-même.
 */
export default defineEventHandler(() => ({ configured: placesConfigured() }))
