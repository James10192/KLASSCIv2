#!/usr/bin/env bash
#
# CI locale : rejoue les controles de .github/workflows/*.yml quand les
# runners GitHub ne demarrent pas (facturation du compte, panne). Elle ne
# remplace pas la CI, elle en reproduit les etapes pour qu'une fusion ne se
# fasse jamais sans elles.
#
#   bash scripts/ci-local.sh            # ou : composer ci
#
# Base de donnees : une instance MariaDB ISOLEE, demarree puis arretee par ce
# script, sur un repertoire et un port a elle. Jamais la base partagee du poste.
#
# Variables (toutes facultatives) :
#   CI_BASE=origin/presentation   branche de comparaison (messages, changelog, diff)
#   PHP=php                       binaire PHP 8.3
#   COMPOSER_BIN="composer"       commande composer (pas COMPOSER : Composer y lit le chemin de composer.json)
#   MYSQLD=mysqld                 serveur MariaDB (sous XAMPP : /c/xampp/mysql/bin/mysqld.exe)
#   MYSQL_INSTALL_DB=mysql_install_db   initialisation du repertoire de donnees
#   MYSQL=mysql                   client
#   CI_DATADIR=${TMPDIR:-/tmp}/klassci-ci-mariadb   donnees de l'instance isolee
#   CI_DB_PORT=3317               port de l'instance isolee
#   CI_TESTS_BRANCHE="..."        filtre PHPUnit des tests de la branche (vide : sautes)
set -uo pipefail

CI_BASE="${CI_BASE:-origin/presentation}"
PHP="${PHP:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
MYSQLD="${MYSQLD:-mysqld}"
MYSQL_INSTALL_DB="${MYSQL_INSTALL_DB:-mysql_install_db}"
MYSQL="${MYSQL:-mysql}"
CI_DATADIR="${CI_DATADIR:-${TMPDIR:-/tmp}/klassci-ci-mariadb}"
CI_DB_PORT="${CI_DB_PORT:-3317}"
CI_TESTS_BRANCHE="${CI_TESTS_BRANCHE-AnalyseurEmailTest|EnumsVerificationTest|ReglesEmailsFormulairesTest|VerificationC|DepotReinscriptionVerifieTest|NettoyerEmailsFacticesTest|DiagnosticEmailsCliTest|CliJoignabiliteTest|SynchroConvocationsTest|AdresseDeCompteTest|PortailPublicExportTest|CorbeilleDemandesTest|DebitPortailTest|RendezVous|MailPulseClientTest|RepriseConvocationsVerifieesTest}"

ECHECS=()
AVERTISSEMENTS=()
etape() { printf '\n==== %s\n' "$1"; }
echec() { ECHECS+=("$1"); printf '  ECHEC : %s\n' "$1"; }
avert() { AVERTISSEMENTS+=("$1"); printf '  AVERTISSEMENT : %s\n' "$1"; }
# Tout ce qui touche a la base : on s'arrete net plutot que de continuer sur
# une connexion incertaine.
abandon() { printf '\nCI LOCALE : ABANDON (%s)\n' "$1"; exit 1; }

cd "$(dirname "$0")/.." || exit 2

# ---------------------------------------------------------------- isolation
# Des le depart, et pas seulement avant la migration : un test « sans base »
# qui ouvrirait quand meme une connexion ne doit jamais atteindre la base du
# poste. `.env.testing` vise le port 3306, et un `config:cache` oublie dans
# bootstrap/cache ignorerait toutes ces variables : les caches sont rediriges
# vers des fichiers qui n'existent pas, sous le repertoire de l'instance isolee.
[ "$CI_DB_PORT" = "3306" ] && abandon "CI_DB_PORT=3306 est le port de la base partagee"
mkdir -p "$CI_DATADIR" || abandon "repertoire $CI_DATADIR impossible a creer"
DATADIR_NATIF=$(cygpath -m "$CI_DATADIR" 2>/dev/null || printf '%s' "$CI_DATADIR")
REP_CACHES="$(cd "$CI_DATADIR" && pwd -P)/laravel-cache"
mkdir -p "$REP_CACHES" || abandon "repertoire des caches impossible a creer"
# config, routes, events : ces fichiers ne doivent pas exister (personne ne
# fait config:cache ici) ; services : Laravel l'ecrit la, pas dans bootstrap/cache.
for f in config routes events; do
    [ -e "$REP_CACHES/$f.php" ] && abandon "$REP_CACHES/$f.php existe : un cache viendrait fausser la connexion"
