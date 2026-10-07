# TASK ENT-111 — entreprise : la fiche du profil remonte d'un cran

- **Créée le** : 2026-08-13.
- **Session** : `4556ea83-bbbd-44e0-87f7-811ecc1e067b`.
- **Statut** : ✅ fait le 2026-08-13 — la fiche du profil est rendue à `#333333`, et l'écart avec la
  colonne du verdict passe de treize à dix-huit points.
- **Type** : réglage de teinte. Un caractère. Aucune donnée, aucune règle, aucun geste ne change.
- **Tests** : `sans objet` — la carte ne produit aucune logique. La preuve est la **couleur calculée**
  du bloc, lue sur le rendu.
- **Réf** : `ENT-092` — qui a posé les trois paliers de profondeur et la dilution à cinquante pour
  cent.

## Contexte

`ENT-092` a donné à la carte du profil trois paliers de profondeur : la fiche avance, la colonne du
verdict tient le ton des cartes, les cartouches descendent au fond. La fiche est obtenue par
**dilution** du gris franc du nuancier, faute d'un palier intermédiaire.

Trois valeurs avaient été proposées au moment de la poser :

```
/40  →  #2a2a2a   plus sombre
/50  →  #2e2e2e   ← posé
/60  →  #333333   le plus clair des trois
```

**Cinquante a été retenu par défaut, pas par choix** : la valeur en place a été validée d'un « c'est
bon » sans que les deux autres soient regardées côte à côte. À l'usage, la fiche manque d'un cran de
séparation d'avec la colonne qu'elle jouxte.

## Périmètre

- **La dilution passe de cinquante à soixante pour cent.**

## Hors périmètre

- **Les deux autres paliers** — la colonne du verdict et les cartouches — qui ne bougent pas.
- **Le principe de la dilution**, ses raisons et ses réserves : tout est déjà écrit dans `ENT-092`.
- **Le nuancier**, qui reste à ses cinq échelles.

## Décisions de cadrage

### Soixante, et non un palier neuf

`ENT-092` avait créé puis retiré deux demi-paliers — `shark-750` et son pendant clair — parce qu'une
dilution donnait la même valeur sans toucher au nuancier. Soixante pour cent rend `#333333`, à un
point du `shark-750` alors abandonné. **Le rouvrir n'apporterait rien** : le nuancier reste intact, et
un palier créé pour un seul usage est exactement ce qu'on avait renoncé à faire.

### La réserve de `ENT-092` tient toujours

Une dilution dépend de ce qu'il y a derrière. Tant que ce bloc est posé sur le fond de page, la valeur
est garantie ; posé ailleurs, il changerait de couleur sans que rien ne le signale. C'est accepté
depuis `ENT-092` et rien ici ne le modifie — ce bloc est la carte principale d'une page, il ne voyage
pas.

## Règles et cas limites

- **Le thème clair suit le même jeton** : la valeur y est plus claire, et le rapport entre les trois
  paliers est conservé. L'élévation s'y inverse, comme partout ailleurs.
- **Les deux autres paliers restent inchangés**, donc l'écart entre la fiche et la colonne grandit
  d'autant.

## Documentation impactée

Aucune page de `docs/showcase-recruiter-webapp-saas/` : la charte décrit les jetons et l'inversion d'élévation, pas la
valeur de dilution d'un bloc particulier. Aucun `README.md` ne change de convention.

## Vérification

### Les trois paliers, rendus

```
                      déclaré                   sombre                clair
la fiche profil       --surface-soft à 60 %     #333333               plus clair, même rapport
la colonne            --surface                 rgb(33, 33, 33)       rgb(226, 233, 227)
la cartouche          --bg                      rgb(26, 26, 26)       rgb(236, 240, 239)
```

Les deux autres paliers sont inchangés, donc **l'écart entre la fiche et la colonne passe de 13 à 18
points** : la séparation se voit mieux sans qu'aucun trait n'apparaisse.

### Le nuancier n'a pas bougé

`#333333` tombe à un point du `shark-750` que `ENT-092` avait créé puis retiré. La dilution donne la
même valeur sans rouvrir les cinq échelles — c'était précisément l'argument qui avait fait retirer ce
demi-palier.

## Livré

| Fichier | Geste |
|---|---|
| `spaces/entreprise/app/pages/recherches/[uuid]/index.vue` | (M) la dilution de la fiche passe de 50 à 60 % |

## Reste à faire (hors périmètre)

- **Rien.** Les deux autres paliers et le nuancier sont intacts.

## Définition de terminé

- [x] La fiche du profil est rendue à la valeur attendue en thème sombre.
- [x] Les deux autres paliers sont inchangés.
