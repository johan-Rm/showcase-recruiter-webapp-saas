# TASK 159 — entreprise : lancer une recherche depuis l'accueil

- **Créée le** : 2026-07-27.
- **Statut** : ✅ fait le 2026-07-27 — vérifié en session réelle sur le dev.
- **Type** : Frontend espace entreprise.
- **Réf** : `dev-book/cadrage/entreprise/ui/arborescence.md` (le parcours),
  `console_contextuelle.md` §4 (auto-enregistrement et nommage).

## Contexte

Les deux moteurs de l'accueil — critères et langage naturel — collectaient la saisie et **ne
faisaient rien** : `onSearch` refermait le moteur, et le BFF n'exposait que des relais en lecture.
Un recruteur qui se connectait pouvait consulter la recherche de démo, jamais en créer une. C'était
le premier trou du parcours, et il précédait tous les autres.

## Décisions

### Lancer, c'est créer — et ça mène à la Visionneuse

Une recherche s'auto-enregistre dès le premier critère : « Enregistrer » n'est pas un verbe de
showcase-recruiter-webapp-saas. Et on lance pour **parcourir**, pas pour contempler : la création navigue directement vers
la Visionneuse, jamais vers la liste.

### Sans critère, on ne crée rien

Le vivier entier n'est pas un résultat de recherche. Un formulaire vide ne crée donc pas une
recherche vide : l'écran le dit et ne part pas.

### Le nom est DÉRIVÉ, pas persisté

Le nom se compose des critères, dans leur ordre — un groupe de facette par segment, trois segments
au plus puis « +N », et le séjour au Maroc nommé comme tel en tête de son segment.

Il n'est **pas envoyé à la création** : l'API désactive l'auto-nommage dès qu'un nom est fourni
(`isAutoNamed: '' === $name`). Persister le composé figerait un nom qui mentirait au premier critère
changé. Le front recompose donc à l'affichage tant que `name` est vide.

### Le moteur assisté se lance en deux temps

Comprendre la phrase, puis lancer ce qu'on a validé. Les critères écartés à l'écran ne partent pas.
Un seul bouton aurait mélangé « relire ma phrase » et « chercher ».

## Pertes assumées du mapping formulaire → moteur

Le moteur ne retient **qu'une valeur par facette** (« le dernier gagne », V1) et n'expose pas tout ce
que l'UI collecte :

| Ce que l'écran collecte | Ce qui part | Pourquoi |
| --- | --- | --- |
| années d'expérience | **rien** | aucune facette d'ancienneté côté moteur |
| plusieurs groupes de mots-clés | une chaîne aplatie | une seule valeur par facette |
| plusieurs langues | la première | idem |

Ces pertes sont **écrites dans le service**, pas silencieuses : le jour où l'API cumule, c'est là
qu'on revient.

## Livré

- `services/search/criteria.ts` : formulaire → critères, critères IA → critères, et le nom
  automatique — métier pur, 11 tests.
- `base/server/api/searches.post.ts` : le relais de création.
- `useSearches().create()` : crée et range la recherche dans la collection, sans second appel.
- L'accueil lance, navigue, et dit quand il ne peut pas.
- Le nom dérivé s'affiche sur la carte et dans le panneau de la Visionneuse.

## Reste à faire (hors périmètre de cette tâche)

- **Facette d'ancienneté** côté API, pour que le critère collecté serve.
- **Cumul multi-valeurs** par facette (OR dans une facette, AND entre facettes — cf. cadrage).
- **Renommer une recherche** à la main, et le retour à l'auto en vidant le champ.
