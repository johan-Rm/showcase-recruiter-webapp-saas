# TASK ENT-021 — entreprise : « Mon profil »

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-003` (les quatre rubriques du compte) · `ENT-019` (même coque, même découpage).

## Contexte

Onze lignes : un titre, une phrase. C'est la dernière des quatre coquilles posées par `ENT-003`.

L'API sert peu, et c'est le cœur du sujet :

```
GET /v1/me            identité, rôles, contextes — LECTURE seule
PUT /v1/me/password   changement de mot de passe
```

## Décisions

### L'identité se lit, elle ne s'édite pas

**Aucune route ne permet de la modifier** : ni `PUT /v1/me`, ni `PATCH`. L'écran l'affiche donc en
lecture, et le dit — un champ grisé sans explication laisse croire à une panne.

C'est une limite du produit, pas un choix d'écran : un recruteur qui change de nom ou de poste doit
aujourd'hui passer par nous. La combler demande une carte API (`PUT /v1/me`), qui n'existe pas.

### Le mot de passe est le seul geste

Ancien mot de passe **et** nouveau : l'API exige les deux, et c'est ce qui distingue un changement
d'une prise de contrôle par une session volée.

La checklist de robustesse vient de `usePasswordPolicy`, déjà écrite pour la définition de mot de
passe — les deux écrans doivent dire la même chose, et l'API reste juge.

### Langue et apparence restent dans le menu

`ENT-003` l'a tranché : le menu de l'avatar les garde. Les répéter ici donnerait deux endroits pour
le même réglage, dont l'un serait oublié à la première évolution.

## Critères d'acceptation

- [x] L'identité s'affiche : nom, adresse, rôle dans l'entreprise, poste.
- [x] L'écran dit pourquoi elle n'est pas modifiable.
- [x] Le mot de passe se change, avec l'ancien exigé et la checklist de robustesse.
- [x] Un mot de passe refusé par l'API le dit sans vider les champs.

## Mise en œuvre

**L'identité vient de la liste des MEMBRES, pas de `/v1/me`.** Cette dernière porte le nom et les
rôles applicatifs, mais ni le poste ni le rôle DANS l'organisation — or c'est cela qu'on vient lire
ici. La ligne qui me concerne est celle qui porte mon adresse.

## Vérifié en réel

```
écran rendu   Omar Alaoui · atlas.owner@… · Propriétaire · Directeur Talent
              « ces informations ne sont pas modifiables »
mauvais mot de passe actuel → 403
changement complet          → 200
```

**Le changement invalide la session** : le jeton d'avant ne passe plus, il faut se reconnecter. Ce
n'est pas géré par l'écran — le formulaire annonce le succès et l'utilisateur reste sur une session
morte jusqu'à sa prochaine requête. À traiter : reconnecter en silence, ou renvoyer vers la connexion
en le disant.

## La vraie limite

**Aucune route ne permet d'éditer l'identité** — ni `PUT /v1/me`, ni `PATCH`. Un recruteur qui change
de nom ou de poste doit passer par nous. La combler demande une carte API, qui n'existe pas.
