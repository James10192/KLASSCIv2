# API CLI — Matières de spécialité sur les bulletins de tronc commun

Recenser, étudiant par étudiant, les matières de spécialité qui s'affichent sur
un bulletin de **tronc commun** BTS, avec la note qui les y porte, la cause
probable et l'action suggérée. **Lecture seule.**

| | |
|---|---|
| Route | `GET /api/cli/diagnostics/tc-specialite-leak` |
| Authentification | Bearer Sanctum, ability `cli:read` |
| Throttle | celui du groupe `/api/cli` (60 requêtes par minute) |
| Contrôleur | `App\Http\Controllers\API\CLI\CLITcSpecialiteLeakController` |
| Service | `App\Domain\BtsTroncCommun\Diagnostics\TcSpecialiteLeakDiagnostic` |
| Commande serveur | `php artisan diagnostics:tc-specialite-leak [--annee=] [--classe=] [--etudiant=] [--json]` |
| Wrapper local | `.\klassci-cli.ps1 diagnostics:tc-specialite-leak <tenant> [annee_universitaire_id=] [classe_id=] [etudiant_id=]` |

## Pourquoi

ESBTP Yamoussoukro, septembre 2026 : l'étudiant AMANI (TRONC COMMUN A) porte
« Sécurité » à 12,75, rang 1, professeur « Non attribué ». La classification
`tronc_commun` / `specialite` du pivot `esbtp_matiere_filiere_niveau` (feature
#576) existe, mais rien n'était classé au déploiement, et **une matière notée
reste au bulletin quelle que soit sa classification**. Ce diagnostic dit où, et
pourquoi.

## Paramètres (query string)

| Paramètre | Type | Défaut | Rôle |
|---|---|---|---|
| `annee_universitaire_id` | entier | année courante (`anneeCourante()`) | année analysée |
| `classe_id` | entier | toutes | restreint à une classe (une classe qui n'est pas une classe BTS de tronc commun ne rend rien) |
| `etudiant_id` | entier | tous | restreint à un étudiant de la cohorte |

422 si un identifiant n'existe pas ou si aucune année n'est trouvée ; 403 sans `cli:read`.

## Ce qui est analysé

Pour chaque classe BTS dont la filière est un tronc commun
(`ESBTPFiliere::isTroncCommun()`) et pour chaque semestre :

- **cohorte** : `BtsClassCohortCounter::etudiantIdsPourPeriode()` — jamais
  `inscriptions.classe_id`, qui suit l'étudiant réorienté ;
- **matières affichées** : la maquette (`BtsBulletinSubjectResolver::subjectsForClasse()`),
  plus toute matière qui porte une note **retenue par le bulletin** ou une
  **moyenne enregistrée** (`esbtp_resultats`) sur la classe de tronc commun.
  Une note est « retenue » si son évaluation est posée sur la classe du bulletin
  ou la classe porteuse du semestre (`BtsAnnualClassMapResolver`), et si cette
  classe est ouverte au semestre (`ClasseOuvertureResolver`) — les mêmes règles
  que `BulletinService` ;
- les ECUE LMD sont hors champ (voir `diagnostics/evaluation-system-mismatch`).

Une matière affichée est **suspecte** si :

- `classee_specialite` : elle est classée `specialite` sur le couple de tronc
  commun (filière × niveau) ;
- `non_classee_hors_planification` : elle n'est pas classée (nulle, ou absente du
  couple de tronc commun), figure sur le couple d'une filière fille du même
  niveau, et n'est planifiée pour le tronc commun à aucun semestre de l'année
  (`esbtp_planifications_academiques`). Tous semestres confondus : la maquette
  du résolveur n'est pas semestrielle, une matière planifiée au seul semestre 1
  ressort aussi au semestre 2 sans être une fuite.

## Causes

Chaque ligne (note ou moyenne enregistrée) porte une cause :

| Cause | Signification |
|---|---|
| `etudiant_reoriente` | l'évaluation est posée sur une classe de spécialité où l'étudiant a une phase |
| `evaluation_autre_classe` | l'évaluation est posée sur une autre classe, où l'étudiant n'a aucune phase |
| `matiere_a_classer` | l'évaluation est sur la classe de tronc commun, la matière n'est pas classée |
| `autre` | évaluation d'une matière classée spécialité posée sur la classe de tronc commun, ou moyenne enregistrée sans aucune note |

Une moyenne enregistrée hérite de la cause des notes de l'étudiant sur la même
matière : une moyenne calculée depuis une note égarée reste au bulletin même
quand le calcul actuel écarte la note.

`action_suggeree` est une phrase, jamais une opération : classer, déplacer ou
retirer une note, régénérer un bulletin restent des décisions de l'école.

## Réponse

```json
{
  "success": true,
  "message": "2 matiere(s) suspecte(s), 2 etudiant(s) touche(s).",
  "data": {
    "annee_universitaire": { "id": 3, "libelle": "2025-2026" },
    "filtres": { "classe_id": null, "etudiant_id": null },
    "resume": {
      "classes_analysees": 7,
      "classes_touchees": 1,
      "matieres_suspectes": 2,
      "lignes": 5,
      "etudiants_touches": 2,
      "par_cause": { "matiere_a_classer": 0, "evaluation_autre_classe": 2, "etudiant_reoriente": 2, "autre": 1 },
      "matieres_par_cause": { "matiere_a_classer": 0, "evaluation_autre_classe": 1, "etudiant_reoriente": 0, "autre": 1 },
      "par_classe": [
        { "classe_id": 12, "classe": "TRONC COMMUN A", "matieres_suspectes": 2, "etudiants_touches": 2, "lignes": 5 }
      ]
    },
    "classes": [
      {
        "classe_id": 12, "classe": "TRONC COMMUN A",
        "filiere_id": 4, "filiere": "Tronc commun BTP",
        "niveau_etude_id": 1, "niveau": "1ère année",
        "semestres": [
          {
            "semestre": 1, "periode": "semestre1", "effectif": 48,
            "matieres": [
              {
                "matiere_id": 88, "matiere": "Sécurité", "code": "SEC1",
                "type_suspicion": "classee_specialite",
                "classification_combo_tc": "specialite",
                "sur_combo_tc": true,
                "dans_maquette_bulletin": false,
                "combos_specialite": [ { "filiere_id": 5, "filiere": "Bâtiment" } ],
                "cause_principale": "autre",
                "action_suggeree": "…",
                "etudiants": [
                  {
                    "etudiant_id": 501, "matricule": "…", "nom": "AMANI", "prenoms": "…",
                    "phase_specialite": null,
                    "notes": [
                      {
                        "note_id": 9001, "note": 12.75, "is_absent": false,
                        "evaluation_id": 622, "titre": "Devoir 1", "date_evaluation": "2025-11-12 00:00:00",
                        "periode": "semestre1", "semestre": 1,
                        "evaluation_classe_id": 12, "evaluation_classe": "TRONC COMMUN A",
                        "evaluation_autre_classe": false,
                        "retenue_au_bulletin": true,
                        "cause": "autre",
                        "action_suggeree": "…"
                      }
                    ],
                    "moyennes_enregistrees": [
                      { "resultat_id": 7001, "moyenne": 12.75, "periode": "semestre1", "cause": "autre", "action_suggeree": "…" }
                    ]
                  }
                ]
              }
            ]
          }
        ]
      }
    ]
  },
  "meta": { "…": "…" }
}
```

`phase_specialite`, quand elle existe : `{ classe_id, classe, filiere, semestre_debut, semestre_fin, is_active }`
(phase active de préférence, sinon la plus récente). `classes` ne contient que les
classes et semestres qui ont au moins une matière suspecte ; `resume.classes_analysees`
compte toutes les classes de tronc commun examinées.

## Coût

Requêtes groupées par classe et semestre (cohorte, notes, moyennes, inscriptions,
planification), aucune requête par étudiant hors le cas legacy sans phase que
`BtsAnnualClassMapResolver` traite lui-même.

## Historique

- **Septembre 2026** — création (cas AMANI, ESBTP Yamoussoukro).
