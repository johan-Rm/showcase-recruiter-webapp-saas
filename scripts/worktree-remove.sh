#!/usr/bin/env bash
#
# Démonte la stack d'un espace et supprime son dossier de travail.
#
#   scripts/worktree-remove.sh <espace> [chemin]
#
# Les conteneurs écrivent en root dans le worktree (`api/vendor`, `api/var`, `webapp/.nuxt`) : un
# `git worktree remove` échoue en « Permission denied » tant que ces chemins sont là. Le script les
# efface depuis un conteneur, seul à en avoir le droit — par chemins nommés, jamais en effaçant le
# dossier entier.
#
# Deux refus avant toute destruction : un arbre sale, ou des commits absents de `origin/develop`.
# Le second se lève avec FORCE=1, jamais le premier.
#
# Cf. docs/showcase-recruiter-webapp-saas/content/1.getting-started/parallel-work.md

set -euo pipefail

# Chemins écrits par les conteneurs, hors suivi Git. Liste explicite : c'est elle qui garantit
# qu'aucun fichier source ne passe par le `rm` privilégié.
readonly CHEMINS_ROOT=("api/vendor" "api/var" "webapp/node_modules" "webapp/.nuxt" "webapp/.env")

[ $# -ge 1 ] || { echo "Usage : scripts/worktree-remove.sh <espace> [chemin]" >&2; exit 2; }
espace="$1"
racine="$(git rev-parse --show-toplevel)"
cible="${2:-$racine/worktree/$espace}"

[ -d "$cible" ] || { echo "→ $cible : rien à supprimer"; exit 0; }

# ─── Garde-fous ───────────────────────────────────────────────────────────────
sale="$(git -C "$cible" status --porcelain)"
if [ -n "$sale" ]; then
	echo "✖ travail non commité dans $cible — rien n'a été supprimé :" >&2
	echo "$sale" >&2
	exit 1
fi

git -C "$cible" fetch origin --quiet 2>/dev/null || true
avance="$(git -C "$cible" log --oneline origin/develop..HEAD 2>/dev/null || true)"
if [ -n "$avance" ] && [ "${FORCE:-0}" != "1" ]; then
	echo "✖ commits absents de origin/develop — relancer avec FORCE=1 pour les perdre :" >&2
	echo "$avance" >&2
	exit 1
fi

# ─── Démontage ────────────────────────────────────────────────────────────────
if [ -f "$cible/.env" ]; then
	(cd "$cible" && docker compose -f docker-compose.dev.yml down -v) || true
fi

chemins_conteneur=()
for chemin in "${CHEMINS_ROOT[@]}"; do
	chemins_conteneur+=("/cible/$chemin")
done
docker run --rm -v "$cible:/cible" alpine rm -rf "${chemins_conteneur[@]}"

git worktree remove --force "$cible" 2>/dev/null || rm -rf "$cible"
git worktree prune

echo "→ $cible supprimé (stack démontée, volumes compris)"
