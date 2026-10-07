/**
 * Option d'un dropdown fermé (UiInlineSelect).
 *
 * Deux formes : une chaîne, quand le libellé EST la valeur stockée (secteurs) ; un couple
 * `{ label, value }`, quand l'affiché diffère du stocké (pays : « Maroc » affiché, « MA » stocké).
 */
export type SelectOption = string | { readonly label: string; readonly value: string }