done
# Laravel ne tient un chemin de cache pour absolu que s'il commence par / ou \ :
# sous Windows, le lecteur (C:) est retire et les separateurs sont des \.
if command -v cygpath > /dev/null 2>&1; then
    CACHES=$(cygpath -w "$REP_CACHES"); CACHES="${CACHES#?:}"; SEP='\'
else
    CACHES="$REP_CACHES"; SEP=/
fi
export APP_CONFIG_CACHE="${CACHES}${SEP}config.php" APP_ROUTES_CACHE="${CACHES}${SEP}routes.php" \
    APP_EVENTS_CACHE="${CACHES}${SEP}events.php" APP_SERVICES_CACHE="${CACHES}${SEP}services.php"
export APP_ENV=testing DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT="$CI_DB_PORT" DB_DATABASE=klassci_testing DB_USERNAME=root DB_PASSWORD=

# ---------------------------------------------------------------- hygiene
etape "Messages de commit ($CI_BASE..HEAD)"
for sha in $(git rev-list "$CI_BASE..HEAD"); do
    [ "$(git rev-list --parents -n 1 "$sha" | wc -w)" -gt 2 ] && continue
    message=$(git log -1 --format='%B' "$sha"); sujet=$(git log -1 --format='%s' "$sha"); court=$(git log -1 --format='%h' "$sha")
    printf '%s\n' "$message" | grep -qiE '^[[:space:]]*Co-Authored-By:' && echec "$court : signature Co-Authored-By"
    printf '%s\n' "$message" | grep -qiE 'Generated with|noreply@anthropic\.com|Claude-Session:' && echec "$court : mention d'outil"
    printf '%s\n' "$sujet" | grep -qE '^(feat|fix|refactor|perf|docs|test|chore|build|ci|style|revert)(\([a-z0-9._/-]+\))?!?: .+' || echec "$court : message non conventionnel ($sujet)"
done

etape "Trace d'un changement visible (CHANGELOG)"
fichiers=$(git diff --name-only "$CI_BASE...HEAD")
touche_appli=$(printf '%s\n' "$fichiers" | grep -cE '^(app|resources|routes|database)/' || true)
changelog=$(printf '%s\n' "$fichiers" | grep -cE '^CHANGELOG\.md$' || true)
visible=$(git log "$CI_BASE..HEAD" --format='%s' | grep -cE '^(feat|fix)' || true)
if [ "$visible" -gt 0 ] && [ "$touche_appli" -gt 0 ] && [ "$changelog" -eq 0 ] \
    && ! git log "$CI_BASE..HEAD" --format='%B' | grep -q '\[sans-changelog\]'; then
    echec "changement visible sans CHANGELOG.md"
fi

etape "Fraicheur du modal Nouveautes (avertit seulement)"
"$PHP" bin/verifier-fraicheur-nouveautes.php || avert "modal Nouveautes en retard sur le CHANGELOG"

# ---------------------------------------------------------------- lint
etape "Syntaxe PHP (app, config, routes)"
while IFS= read -r -d '' f; do
    "$PHP" -l "$f" > /dev/null 2>&1 || echec "syntaxe : $f"
done < <(find app config routes -name '*.php' -print0)

