#!/usr/bin/env bash
#
# Prépare le dossier de travail d'un espace : worktree Git + fichiers non versionnés + `.env`
# portant son bloc de ports et son domaine, pour que sa stack Docker tourne EN MÊME TEMPS que
# celle de l'arbre principal.
#
#   scripts/worktree-init.sh <espace> [chemin]
#
# Idempotent et non destructif : un worktree, un `.env` ou une clé déjà présents ne sont jamais
# écrasés — le script les signale et passe. Il ne démarre aucun conteneur.
#
# Cf. docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md

set -euo pipefail

readonly ESPACES="api entreprise candidat admin public socle-webapp outillage"

# Fichiers indispensables à une stack et absents d'un worktree neuf : ils ne sont pas versionnés.
# `api/vendor` et `webapp/node_modules` n'y figurent pas — trop lourds à copier, ils se réinstallent
# (`make dev-setup`), et node_modules vit de toute façon dans un volume Docker.
readonly PREREQUIS=(".env" "api/.env" "api/.env.test" "api/config/jwt/private.pem" "api/config/jwt/public.pem")

usage() {
	cat >&2 <<-EOF
		Usage : scripts/worktree-init.sh <espace> [chemin]

		  espace   ${ESPACES// /, }
		  chemin   dossier du worktree (défaut : worktree/<espace>)
	EOF
	exit 2
}

# Rang de l'espace dans la table des ports. L'arbre principal occupe le rang 0 ; chaque espace
# décale tous ses ports de 100 × son rang. Table complète : .env.example § Stack d'un worktree.
rang_de() {
	local i=1
	for espace in $ESPACES; do
		[ "$espace" = "$1" ] && { echo "$i"; return 0; }
		i=$((i + 1))
	done
	return 1
}

# Espace Nuxt allumé par défaut dans la stack. Les espaces sans front (api, outillage, socle) ont
# besoin d'une interface pour être exercés : entreprise, la plus complète. Modifiable dans le `.env`.
front_de() {
	case "$1" in
		entreprise | candidat | admin | public) echo "$1" ;;
		*) echo "entreprise" ;;
	esac
}

# Pose `CLE=valeur` dans un fichier d'env : remplace la ligne existante, ou l'ajoute à la fin.
poser_var() {
	local cle="$1" valeur="$2" fichier="$3"

	if grep -qE "^${cle}=" "$fichier"; then
		# Délimiteur `|` : les valeurs contiennent des points, jamais de barre verticale.
		sed -i "s|^${cle}=.*|${cle}=${valeur}|" "$fichier"
	else
		printf '%s=%s\n' "$cle" "$valeur" >>"$fichier"
	fi
}

[ $# -ge 1 ] || usage
espace="$1"
rang="$(rang_de "$espace")" || { echo "espace inconnu : $espace" >&2; usage; }

racine="$(git rev-parse --show-toplevel)"
cible="${2:-$racine/worktree/$espace}"
branche="espace/$espace"

# ─── 1. Le worktree ───────────────────────────────────────────────────────────
if [ -e "$cible" ]; then
	echo "→ worktree déjà présent : $cible"
else
	if git show-ref --verify --quiet "refs/heads/$branche"; then
		git worktree add "$cible" "$branche"
	else
		git fetch origin --quiet
		git worktree add -b "$branche" "$cible" origin/develop
	fi
	echo "→ worktree créé : $cible ($branche)"
fi

# ─── 2. Les fichiers non versionnés ───────────────────────────────────────────
for fichier in "${PREREQUIS[@]}"; do
	if [ -e "$cible/$fichier" ]; then
		echo "→ $fichier déjà présent, laissé tel quel"
	elif [ -e "$racine/$fichier" ]; then
		mkdir -p "$(dirname "$cible/$fichier")"
		cp "$racine/$fichier" "$cible/$fichier"
		echo "→ $fichier copié depuis l'arbre principal"
	else
		echo "⚠ $fichier absent de l'arbre principal — à créer avant de démarrer la stack" >&2
	fi
done

# ─── 3. Le bloc de ports et le domaine ────────────────────────────────────────
env_cible="$cible/.env"
[ -f "$env_cible" ] || { echo "✖ $env_cible introuvable : impossible de poser les ports" >&2; exit 1; }

decalage=$((rang * 100))
poser_var COMPOSE_PROJECT_NAME "showcase-recruiter-webapp-saas-$espace" "$env_cible"
poser_var DEV_BASE_DOMAIN "wt-$espace.localhost" "$env_cible"
poser_var DEV_SPACE "$(front_de "$espace")" "$env_cible"
poser_var DEV_PROXY_PORT "$((8080 + decalage))" "$env_cible"
poser_var DEV_OAUTH_PORT "$((8082 + decalage))" "$env_cible"
poser_var DEV_API_PORT "$((18082 + decalage))" "$env_cible"
poser_var DEV_DB_PORT "$((5432 + decalage))" "$env_cible"
poser_var DEV_MAILPIT_UI_PORT "$((18025 + decalage))" "$env_cible"

cat <<-EOF

	Stack « showcase-recruiter-webapp-saas-$espace » prête à être installée :

	  cd $cible
	  make dev-setup          # composer install, clés JWT, schéma, fixtures, comptes (une fois)
	  make dev-up-space       # socle + l'espace $(front_de "$espace")

	  http://$(front_de "$espace").wt-$espace.localhost:$((8080 + decalage))/
	  http://api.wt-$espace.localhost:$((8080 + decalage))/     mails : http://localhost:$((18025 + decalage))/
EOF
