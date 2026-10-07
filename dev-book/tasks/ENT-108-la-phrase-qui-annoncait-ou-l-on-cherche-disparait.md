# TASK ENT-108 — entreprise : la phrase qui annonçait où l'on cherche disparaît

- **Créée le** : 2026-08-13.
- **Session** : `a976bcf6-f820-47e2-8d17-9e731cbcc4fd`.
- **Statut** : ✅ fait le 2026-08-13 — la phrase a quitté les trois langues, l'attribut qui l'appelait
  a quitté le composant, et le commentaire qui la justifiait dit maintenant où la règle de
  l'élargissement est réellement énoncée au recruteur.
- **Type** : Frontend entreprise — retrait de contenu devenu sans lecteur. Aucun contrat, aucune
  donnée, aucune règle métier touchée.
- **Tests** : `sans objet — aucun code exécutable`. Rien ne se calcule : trois lignes de fichier de
  langue et un attribut de gabarit disparaissent. Le **test de parité des langues**
  (`webapp/base/i18n/locales/parity.vitest.ts`) est en revanche impacté et doit rester vert — c'est
  lui qui refuse qu'une clé survive dans une langue et plus dans une autre. **La preuve attendue est
  double** : ce test, et une recherche dans tout le dépôt montrant qu'aucune référence ne subsiste.
