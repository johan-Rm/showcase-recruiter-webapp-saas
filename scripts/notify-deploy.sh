#!/usr/bin/env bash
#
# Prévient par e-mail qu'un déploiement s'est terminé sans erreur, en disant ce qu'il a apporté.
#
#   scripts/notify-deploy.sh <environnement>
#
# Appelé en DERNIÈRE étape de `make preprod-deploy` : make interrompt la chaîne à la première étape
# qui échoue, donc atteindre ce script signifie déjà que tout est passé. Rien d'autre n'a à être
# testé ici.
#
# TROIS RÔLES, ET ILS NE SE MÉLANGENT PAS :
#   - l'application POSSÈDE L'APPARENCE — elle compose le message avec le gabarit des e-mails du
#     produit, logos attachés compris (`app:deploy:compose-notification`, task API-065) ;
#   - ce script POSSÈDE CE QU'IL Y A À RACONTER — l'image de l'API ne contient que le code, jamais
#     l'historique du dépôt : la liste des changements ne peut venir que d'ici ;
#   - ce script POSSÈDE LE TRANSPORT — le mot de passe du serveur de messagerie vit sur cette
#     machine, jamais dans l'image, et en préproduction l'expéditeur de l'application est détourné
#     vers un attrape-mails pour qu'aucun message n'atteigne de vraies personnes.
#
# Aucun service tiers : les boîtes du domaine sont chez son hébergeur, que le SPF du domaine autorise
# déjà à envoyer en son nom, donc le message part authentifié. Le port par défaut est 465 (soumission
# chiffrée), pas 25 — c'est le 25 que les hébergeurs ferment.
#
# Cf. docs/showcase-recruiter-webapp-saas/content/2.architecture/docker-and-make.md § Déploiement & secrets

set -euo pipefail

readonly SMTP_PAR_DEFAUT="smtps://ssl0.ovh.net:465"
readonly MAX_CHANGEMENTS=15

racine="$(git rev-parse --show-toplevel)"
environnement="${1:-preprod}"
# La mémoire du dernier envoi RÉUSSI. Hors du dépôt : elle décrit cette machine, pas le code.
memoire="$racine/.deploy-notified-$environnement"

# shellcheck source=/dev/null
[ -f "$racine/.env" ] && . "$racine/.env"

destinataires="${DEPLOY_NOTIFY_TO:-}"
serveur="${DEPLOY_NOTIFY_SMTP_URL:-$SMTP_PAR_DEFAUT}"
compte="${DEPLOY_NOTIFY_SMTP_USER:-}"
motdepasse="${DEPLOY_NOTIFY_SMTP_PASSWORD:-}"
expediteur="${DEPLOY_NOTIFY_FROM:-Shortlist <$compte>}"
domaine="${BASE_DOMAIN:-showcase-recruiter-webapp-saas.example}"

# Une machine sans notification configurée déploie normalement : la notification est un confort, pas
# une étape du déploiement. Sortie 0, sinon un `.env` incomplet ferait échouer un déploiement réussi.
if [ -z "$destinataires" ] || [ -z "$compte" ]; then
	echo "→ notification ignorée (DEPLOY_NOTIFY_TO / DEPLOY_NOTIFY_SMTP_USER absents du .env)"
	exit 0
fi

# ─── Échappement JSON ─────────────────────────────────────────────────────────
# À la main : ni jq ni python ne sont garantis sur une machine de déploiement, et ce qui traverse est
# du texte libre — des intitulés de commit avec apostrophes et guillemets, des mots de passe avec
# n'importe quoi dedans.
#
# TROIS EXIGENCES, et chacune a déjà cassé ce script :
#   1. `$!{N;ba}` — la réunion des lignes est GARDÉE. Écrit `N` puis `$!ba`, le `N` s'exécute même
#      sur la dernière ligne : sed n'a alors rien à ajouter, il imprime et QUITTE avant les
#      substitutions. Un texte d'UNE SEULE ligne ressortait donc totalement non échappé — et dans
#      une liste, chaque entrée est une ligne unique. Le mail partait cassé dès qu'un intitulé de
#      livraison contenait un guillemet.
#   2. la réunion se fait AVANT les substitutions — un branchement revient après le label, donc des
#      substitutions placées avant ne verraient jamais les lignes 2 et suivantes.
#   3. les antislashs sont doublés AVANT les guillemets et les sauts de ligne, sinon les antislashs
#      que ces deux-là introduisent seraient doublés à leur tour.
echapper_json() {
	printf '%s' "$1" |
		sed -e ':a' -e '$!{N;ba' -e '}' -e 's/\\/\\\\/g' -e 's/"/\\"/g' -e 's/\t/\\t/g' -e 's/\n/\\n/g'
}

# Une liste JSON à partir de lignes, une entrée par ligne. Les lignes vides sont ignorées.
liste_json() {
	local entree resultat=""
	while IFS= read -r entree; do
		[ -n "$entree" ] || continue
		resultat="${resultat:+$resultat,}\"$(echapper_json "$entree")\""
	done
	printf '[%s]' "$resultat"
}

