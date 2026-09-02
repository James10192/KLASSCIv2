# Versionnage et traçabilité des livraisons

> **Pour l'agent qui alimente `klassci-landing`** : la section [Flux machine](#flux-machine) décrit
> tout ce dont tu as besoin. Le reste explique pourquoi les garde-fous existent.

## Le problème que ça résout

Les nouveautés du site public étaient recopiées à la main depuis le `CHANGELOG`. Quelqu'un devait y
penser. Personne n'y pensait toutes les semaines, et le modal « What's new » de l'application est
resté figé sur mai 2026 pendant quatre mois.

Le principe retenu : **l'historique des commits est la source**, tout le reste en dérive. Un
changement qui n'apparaît nulle part n'a pas été livré.

## Trois barrières, de la plus souple à la plus ferme

| Barrière | Où | Contournable | Ce qu'elle attrape |
|---|---|---|---|
| `commit-msg` | poste local | oui, `--no-verify` | retour immédiat pendant qu'on écrit |
| `pre-push` | poste local | oui, `--no-verify` | ce qui allait devenir public |
| `hygiene-commits.yml` | GitHub Actions | **non** | tout le reste |

Les deux premières donnent un retour en moins d'une seconde. La troisième est la seule qui engage :
elle rejoue **exactement** les mêmes règles sur la pull request. Contourner localement ne fait que
déplacer le refus.

### Activer les hooks

```sh
sh .githooks/install.sh
```

Git ne versionne pas `.git/hooks`. Sans cette commande, un poste neuf repart sans garde-fou et
personne ne s'en aperçoit avant le premier commit mal formé.

## Ce qui est refusé, et pourquoi

**Toute signature d'outil** — `Co-Authored-By:`, `Generated with`, l'emoji robot, un lien de session,
`noreply@anthropic.com`. L'auteur d'un commit est la personne qui en répond. Une co-signature d'outil
brouille cette responsabilité et pollue durablement l'historique : la retirer après coup demande de
réécrire les commits.

**Un message non conventionnel.** Pas par goût de la norme : c'est ce qui rend l'historique lisible
par une machine, donc ce qui permet de produire les notes de version sans les réécrire.

```
type(portée): description
```

Types : `feat` `fix` `refactor` `perf` `docs` `test` `chore` `build` `ci` `style` `revert`.
Un `!` après la portée, ou `BREAKING CHANGE` dans le corps, signale une rupture.

**Un `feat` ou un `fix` qui touche `app/`, `resources/`, `routes/` ou `database/` sans toucher au
`CHANGELOG.md`.** Ce commit change ce que voit une école : sans trace, personne ne saura le dire
trois mois plus tard.

Échappatoire assumée pour un changement strictement interne :

```
fix(paiements): renomme une variable privée

Aucune école ne voit ce changement. [sans-changelog]
```

Elle est écrite dans le message, donc elle se relit et se conteste.

## Flux machine

```sh
php artisan release:notes --depuis=v2026.08 --jusqua=HEAD --sortie=release.json
```

Sans `--depuis`, la commande part du dernier tag ; à défaut, des cinquante derniers commits.

```jsonc
{
  "genere_le": "2026-09-02T21:35:18+00:00",
  "depuis": "1c5d58a9",
  "jusqua": "af8566ab",
  "total": 15,
  "saut": "mineur",              // majeur | mineur | correctif, déduit des commits
  "sections": {
    "feat": {
      "titre": "Ajouts",
      "visible_ecole": true,      // ← ce qui mérite d'aller sur le site public
      "commits": [
        { "sha_court": "6198f3dd", "portee": "paiements", "titre": "…", "rupture": false, "date": "…" }
      ]
    }
  },
  "ruptures": []
}
```

**Ce qu'un agent doit en faire.** Ne reprends que les sections où `visible_ecole` vaut `true`
(`feat`, `fix`, `perf`, `revert`). Les autres décrivent de la plomberie : elles n'intéressent pas une
école et alourdissent la page.

Le champ `titre` est un sujet de commit, écrit pour un développeur. **Il ne se recopie pas tel quel
sur le site public.** Le `CHANGELOG.md` contient déjà la version rédigée pour un lecteur non
technique : le flux JSON sert à savoir *quoi* raconter et dans quel ordre, le `CHANGELOG` à savoir
*comment* le dire. Les conventions de rédaction publique sont dans
[`.claude/rules/changelog.md`](../.claude/rules/changelog.md) — pas de nom de fichier, pas de numéro
de PR, pas de vocabulaire interne.

Un commit `"conventionnel": false` n'est pas masqué : le taire produirait des notes incomplètes sans
le dire.

## Les trois fichiers, et à qui ils parlent

| Fichier | Lecteur | Contenu |
|---|---|---|
| historique git | machine | la source, une ligne par changement |
| `CHANGELOG.md` | équipe, puis site public | la version rédigée, groupée par mois |
| modal « What's new » | l'école, dans l'application | les quelques nouveautés qui changent son quotidien |

Les trois viennent des mêmes commits. Le premier est produit, les deux autres sont écrits — mais
aucun des deux ne peut plus être oublié en silence.
