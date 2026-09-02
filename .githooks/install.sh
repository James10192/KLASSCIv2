#!/bin/sh
#
# Active les hooks du depot.
#
# Git ne versionne pas .git/hooks : sans cette commande, chaque poste repart
# sans garde-fou, et personne ne s'en apercoit avant le premier commit mal
# forme. Une ligne de configuration suffit — elle pointe git vers un dossier
# qui, lui, est suivi.
#
#   sh .githooks/install.sh
#
git config core.hooksPath .githooks
chmod +x .githooks/commit-msg .githooks/pre-push 2>/dev/null
echo "Hooks actives : commit-msg, pre-push."
echo "Pour les desactiver : git config --unset core.hooksPath"