# ─── Ce que cette mise en ligne a apporté ─────────────────────────────────────
# Depuis le dernier envoi RÉUSSI, pas depuis la dernière récupération de code : la question à
# laquelle le lecteur veut une réponse est « qu'est-ce qui est nouveau depuis que tu m'as écrit ».
# Deux déploiements sans nouveau code ne racontent donc pas deux fois la même livraison.
sha="$(git -C "$racine" rev-parse --short HEAD)"
depuis=""
if [ -f "$memoire" ]; then
	depuis="$(tr -d '[:space:]' <"$memoire")"
	# Une mémoire qui ne désigne plus rien — dépôt recloné, historique réécrit — vaut pas de mémoire.
	git -C "$racine" cat-file -e "${depuis}^{commit}" 2>/dev/null || depuis=""
fi

# L'emballage technique tombe : un préfixe de type et une référence de tâche ne disent rien à qui
# lit son courrier. La phrase, elle, est déjà écrite en français dans ce dépôt.
if [ -n "$depuis" ]; then
	changements_bruts="$(git -C "$racine" log --format='%s' "${depuis}..HEAD" |
		sed -E 's/^[a-z]+(\([^)]*\))?(!)?: //; s/ — task [A-Z]+-[0-9]+$//')"
else
	changements_bruts=""
fi

total="$(printf '%s' "$changements_bruts" | grep -c . || true)"
changements="$(printf '%s\n' "$changements_bruts" | head -n "$MAX_CHANGEMENTS")"
restants=$((total > MAX_CHANGEMENTS ? total - MAX_CHANGEMENTS : 0))
[ "$restants" -gt 0 ] && changements="$changements
et $restants autre(s)"

# ─── Les accès de recette ─────────────────────────────────────────────────────
# Écrits ICI et non dans les réglages du serveur : ces trois accès sont les mêmes partout où ce
# script tourne — il n'existe qu'une préproduction — et la ficelle qu'il fallait composer dans le
# `.env` a fait partir trois mails sans le bloc avant que quiconque comprenne pourquoi. Une valeur
# identique sur toutes les machines n'est pas un réglage, c'est une constante.
#
# ⚠️ MOTS DE PASSE EN CLAIR — DÉROGATION ASSUMÉE. Le dépôt les interdit, et pour une bonne raison :
# un secret commité reste dans l'historique et chez tous ceux qui en ont une copie. L'exception tient
# à trois conditions, et cesse d'être défendable si l'une tombe :
#   1. préproduction JETABLE, sans aucune donnée réelle ;
#   2. mot de passe REPOSÉ à chaque `make preprod-reset PASSWORD=…` ;
#   3. JAMAIS réutilisé ailleurs, ni en production, ni pour un compte réel.
#
# Le sous-domaine est stocké seul, l'adresse se construit sur `$domaine` : une préproduction
# déplacée suivrait, là où une URL entière en dur afficherait des liens morts.
readonly ACCES_RECETTE=(
	"Boîte aux lettres de test|mailpit|showcase-recruiter-webapp-saas|change-me-demo-password"
	"Administration|admin|contact@showcase-recruiter-webapp-saas.example|change-me-demo-password"
	"Espace entreprise|entreprise|entreprise@showcase-recruiter-webapp-saas.example|change-me-demo-password"
)

# LE GARDE-FOU EST DÉSORMAIS LE SEUL REMPART : il n'y a plus de réglage à oublier de renseigner, donc
# plus de seconde condition. C'est une LISTE D'AUTORISATION et non d'interdiction — un environnement
# futur portant un autre nom sera traité comme une production, et c'est le sens de refus qu'on veut
# quand ce sont des mots de passe qui partent.
acces_json="[]"
case "$domaine" in
preprod.*)
	entrees=""
	for entree in "${ACCES_RECETTE[@]}"; do
		IFS='|' read -r libelle sous_domaine login mdp <<<"$entree"
		entrees="${entrees:+$entrees,}{\"label\":\"$(echapper_json "$libelle")\",\"url\":\"$(echapper_json "https://$sous_domaine.$domaine")\",\"login\":\"$(echapper_json "$login")\",\"password\":\"$(echapper_json "$mdp")\"}"
	done
	acces_json="[$entrees]"
	;;
*)
	# Un garde-fou silencieux qui se déclenche à tort passe pour une panne. Celui-ci s'annonce.
	echo "⚠ accès NON joints : « $domaine » n'est pas une préproduction — les identifiants ne partent que de là" >&2
	;;
esac

# ─── Le texte ─────────────────────────────────────────────────────────────────
case "$environnement" in
preprod) libelle_env="préproduction" ;;
prod) libelle_env="production" ;;
*) libelle_env="$environnement" ;;
esac

