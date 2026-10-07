# TASK ENT-018 — entreprise : « L'équipe » cesse d'être une coquille

- **Créée le** : 2026-07-29.
- **Statut** : ✅ fait le 2026-07-29.
- **Type** : Frontend espace entreprise.
- **Réf** : `ENT-003` (les quatre rubriques du compte) · task 009 (l'invitation côté API).

## Contexte

Onze lignes : un titre, une phrase. La rubrique existe dans la navigation depuis `ENT-003` et ne
montre rien.

L'API, elle, sert tout ce qu'il faut :

```
GET    /v1/organizations/{id}/members                    la liste
DELETE /v1/organizations/{id}/members/{recruiterId}      retirer
GET    /v1/organizations/{organizationId}/invitations    les invitations en cours
POST   /v1/organizations/{organizationId}/invitations    inviter
```

## Décisions

### Une équipe, c'est deux listes

Les **membres**, qui sont là, et les **invitations en attente**, qui n'y sont pas encore. Les
mélanger en une seule liste demanderait une colonne « statut » pour dire lequel est lequel — deux
listes le disent par leur seule existence.

Une invitation n'est d'ailleurs pas une personne : elle a une adresse et une date d'expiration, pas
un nom ni un poste.

### On n'invite que des MEMBRES

L'API n'accepte que `ROLE_COMPANY_MEMBER` sur une invitation — le propriétaire est celui qui a créé
l'organisation, il ne se délègue pas. Pas de sélecteur de rôle : un champ à valeur unique est un
champ qui ment sur le choix qu'il offre.

Le rôle reste **affiché** sur la liste des membres : savoir qui est propriétaire explique pourquoi
certains boutons manquent.

### Seul le propriétaire écrit

Inviter et retirer lui appartiennent (cf. `useAccountSections`, qui masque déjà la rubrique à un
membre). L'écran reste néanmoins lisible par un membre qui arriverait par l'adresse : il voit son
équipe, sans les gestes.

### Se retirer soi-même n'a pas de sens

Le propriétaire ne peut pas se retirer de sa propre organisation : elle se retrouverait sans personne
pour l'administrer. Le bouton n'est pas offert sur sa propre ligne — plutôt qu'offert puis refusé.

### Le changement de rôle n'est pas de cette carte

Aucune route ne l'expose (task 026b, à faire). L'écran ne montre donc pas un geste qui échouerait.

## Critères d'acceptation

- [x] Les membres sont listés avec leur nom, leur rôle et leur poste.
- [x] Les invitations en attente forment une seconde liste, avec leur date d'expiration.
- [x] Le propriétaire invite par e-mail, et voit l'invitation apparaître sans recharger.
- [x] Le propriétaire retire un membre, jamais lui-même.
- [x] Un membre voit l'équipe sans les gestes d'écriture.

## Vérifié en réel

```
membres     Omar Alaoui (Propriétaire) · Leila Mansouri (Membre)
invitation  future.recruiter@atlas-talent.example · expire le 01/08/2026
POST        nouvelle.recrue@… → ROLE_COMPANY_MEMBER, expire le 2026-08-12
```

## Le piège qui a coûté le plus cher

Le placeholder du champ d'invitation contenait une adresse d'exemple. **`@` introduit un LIEN vers un
autre message en vue-i18n** : non échappé, il fait échouer la compilation du catalogue **entier**, et
toutes les clés de la locale retombent sur leur chemin brut — y compris `app.name`, et des écrans que
la carte ne touchait pas.

```
ERROR [unplugin-vue-i18n] Invalid linked format (error code: 10) in fr.yaml
WARN  Failed to load messages for locale "fr"
```

Le YAML était valide, les deux parseurs testés le lisaient sans broncher : ce n'est pas le YAML qui
refuse, c'est le compilateur de messages. Le diagnostic était **dans les logs du serveur de dev
depuis le début**.

Échappement : `"prenom.nom{'@'}entreprise.com"`. À retenir pour toute adresse d'exemple dans une
locale.
