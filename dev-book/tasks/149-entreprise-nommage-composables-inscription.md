# TASK 149 — entreprise : nommer les composables d'inscription par famille

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — renommage seul, aucun changement de comportement.
- **Type** : Frontend espace entreprise — nommage.

## Contexte

`useCompanyRegistration` portait le domaine en second (`Company` + `Registration`). Dans une layer
d'espace qui EST déjà l'entreprise, le préfixe redit ce que le chemin dit, et un futur composable
d'inscription candidat serait parti dans une autre direction alphabétique.

## Décision

`useRegistrationCompany` : la famille d'abord, la variante ensuite. Un `useRegistrationCandidate`
viendra se ranger à côté au lieu de vivre à l'autre bout de la liste.

**Le nom court `useRegistration` était écarté** : il existe déjà dans le socle
(`base/app/composables/useRegistration.ts`, consommé par `login.vue` et le layout `auth`). Un
composable d'espace portant ce nom **masquerait** celui du socle — la layer d'espace gagne — et
`login.vue` recevrait `{ createOrganization, pending, error, fieldErrors }` au lieu de
`{ available, registering, registerPath }`. L'écran de connexion de l'espace entreprise serait cassé.

> Les deux ne parlent d'ailleurs pas de la même chose : celui du socle décrit **l'entrée** du tunnel
> sur l'écran d'auth (le tunnel est-il ouvert ici, est-on sur `?inscription`), celui de l'espace
> **exécute** l'inscription. Renommer le socle en `useRegistrationEntry` reste possible, mais c'est
> un changement transverse qui n'a pas sa place dans un renommage d'espace.

## Livré

- `useCompanyRegistration` → `useRegistrationCompany`, et son unique appelant.