- **Réf** : `webapp/base/i18n/locales/{fr,en,ar}.yaml` (`search.primary.keywords.hint`) ·
  `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` ·
  `webapp/base/app/components/ui/Field.vue` (l'aide y est facultative) · aval qui rendait cette
  phrase fausse de toute façon : `API-040`.

## Contexte

**La phrase.** Sous le champ des mots-clés, une aide annonce : « Cherchés dans l'intitulé et les
compétences. Chaque mot ajouté ÉLARGIT le résultat — un profil suffit à en satisfaire un seul. »

**Personne ne la lit, et c'est vérifié.** Son unique appelant est `FiltersPrimary.vue`, le formulaire
de critères qui **n'est rendu sur aucune page** depuis que l'écran de la phrase de recherche l'a
remplacé. Aucun recruteur ne voit ce texte aujourd'hui.

**Sa première moitié est en sursis.** Une tâche serveur cadrée le même jour (`API-040`) élargit la
recherche par mot-clé au texte de présentation et aux expériences : « cherchés dans l'intitulé et
les compétences » deviendra alors **faux**. Le choix produit est de ne pas la corriger mais de la
retirer — le recruteur découvrira la provenance par les fonctionnalités qui arrivent, et non par une
phrase d'aide.

**Sa seconde moitié est déjà dite, mieux et ailleurs.** « Chaque mot ajouté élargit le résultat » est
porté par le titre visible de la colonne — **« Qui maîtrise l'un de ces mots-clés »** —, où le
« l'un de » énonce la même règle au moment où l'on s'en sert, sans avoir à lire une note.

## Périmètre

- **La clé `search.primary.keywords.hint` disparaît des trois langues.**
- **L'attribut qui l'appelle disparaît** de l'encadré de formulaire dans `FiltersPrimary.vue`.
- **Le commentaire qui justifiait cette aide est réécrit**, pas effacé : il porte une observation qui
  reste vraie — poser plusieurs mots-clés élargit, ce qui est contre-intuitif — et doit dire où cette
  règle est désormais énoncée.

## Hors périmètre

- **`FiltersPrimary.vue` lui-même**, qui reste en place. Sa suppression est une décision qui se prend
  pour elle-même, et elle demande de vérifier quelles autres clés de traduction deviendraient
  orphelines avec lui. Déjà signalé par `ENT-104` et `ENT-107` ; toujours pas ouvert.
- **Les autres clés éventuellement orphelines** du dépôt : aucune chasse générale ici.
- **Le titre de la colonne** « Qui maîtrise l'un de ces mots-clés », qui ne bouge pas — c'est lui qui
  reprend la charge.
- **L'aide affichée dans la gouttière** de l'écran de recherche, qui est un autre texte.

## Décisions de cadrage

**On retire, on ne corrige pas, arbitré avec l'utilisateur le 2026-08-13.** Corriger la phrase pour
qu'elle énumère quatre champs au lieu de deux aurait produit une note plus longue, à refaire au
prochain élargissement, pour une information que l'écran s'apprête à montrer autrement — la
provenance apparaîtra là où elle se constate, sur le profil trouvé.

**La clé part en entier, et pas seulement sa première phrase.** Garder la seconde en réserve
laisserait un texte traduit dans trois langues, appelé par du code que rien n'affiche : un contenu
sans lecteur qu'une relecture future prendrait pour un oubli.

**La clé et son appel partent dans le même commit, et c'est une contrainte, pas une préférence.**
Aucune règle de la chaîne d'intégration ne surveille les clés de traduction orphelines : le test de
parité compare les trois langues entre elles, il ne sait pas si une clé est appelée quelque part. Un
appel laissé derrière survivrait donc en silence, et afficherait le chemin de la clé le jour où
quelqu'un rendrait ce composant à nouveau visible.

**Le commentaire est réécrit plutôt que supprimé.** Il énonce que l'ajout d'un mot-clé élargit le
résultat « ce que l'aide doit dire — c'est contre-intuitif ». L'observation reste juste ; c'est
l'aide qui disparaît. Effacer le commentaire ferait croire à un oubli et quelqu'un rétablirait la
note de bonne foi.

## Règles et cas limites

- **Rien ne change à l'écran**, puisque rien ne s'affichait. La preuve n'est donc pas une capture
  mais une absence : aucune référence restante dans le dépôt.
- **L'aide est facultative** sur l'encadré de formulaire qui la recevait : retirer l'attribut ne rend
  aucune propriété manquante, et le paragraphe qui l'affichait ne se rend simplement plus.
- **Le lien d'accessibilité suit tout seul, et proprement.** L'encadré compose l'identifiant
  décrivant le champ à partir de ce qui est présent — l'aide, l'erreur — et **ne rend rien du tout**
  quand il n'y a ni l'une ni l'autre. Aucun attribut vide n'est donc posé sur le champ. *(Vérifié à
  l'implémentation : le cadrage supposait un identifiant pointant dans le vide, ce n'est pas le cas.)*
- **Le test de parité doit rester vert** : la clé part des trois langues, pas d'une seule.
- **Aucune autre clé ne bouge**, y compris les voisines du même bloc.

## Documentation impactée

**Aucune.** `docs/showcase-recruiter-webapp-saas/content/2.architecture/i18n.md` décrit le fonctionnement de la couche de
traduction et la règle de parité, pas l'inventaire des clés — retirer une clé conforme ne le modifie
pas. Aucun `README.md` de dossier ne change de convention. Ni route, ni contrat, ni jeton de design
n'est touché.

## Définition de terminé

- [x] `search.primary.keywords.hint` n'existe plus dans aucun des trois fichiers de langue.
- [x] Plus aucun fichier du dépôt ne référence cette clé — prouvé par une recherche.
- [x] Le commentaire du composant dit où la règle de l'élargissement est énoncée, sans renvoyer à
      une aide qui n'existe plus.
- [x] Le test de parité des langues passe.
- [x] `FiltersPrimary.vue` existe toujours et n'a perdu que cet attribut.

## Vérification

Preuves prises le 2026-08-13 dans le worktree entreprise. **Cette tâche ne se prouve pas par une
capture** : elle retire un texte que rien n'affichait, donc l'écran est identique avant et après.
Ce qui se prouve, c'est l'absence.

| Ce qui est prouvé | Ce qui a été lu |
| --- | --- |
| Aucune référence à la clé ne subsiste dans le dépôt | recherche sur `keywords.hint` dans tous les fichiers suivis, hors `dev-book/` : **aucun résultat** |
| La clé a quitté les trois langues | seules les deux autres aides du bloc subsistent — `ar` 483/509, `fr` 490/516, `en` 486/512 — à parité |
| L'attribut a quitté le composant | seul celui de l'intitulé de poste reste, ligne 199, intact |
| Les trois langues restent à parité | **9 tests verts** (`parity.vitest.ts`) |
| Les conventions du dépôt tiennent | **19 règles, aucune violation** |
| Le diff ne déborde pas | 4 fichiers, 4 lignes ajoutées, 5 retirées |

**Le lien d'accessibilité, vérifié dans le code plutôt que supposé** : l'encadré de formulaire
compose l'attribut décrivant le champ à partir de l'aide et de l'erreur, et rend `undefined` quand
il n'a ni l'une ni l'autre. Aucun attribut vide n'est posé — cf. la correction apportée aux *Règles
et cas limites*.

Aucune gate complète n'a été jouée : elles appartiennent à l'ouverture de la PR. Le test de parité
et la vérification des conventions l'ont été parce que le diff les vise directement.

## Livré

- `webapp/base/i18n/locales/{fr,en,ar}.yaml` — la clé `search.primary.keywords.hint` disparaît des
  trois langues.
- `webapp/spaces/entreprise/app/components/search/FiltersPrimary.vue` — l'attribut qui l'appelait
  disparaît ; le commentaire du bloc garde l'observation utile — poser plusieurs mots-clés élargit le
  résultat, ce qui est contre-intuitif — et dit désormais deux choses de plus : que l'intitulé de
  colonne de l'écran de recherche l'énonce au moment où l'on s'en sert, et qu'une note énumérant les
  endroits fouillés vieillirait à chaque élargissement du moteur.

## Reste à faire (hors périmètre)

- **`FiltersPrimary.vue` reste en place alors que rien ne l'affiche.** Sa suppression demande de
  vérifier quelles autres clés de traduction deviendraient orphelines avec lui. Signalé pour la
  troisième fois — `ENT-104`, `ENT-107`, ici — et toujours pas ouvert.
- **Rien ne surveille les clés de traduction orphelines** dans la chaîne d'intégration : le test de
  parité compare les langues entre elles, il ignore si une clé est appelée quelque part. Un verrou
  de conventions pourrait le faire ; ce serait une tâche à part.
