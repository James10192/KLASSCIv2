#!/bin/sh
#
# Un changement que l'ecole VOIT doit lui etre annonce.
#
# Le CHANGELOG interne ne suffit pas : l'ecole ne le lit pas. Ce qu'elle lit,
# c'est la fenetre « Nouveautes » a la connexion (resources/data/nouveautes.php)
# et la page publique klassci.com/docs/changelog. Les deux ont ete oubliees
# plusieurs fois apres une livraison visible : la regle existait dans
# .claude/rules/changelog.md, rien ne la tenait.
#
# Le piege a eviter : exiger l'entree Nouveautes DANS le meme commit que la
# fonctionnalite. Sa capture « apres » ne se prend qu'une fois la fonctionnalite
# deployee sur presentation, donc apres fusion. L'exiger plus tot pousserait a
# inventer la capture, ou a contourner le controle. D'ou un REGISTRE :
#
#   1. un feat/fix qui touche une vue ou une feuille de style doit, au choix,
#      modifier resources/data/nouveautes.php, ajouter une ligne au registre
#      docs/nouveautes-en-attente.md, ou declarer [sans-nouveaute] suivi de sa
#      raison ;
#   2. un commit qui modifie nouveautes.php porte « Changelog-public: <sha> »
#      (le commit klassci-landing FR+EN), ou « Changelog-public: sans-objet
#      <raison> » ;
#   3. on ne propage pas vers une ecole tant que le registre a une ligne
#      ouverte — c'est aussi le moment ou la capture sur presentation est
#      exigee (pre-merge-checklist.md, commandement 0).
#
# Usage :
#   bin/garde-nouveautes.sh trace     # pour UN commit : fichiers sur stdin,
#                                     # TYPE (type du sujet), MESSAGE (le message),
#                                     # REGISTRE_AJOUTS (lignes ajoutees au registre)
#   bin/garde-nouveautes.sh registre <ref>
#   bin/garde-nouveautes.sh ecoles    # les branches d'ecole, une par ligne
#
# Les vues e-mail et PDF comptent : l'ecole les voit aussi.
#
# Sortie 1 avec l'explication sur stdout quand la regle n'est pas tenue.

REGISTRE="docs/nouveautes-en-attente.md"
VISIBLE='^(resources/views/|resources/css/|resources/js/|public/css/|public/js/)'
# La liste des ecoles vit ici ; .github/workflows/hygiene-commits.yml ne peut
# que la recopier (on.push.branches est statique). A tenir ensemble.
ECOLES="esbtp-abidjan esbtp-yakro ephrata hetec rostan usat ucao-benin"

case "$1" in
    ecoles)
        printf '%s\n' $ECOLES
        exit 0
        ;;

    trace)
        fichiers=$(cat)
        message="${MESSAGE:-}"

        touche_vue=$(printf '%s\n' "$fichiers" | grep -cE "$VISIBLE" || true)
        nouveautes=$(printf '%s\n' "$fichiers" | grep -cE '^resources/data/nouveautes\.php$' || true)
        layout=$(printf '%s\n' "$fichiers" | grep -cE '^resources/views/layouts/app\.blade\.php$' || true)
        # Seule une ligne AJOUTEE compte : retirer celle d'un autre, ou
        # retoucher l'en-tete du registre, n'annonce rien.
        inscrit=$(printf '%s\n' "${REGISTRE_AJOUTS:-}" | grep -cE '^- ' || true)
        sans_objet=$(printf '%s\n' "$message" | grep -cE '^Changelog-public: sans-objet [^[:space:]]' || true)

        if [ "$nouveautes" -gt 0 ]; then
            if ! printf '%s\n' "$message" | grep -qE '^Changelog-public: ([0-9a-f]{7,40}|sans-objet [^[:space:]].*)$'; then
                echo "La fenetre Nouveautes change sans le changelog public."
                echo "Publie l'entree FR + EN dans klassci-landing, puis ajoute au message :"
                echo "  Changelog-public: <sha du commit klassci-landing>"
                echo "ou, pour une retouche sans contenu nouveau :"
                echo "  Changelog-public: sans-objet <raison>"
                exit 1
            fi
            # Sans nouvelle cle de version, qui a deja ferme la fenetre ne la
            # revoit pas : l'annonce n'atteint presque personne.
            if [ "$layout" -eq 0 ] && [ "$sans_objet" -eq 0 ]; then
                echo "La fenetre Nouveautes change, mais pas sa cle de version."
                echo "Change whatsNew.vAAAA_MM_JJ dans resources/views/layouts/app.blade.php,"
                echo "sinon qui l'a deja fermee ne verra pas la nouvelle entree."
                exit 1
            fi
        fi

        case "${TYPE:-}" in feat|fix) ;; *) exit 0 ;; esac

        if [ "$touche_vue" -gt 0 ] && [ "$nouveautes" -eq 0 ] && [ "$inscrit" -eq 0 ]; then
            if printf '%s\n' "$message" | grep -qE '\[sans-nouveaut(e|é)\][[:space:]]*[^[:space:]]'; then
                exit 0
            fi
            echo "Changement visible (vue, style ou script) sans annonce a l'ecole."
            echo "Au choix :"
            echo "  - ajoute une ligne '- ...' a ${REGISTRE} (l'entree Nouveautes suivra,"
            echo "    avec ses captures, apres le deploiement sur presentation) ;"
            echo "  - ou ecris l'entree dans resources/data/nouveautes.php ;"
            echo "  - ou ecris [sans-nouveaute] suivi de la raison dans le corps"
            echo "    (rien que l'ecole remarque : texte interne, refactor sans effet)."
            exit 1
        fi
        exit 0
        ;;

    registre)
        ref="${2:-HEAD}"
        if ! git cat-file -e "${ref}^{commit}" 2>/dev/null; then
            echo "Reference introuvable : ${ref}"
            exit 1
        fi
        ouvertes=$(git show "${ref}:${REGISTRE}" 2>/dev/null | grep -E '^- ' || true)
        if [ -n "$ouvertes" ]; then
            echo "Annonces encore dues aux ecoles (${REGISTRE}) :"
            printf '%s\n' "$ouvertes" | sed 's/^/  /'
            echo ""
            echo "Ecris leurs entrees Nouveautes (captures prises sur presentation)"
            echo "et le changelog public, retire les lignes, puis propage."
            exit 1
        fi
        exit 0
        ;;

    *)
        echo "Usage : $0 trace | registre <ref> | ecoles" >&2
        exit 2
        ;;
esac