# Le nom du mois à la main : la machine de déploiement n'a aucune raison d'avoir une locale française.
mois=(x janvier février mars avril mai juin juillet août septembre octobre novembre décembre)
quand="$(date '+le %-d') ${mois[$(date '+%-m')]} $(date '+%Y à %H h %M')"

# Ces phrases tiennent sur UNE ligne, et c'est structurel : une entrée par ligne, un paragraphe par
# entrée. Un retour à la ligne dans le code couperait la phrase en deux paragraphes à l'écran.
if [ -z "$depuis" ]; then
	resume="C'est la première fois que ce déploiement s'annonce : la liste de ce qui a changé commencera au prochain."
	apercu="Mise en ligne terminée."
elif [ "$total" -eq 0 ]; then
	resume="Aucun changement depuis la dernière fois : le code en ligne est le même, il a simplement été redéployé."
	apercu="Aucun changement depuis la dernière mise en ligne."
else
	resume=""
	apercu="$total changement(s) en ligne."
fi

# ─── L'appel à l'application, qui compose le message ──────────────────────────
charge="$(mktemp)"
trap 'rm -f "$charge"' EXIT
{
	printf '{'
	printf '"from":"%s",' "$(echapper_json "$expediteur")"
	printf '"to":%s,' "$(printf '%s\n' "$destinataires" | tr ',' '\n' | sed 's/^[[:space:]]*//; s/[[:space:]]*$//' | liste_json)"
	printf '"subject":"%s",' "$(echapper_json "La $libelle_env est à jour")"
	printf '"preheader":"%s",' "$(echapper_json "$apercu")"
	printf '"heading":"%s",' "$(echapper_json "La $libelle_env est à jour")"
	printf '"paragraphs":%s,' "$(printf '%s\n' "La mise en ligne s'est terminée sans erreur, $quand. Le site répond." "$resume" | liste_json)"
	printf '"changes":%s,' "$(printf '%s\n' "$changements" | liste_json)"
	printf '"accesses":%s,' "$acces_json"
	printf '"cta":{"label":"%s","url":"%s"},' "$(echapper_json "Ouvrir la $libelle_env")" "$(echapper_json "https://entreprise.$domaine")"
	printf '"footerNote":"%s"' "$(echapper_json "Version en ligne : $sha")"
	printf '}'
} >"$charge"

message="$(mktemp)"
trap 'rm -f "$charge" "$message"' EXIT
if ! docker compose -f "$racine/docker-compose.$environnement.yml" exec -T api \
	php bin/console app:deploy:compose-notification <"$charge" >"$message" 2>/dev/null; then
	echo "⚠ notification NON envoyée : l'application n'a pas pu composer le message (le déploiement, lui, est bien passé)" >&2
	exit 0
fi

# ─── L'envoi ──────────────────────────────────────────────────────────────────
# Identifiants et destinataires passent par un fichier de configuration temporaire, jamais par la
# ligne de commande : un `--user "compte:motdepasse"` exposerait le mot de passe de la boîte dans
# `ps` à tout compte de la machine, le temps de la connexion.
echapper_config() {
	printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'
}

configuration="$(mktemp)"
trap 'rm -f "$charge" "$message" "$configuration"' EXIT
{
	printf 'url = "%s"\n' "$(echapper_config "$serveur")"
	printf 'user = "%s:%s"\n' "$(echapper_config "$compte")" "$(echapper_config "$motdepasse")"
	printf 'mail-from = "%s"\n' "$(echapper_config "$compte")"
	# Un destinataire par ligne : l'en-tête du message ne fait qu'afficher, c'est cette liste qui
	# livre. « Nom <adresse> » est accepté — seule l'adresse est retenue, sinon l'enveloppe partirait
	# malformée et le serveur refuserait ce destinataire sans que le déploiement le sache.
	printf '%s\n' "$destinataires" | tr ',' '\n' | while read -r adresse; do
		adresse="$(printf '%s' "$adresse" | sed -e 's/.*<//' -e 's/>.*//' | tr -d '[:space:]')"
		case "$adresse" in
		"") ;;
		*@*) printf 'mail-rcpt = "%s"\n' "$(echapper_config "$adresse")" ;;
		*) echo "⚠ destinataire ignoré, ce n'est pas une adresse : ${adresse}" >&2 ;;
		esac
	done
	printf 'upload-file = "%s"\n' "$message"
} >"$configuration"

if curl -fsS --config "$configuration"; then
	# La mémoire ne se met à jour QUE si le message est parti : un envoi raté n'emporte pas avec lui
	# ce qu'il devait annoncer, le suivant couvrira les deux mises en ligne.
	git -C "$racine" rev-parse HEAD >"$memoire"
	echo "→ notification envoyée à ${destinataires}"
else
	# Un mail qui ne part pas ne rend pas le déploiement raté : il a réussi, on le dit et on sort en 0.
	echo "⚠ notification NON envoyée (le déploiement, lui, est bien passé) — vérifier le compte ${compte} sur ${serveur}" >&2
fi
