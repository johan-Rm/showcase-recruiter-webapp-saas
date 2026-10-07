# TASK ENT-017 — les types du contrat remis en phase

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise — contrat.
- **Réf** : `API-017` (la surface qui a changé) · `ENT-013` (qui la consomme).

## Contexte

`API-017` a ajouté `organizationId` à `/v1/me` et régénéré `api/openapi.json`. Les types TypeScript
du front, eux, **dérivent** de ce contrat — et n'ont pas suivi.

La gate `quality-contracts-check` régénère `shared/types/api.ts` puis compare : elle échoue tant que
le fichier commité ne correspond pas au contrat. La CI **webapp** rougissait donc sur une
modification purement **API**, ce qui n'aide pas à comprendre d'où vient la panne.

```
Contrat — types en phase avec l'OpenAPI
+            organizationId?: string | null;
error: script "quality-contracts-check" exited with code 1
```

## Décision

Régénérer, et rien d'autre. Le fichier est **produit**, jamais écrit à la main : le diff se lit à une
ligne près et n'a pas à être relu comme du code.

### Pourquoi cette carte existe sur la lane entreprise

Parce que la PR api était déjà mergée quand la CI a parlé. Le bon geste aurait été de régénérer les
deux fichiers dans le commit d'`API-017` — **le contrat et ses types dérivés voyagent ensemble**, une
modification de surface API touche donc toujours `webapp/`.

À défaut, la réparation part avec la lane qui consomme le champ.

## Critères d'acceptation

- [x] `quality-contracts-check` passe.
- [x] Le diff se limite à la ligne que le contrat a gagnée.
