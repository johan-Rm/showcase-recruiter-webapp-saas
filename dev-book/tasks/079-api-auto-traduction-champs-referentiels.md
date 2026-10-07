# TASK 079 — API : auto-traduction des champs traduisibles (`#[AutoTranslate]` / `#[AutoFill]`)

- **Créée le** : 2026-07-13.
- **Statut** : 🔭 **différé V2** — non commencé, aucun code.
- **Type** : confort de contribution / i18n.
- **Dépend de** : socle i18n (livré).
- **Réf ClickUp** : _à créer_.

## Contexte

L'API est trilingue (FR / EN / AR). Les libellés traduisibles — référentiels (`CategoryCode`),
descriptions, contenus éditoriaux — doivent aujourd'hui être saisis **manuellement dans les trois
langues**. Quand une seule est fournie, la ressource est incomplète et rien ne le signale.

Un mécanisme d'auto-traduction avait été décrit dans la documentation d'architecture (attributs
`#[AutoTranslate]` et `#[AutoFill]`, mode stub) **sans jamais être implémenté**. La description est
retirée de `docs/` — qui ne décrit que ce qui existe — et l'intention est conservée ici.

## Objectif

Qu'un champ marqué comme traduisible se remplisse **automatiquement** dans les langues manquantes, à
partir de la langue source, sans que le contributeur ait à le faire.

## Périmètre pressenti

- Un **attribut PHP** posé sur la propriété traduisible, qui déclare la langue source.
- Au moment de la persistance : les traductions absentes sont **générées**, les traductions déjà
  saisies à la main sont **respectées** (jamais écrasées).
- Un **mode stub** en développement et en test : aucune traduction n'est appelée, une valeur
  reconnaissable est posée — la CI ne dépend d'aucun service externe.
- Le **fournisseur de traduction** reste à choisir (service externe, LLM, table de correspondance).

## Invariants

- Une traduction **saisie à la main fait foi** : l'automatisme ne l'écrase jamais.
- **Aucun appel externe en test ni en CI.**
- Le mécanisme est **transparent pour le contrat d'API** : la ressource expose ses traductions, quelle
  que soit leur origine.

## Pourquoi c'est différé

La saisie manuelle tient à l'échelle actuelle : les référentiels sont peu nombreux et bougent peu.
Le sujet redevient prioritaire le jour où du **contenu éditorial** entre dans le produit, ou si le
catalogue de référentiels s'ouvre à des contributeurs non techniques.
