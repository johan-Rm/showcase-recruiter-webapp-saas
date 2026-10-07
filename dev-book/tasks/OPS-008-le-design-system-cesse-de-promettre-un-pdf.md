# TASK OPS-008 — le design system cesse de promettre un PDF

- **Créée le** : 2026-07-30.
- **Statut** : ✅ fait le 2026-07-30 — section retirée, plus aucune mention du PDF.
- **Type** : Docs — correction d'une documentation qui décrivait un artefact inexistant.
- **Tests** : sans objet — la tâche ne retire que du Markdown, aucun code exécutable.
- **Réf** : task `OPS-004` (qui a introduit la section)

## Contexte

`OPS-004` a écrit dans `design-system.md` une section « L'export PDF » annonçant qu'« une capture
figée de la galerie est publiée ici », avec un lien `/ux-kit.pdf` et la procédure pour le
régénérer. Le fichier n'a jamais été déposé, et il ne le sera pas : la décision est prise de ne pas
produire cet export.

La section est donc **fausse** dans une documentation publiée, et son lien est mort.

## Décisions de cadrage

### Retirer plutôt que reformuler

Une phrase du type « un export PDF pourra être ajouté » ne vaut rien : elle documente une intention,
pas un système. La doc ne décrit que ce qui existe.

### La galerie reste documentée

Seule la sous-section PDF disparaît. Le paragraphe « La galerie UX Kit » qui la précède décrit la
route `/ux-kit`, qui existe et reste la référence à ouvrir avant de dessiner un écran.

## Règles et cas limites

La leçon dépasse ce correctif : la règle « ne rien documenter qui n'existe pas » était écrite pour
`dev-book/cadrage/`, parce que ce dossier part chez le client. Elle vaut tout autant pour `docs/`,
où une promesse non tenue se lit comme un fait. `OPS-004` l'a enfreinte en documentant un artefact
**à venir** comme s'il était en place.

## Documentation impactée

- `docs/showcase-recruiter-webapp-saas/content/4.design/design-system.md` — section « L'export PDF » retirée.
- `docs/showcase-recruiter-webapp-saas/content/4.design/index.md` — le sommaire ne l'annonce plus.

## Définition de terminé

- [x] Plus aucune mention de `ux-kit.pdf` dans `docs/showcase-recruiter-webapp-saas/content/4.design/`.
- [x] La section « La galerie UX Kit » reste intacte et exacte.
- [x] Le sommaire de section est aligné.
