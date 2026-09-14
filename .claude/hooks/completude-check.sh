#!/usr/bin/env bash
# Garde-fou de fin de tâche — KLASSCI
#
# Empêche de rendre la main sur un travail incomplet.
#
# Principe : pas de cérémonie sur les échanges conversationnels. Le compte
# rendu n'existe que si une vraie tâche est ouverte (voir le skill
# `fin-de-tache`). Mais dès qu'il existe, il doit être fini avant de rendre
# la main — sinon on retombe exactement sur le défaut que ce hook existe
# pour supprimer : un travail rendu à moitié, et un utilisateur qui doit
# relancer.
#
# Voir .claude/skills/fin-de-tache/SKILL.md

set -uo pipefail

input=$(cat)

# Sans jq, impossible de lire stop_hook_active : la sécurité anti-récursion
# n'existe plus, et bloquer reviendrait à boucler sans issue. On le dit et on
# laisse passer, plutôt que de retomber en silence sur « bloque quand même ».
# (Git Bash pour Windows ne livre pas jq — voir klassci-local-test-suite.md.)
if ! command -v jq >/dev/null 2>&1; then
  echo "completude-check : jq absent, garde-fou desactive (anti-recursion impossible)." >&2
  exit 0
fi

# Anti-récursion : si ce hook a déjà bloqué une fois sur ce tour, on laisse
# passer. Sans ça, une case qu'on n'arrive pas à cocher boucle indéfiniment.
if [[ "$(printf '%s' "$input" | jq -r '.stop_hook_active // false' 2>/dev/null)" == "true" ]]; then
  exit 0
fi

REPO="${CLAUDE_PROJECT_DIR:-$(git rev-parse --show-toplevel 2>/dev/null || pwd)}"
REC="$REPO/.claude/completude.md"

# Aucun compte rendu ouvert : rien à vérifier.
[[ -f "$REC" ]] || exit 0

restes=$(grep -cE '^[[:space:]]*[-*][[:space:]]\[[[:space:]]\]' "$REC" 2>/dev/null || true)
gabarit=$(grep -cE 'À COMPLÉTER' "$REC" 2>/dev/null || true)

# Cocher une case ne prouve rien : c'est une auto-déclaration. Ce qui la rend
# vérifiable, c'est la ligne du tableau « Vérifié, et comment » qui dit PAR QUEL
# MOYEN on le sait. Sans ce rapport, on obtient un compte rendu entièrement coché
# qui affirme des choses fausses — c'est exactement ce qui est arrivé le
# 14/09/2026 : six citations de rules pointaient vers des méthodes, des fichiers
# et des chiffres qui n'existaient pas, toutes dans des tâches « terminées ».
faits=$(grep -cE '^[[:space:]]*[-*][[:space:]]\[[xX]\]' "$REC" 2>/dev/null || true)
preuves=$(awk '/^\|/ && !/^\|[[:space:]]*-/ && !/Comment je le sais/ {n++} END{print n+0}' "$REC" 2>/dev/null || echo 0)
preuves_maigres=0
if [[ "${faits:-0}" -ge 3 && "${preuves:-0}" -lt $(( faits / 2 )) ]]; then
  preuves_maigres=1
fi

if [[ "${restes:-0}" -gt 0 || "${gabarit:-0}" -gt 0 || "$preuves_maigres" -eq 1 ]]; then
  {
    echo "Compte rendu de tâche incomplet — .claude/completude.md"
    echo
    if [[ "${restes:-0}" -gt 0 ]]; then
      echo "  $restes point(s) non coché(s) :"
      grep -nE '^[[:space:]]*[-*][[:space:]]\[[[:space:]]\]' "$REC" | head -12 | sed 's/^/    /'
      echo
    fi
    if [[ "${gabarit:-0}" -gt 0 ]]; then
      echo "  Le gabarit contient encore « À COMPLÉTER »."
      echo
    fi
    if [[ "$preuves_maigres" -eq 1 ]]; then
      echo "  $faits point(s) coché(s) pour seulement $preuves ligne(s) de preuve."
      echo "  Une case cochée est une affirmation ; le tableau « Vérifié, et comment »"
      echo "  est ce qui la rend vérifiable. Pour chaque affirmation qui nomme un"
      echo "  fichier, une méthode, une ligne ou un chiffre : dis par quelle commande"
      echo "  tu le sais. Si tu ne l'as pas exécutée, ne l'affirme pas."
      echo
    fi
    echo "Avant de rendre la main, pour chaque point restant : soit tu le fais,"
    echo "soit tu le déplaces dans « Non fait, et pourquoi » en l'expliquant."
    echo
    echo "Et avant de conclure qu'un point est bloqué, vérifie que tu as employé"
    echo "les moyens dont tu disposes — l'utilisateur ne devrait pas avoir à te"
    echo "les rappeler :"
    echo "  - recherche internet, pour tout ce qui porte sur le monde extérieur"
    echo "  - les rules de .claude/rules/ et la mémoire projet"
    echo "  - l'agent critique-transversale, pour un second angle"
    echo "  - /thermo-review avant tout commit, fusion ou déploiement"
    echo "  - /klassci-test-e2e et une capture réelle, pour prouver un écran"
  } >&2
  exit 2
fi

exit 0
