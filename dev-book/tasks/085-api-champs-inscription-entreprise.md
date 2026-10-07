# TASK 085 — API : champs d'inscription entreprise (secteur, provenance, description, fonction)

- **Créée le** : 2026-07-13.
- **Statut** : ✅ livré le 2026-07-13.
- **Type** : API — modèle de données + contrat d'inscription.
- **Débloque** : tâches `082` (formulaire d'inscription) · `083` (fiche de revue admin).
- **Réf cadrage** : `dev-book/cadrage/entreprise/api/inscription.md` (§ Informations demandées,
  § Provenance de l'entreprise).

## Contexte

Le cadrage décrit le formulaire d'inscription (raison sociale, pays, site web, immatriculation,
**secteur**, **fonction du recruteur**, **provenance**, **description**). L'entité `Organization`
n'en portait que la moitié : ni secteur, ni provenance, ni description. La provenance manquait donc
à la fiche de revue admin, alors que le cadrage en fait un **signal de légitimité**.

## Livré

- Enum `AcquisitionChannel` (recommandation, LinkedIn, recherche web, salon, contact direct, autre).
- `Organization` : `sector`, `description`, `acquisitionChannel`, `acquisitionChannelDetail`.
  **Mise à jour du 2026-07-13** : `sector` était d'abord une relation vers le référentiel
  `CategoryCode` ; ce référentiel a été supprimé (l'ingestion Jarvi ne l'alimentait jamais), et le
  secteur est devenu une **valeur libre**.
- La **fonction du recruteur** existait déjà (`Recruiter::jobTitle`) mais n'était jamais renseignée :
  elle se saisit désormais à l'inscription.
- `OrganizationInput` : `sector`, `description`, `jobTitle`, `acquisitionChannel`,
  `acquisitionChannelDetail`. Les règles remontent automatiquement au front via `/v1/validation-rules/organization`
  (tâche `069`).
- `OrganizationOutput` : `sector` + les trois nouveaux champs.

## Règles

- **Provenance obligatoire à la création** (contrat modifié : un POST sans provenance → 422) et
  **figée ensuite** — le PUT ne la réécrit pas. C'est une donnée d'acquisition, pas de profil.
- **« Autre » exige une précision** : la valeur seule n'apprend rien.
- **Secteur** : valeur libre, bornée en longueur (plus de vocabulaire à respecter).

## Vérifié

Suite fonctionnelle complète verte (139 tests). Deux tests ajoutés : provenance obligatoire /
« Autre » sans précision / provenance figée au PUT, et secteur inconnu rejeté. `api/openapi.json` et
`webapp/shared/types/api.ts` régénérés (gate `contracts-drift`).

## Reste

Le **push Jarvi** à la validation (tâche `023`) ignore ces champs : la provenance ne doit
volontairement pas y aller (donnée interne showcase-recruiter-webapp-saas), mais secteur et description pourraient un jour
enrichir la company Jarvi. Non traité, non nécessaire au MVP.