etape "Noms de classes irresolus (fichiers modifies)"
mapfile -t modifies < <(git diff --name-only --diff-filter=d "$CI_BASE...HEAD" -- '*.php')
if [ ${#modifies[@]} -gt 0 ]; then
    "$PHP" bin/verifier-classes.php "${modifies[@]}" || echec "noms de classes irresolus"
    # Sous Windows la casse ne compte pas ; en production (Linux, PSR-4) si.
    "$PHP" bin/verifier-casse-psr4.php "${modifies[@]}" || echec "namespace ou classe dont la casse differe du chemin"
fi

etape "Directives Blade (avertit seulement)"
while IFS= read -r f; do
    o=$(grep -c "@if\|@foreach\|@for\|@while" "$f" || true); c=$(grep -c "@endif\|@endforeach\|@endfor\|@endwhile" "$f" || true)
    [ "$o" != "$c" ] && [ -n "$(git diff --name-only "$CI_BASE...HEAD" -- "$f")" ] && avert "Blade desequilibre : $f"
done < <(find resources/views -name '*.blade.php')

etape "composer validate --strict"
$COMPOSER_BIN validate --strict --no-interaction > /dev/null || echec "composer.json invalide"

# ---------------------------------------------------------------- securite
etape "Audit des dependances deployees"
$COMPOSER_BIN audit --locked --no-dev --abandoned=ignore --no-interaction || avert "composer audit : avis a lire (ou API injoignable)"
git ls-files --error-unmatch .env > /dev/null 2>&1 && echec ".env versionne"

# ---------------------------------------------------------------- tests sans base
etape "Tests unitaires (sans base)"
for chemin in tests/Unit/Services/Frais tests/Unit/Paiements tests/Unit/Models/SettingRepliSurDefautTest.php \
    tests/Unit/Services/FraisAudienceTest.php tests/Unit/Services/ExtensionsDeRoleTest.php tests/Unit/Exploitation \
    tests/Feature/Exploitation tests/Unit/Deployment tests/Unit/Routes tests/Unit/Comptabilite tests/Unit/Photos \
    tests/Unit/Services/Master; do
    [ -e "$chemin" ] || continue
    "$PHP" -d memory_limit=2G vendor/bin/phpunit "$chemin" --no-coverage > /tmp/ci-local-unit.log 2>&1 \
        && printf '  ok  %s\n' "$chemin" || { tail -n 15 /tmp/ci-local-unit.log; echec "tests : $chemin"; }
done

# ---------------------------------------------------------------- base isolee
etape "MariaDB isolee ($CI_DATADIR, port $CI_DB_PORT)"
repond() { "$PHP" -r "new PDO('mysql:host=127.0.0.1;port=$CI_DB_PORT','root','');" 2>/dev/null; }
# Un serveur qui repond deja sur ce port n'est pas le notre : ni DROP, ni SHUTDOWN.
repond && abandon "le port $CI_DB_PORT est deja occupe par un autre serveur"
[ -d "$CI_DATADIR/mysql" ] || "$MYSQL_INSTALL_DB" --datadir="$DATADIR_NATIF" --port="$CI_DB_PORT" > /dev/null 2>&1 \
    || abandon "initialisation de la base isolee"
# Le fichier de reglages ecrit par l'initialisation (XAMPP) porte le datadir ;
# sinon, aucun fichier du poste n'est lu.
if [ -f "$CI_DATADIR/my.ini" ]; then DEFAUTS=(--defaults-file="$DATADIR_NATIF/my.ini"); else DEFAUTS=(--no-defaults); fi
"$MYSQLD" "${DEFAUTS[@]}" --datadir="$DATADIR_NATIF" --port="$CI_DB_PORT" --bind-address=127.0.0.1 --console > /tmp/ci-local-mysqld.log 2>&1 &
PID_BASE=$!
BASE_VERIFIEE=non
arreter_base() {
    # SHUTDOWN seulement vers un serveur dont on a verifie qu'il est le notre.
    [ "$BASE_VERIFIEE" = oui ] && "$MYSQL" -h127.0.0.1 -P"$CI_DB_PORT" -uroot -e "SHUTDOWN" > /dev/null 2>&1
    kill "$PID_BASE" 2>/dev/null
    wait "$PID_BASE" 2>/dev/null
    echo "  base isolee arretee"
}
trap arreter_base EXIT
for _ in $(seq 1 60); do
    repond && break
    kill -0 "$PID_BASE" 2>/dev/null || abandon "le serveur isole s'est arrete au demarrage (voir /tmp/ci-local-mysqld.log)"
    sleep 1
done
repond || abandon "base isolee injoignable apres 60 s"

# Le serveur qui repond est-il celui que ce script vient de lancer ?
serveur=$("$MYSQL" -h127.0.0.1 -P"$CI_DB_PORT" -uroot -N -B -e "SELECT CONCAT(@@port, '|', @@datadir)") \
    || abandon "lecture de @@port/@@datadir impossible"
[ "${serveur%%|*}" = "$CI_DB_PORT" ] || abandon "le serveur repond sur le port ${serveur%%|*}"
datadir_lu=$(printf '%s' "${serveur#*|}" | tr '\\' '/' | sed 's#/*$##' | tr '[:upper:]' '[:lower:]')
datadir_vise=$(printf '%s' "$DATADIR_NATIF" | tr '\\' '/' | sed 's#/*$##' | tr '[:upper:]' '[:lower:]')
[ "$datadir_lu" = "$datadir_vise" ] || abandon "datadir du serveur ${serveur#*|}, attendu $DATADIR_NATIF"
BASE_VERIFIEE=oui

"$MYSQL" -h127.0.0.1 -P"$CI_DB_PORT" -uroot -e "DROP DATABASE IF EXISTS klassci_testing; CREATE DATABASE klassci_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    || abandon "creation de klassci_testing"

etape "Laravel se connecte bien a la base isolee"
# Meme verification, mais par la connexion que migrate:fresh et PHPUnit
# utiliseront : configuration, .env.testing et caches compris.
"$PHP" bin/verifier-base-isolee.php "$CI_DB_PORT" "$DATADIR_NATIF" klassci_testing \
    || abandon "Laravel ne vise pas la base isolee : aucune migration lancee"

etape "Le schema se cree entierement"
"$PHP" artisan migrate:fresh --force --env=testing > /tmp/ci-local-schema.log 2>&1 || { tail -n 20 /tmp/ci-local-schema.log; echec "schema"; }

etape "Tests d'integration de la CI"
for chemin in tests/Feature/Frais/MontantsNetsParFraisTest.php tests/Feature/Classes/AncrageFiliereLmdTest.php \
    tests/Feature/LMD/ImportRefuseEcrasementTest.php tests/Unit/Care tests/Feature/Care; do
    [ -e "$chemin" ] || continue
    "$PHP" -d memory_limit=2G vendor/bin/phpunit "$chemin" --no-coverage > /tmp/ci-local-int.log 2>&1 \
        && printf '  ok  %s\n' "$chemin" || { tail -n 20 /tmp/ci-local-int.log; echec "tests : $chemin"; }
done

if [ -n "$CI_TESTS_BRANCHE" ]; then
    etape "Tests de la branche"
    "$PHP" -d memory_limit=2G vendor/bin/phpunit --filter "$CI_TESTS_BRANCHE" --no-coverage > /tmp/ci-local-branche.log 2>&1 \
        || echec "tests de la branche"
    grep -E '^(Tests:|OK \()' /tmp/ci-local-branche.log | tail -n 1
    grep -E '^[0-9]+\) ' /tmp/ci-local-branche.log || true
fi

# ---------------------------------------------------------------- verdict
etape "Verdict"
printf '%s avertissement(s)\n' "${#AVERTISSEMENTS[@]}"
if [ ${#ECHECS[@]} -eq 0 ]; then
    echo "CI LOCALE : VERT"
    exit 0
fi
printf 'CI LOCALE : ROUGE (%s echec(s))\n' "${#ECHECS[@]}"
printf ' - %s\n' "${ECHECS[@]}"
exit 1
