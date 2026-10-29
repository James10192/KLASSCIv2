# API CLI — Rapport d'usage

`GET /api/cli/usage/report`

Répond à une seule question : **l'établissement travaille-t-il réellement dans KLASSCI ?**
Lecture seule. Jeton Sanctum `cli:read`. Limité à 10 appels par minute.

## Paramètres

| Paramètre | Obligatoire | Description |
|---|---|---|
| `from` | oui | Début de période, `AAAA-MM-JJ` (inclus) |
| `to` | oui | Fin de période, `AAAA-MM-JJ` (inclus) |
| `exclure` | non | Identifiants de comptes à ranger avec KLASSCI, séparés par des virgules (ex. un compte de support créé chez l'école) |
| `nominatif` | non | `1` pour afficher les noms du personnel. Exige un jeton `cli:admin`, sinon 403 |

Période maximale : `config('usage_report.max_window_days')`, 186 jours par défaut.

## Méthode

Source : table `audits`. Chaque écriture (création, modification, suppression, restauration) est rangée dans un groupe :

| Groupe | Règle |
|---|---|
| `klassci` | URL contenant `/api/cli/`, ou compte portant un rôle interne (`serviceTechnique`), ou compte listé dans `exclure` |
| `systeme` | Commande serveur ou file d'attente (URL qui n'est pas `http…`), ou action sans compte |
| `etudiants` | Compte dont tous les rôles sont `etudiant` ou `parent` |
| `ecole` | Tout autre compte connecté à l'application : le personnel |

- **Journée active** : jour où un compte a enregistré au moins une écriture. La simple navigation ne laisse pas de trace.
- **Lectures** : seuls les paiements et les factures journalisent leurs consultations. Elles sont comptées à part (`lectures`), jamais mêlées aux écritures.
- **Journée de masse** : plus de `bulk_actions_per_user_day` écritures (300 par défaut) pour un même compte dans la journée. Elle reste une journée active, mais `semaines[].actions_hors_masse` l'exclut.
- **Modules sans audit** (séances, présences des enseignants, messages…) : seul le nombre de lignes créées et de jours distincts est connu, pas leur auteur (`volumes_sans_audit`).
- `last_login_at` ne garde que la dernière connexion : `parc_de_comptes` dit si un compte s'est connecté pendant la période, pas combien de fois.

Tout se règle dans `config/usage_report.php`.

## Réponse

```json
{
  "success": true,
  "data": {
    "periode": {"from": "2026-07-24", "to": "2026-09-24", "days": 63},
    "methode": {"seuil_journee_de_masse": 300, "...": "..."},
    "groupes": {"ecole": {"actions": 5120, "lectures": 310, "comptes_actifs": 14, "comptes_lecteurs": 3}, "klassci": {}, "systeme": {}, "etudiants": {}},
    "jours": [{"jour": "2026-07-24", "actions": 12, "comptes": 3}],
    "semaines": [{"semaine": "2026-07-20", "actions": 800, "actions_hors_masse": 420, "comptes": 6}],
    "mois": [{"mois": "2026-08", "actions": 35000, "comptes": 4, "jours": 17}],
    "realisations": [{"entite": "ESBTPNote", "label": "Notes", "crees": 12000, "modifies": 800, "supprimes": 3, "restaures": 0, "comptes": 4}],
    "creations_par_mois": [{"mois": "2026-08", "creations": [{"label": "Notes", "nombre": 12000}]}],
    "paiements_par_mois": [{"mois": "2026-08", "nombre": 120, "montant": 18000000}],
    "comptes_actifs": [{"user_id": 42, "compte": "Compte 42", "role": "secretaire", "actions": 900, "jours_actifs": 31, "jours_de_masse": 1, "premiere_action": "2026-07-24", "derniere_action": "2026-09-23"}],
    "journees_de_masse": [{"groupe": "ecole", "user_id": 42, "compte": "Compte 42", "role": "secretaire", "jour": "2026-09-02", "actions": 410}],
    "modules": [{"module": "inscriptions", "label": "Inscriptions et dossiers étudiants", "actions_ecole": 3000, "actions_klassci": 120, "actions_systeme": 0, "comptes": 5, "jours": 40, "roles": ["secretaire", "caissier"]}],
    "volumes_sans_audit": [{"module": "seances", "label": "Séances de cours (emploi du temps)", "lignes": 230, "jours": 12}],
    "heures": [{"jour_semaine": 1, "heure": 9, "actions": 140}],
    "parc_de_comptes": {
      "personnel_par_role": [{"role": "enseignant", "comptes": 60, "desactives": 2, "jamais_connectes": 35, "vus_dans_la_periode": 12, "actifs_dans_la_periode": 4}],
      "etudiants": {"comptes": 2100, "vus_dans_la_periode": 180}
    }
  }
}
```

- `realisations` et `creations_par_mois` : ce que le personnel de l'école a fait, entité par entité (créations, modifications, suppressions). Nos opérations en sont exclues.
- `paiements_par_mois` : paiements validés, par mois de `date_paiement`, nombre et montant encaissé. Source : la table des paiements, quel que soit l'auteur.

`heures[].jour_semaine` suit la norme ISO : 1 = lundi … 7 = dimanche.

## Erreurs

| Code | Cas |
|---|---|
| 403 | Jeton sans `cli:read`, ou `nominatif=1` sans `cli:admin` |
| 422 | Dates invalides, fin avant début, période trop longue |
| 500 | Échec du calcul (détail dans le journal serveur, préfixe `[usage-report]`) |

## Historique

- 2026-09-24 : création.
- 2026-09-24 : ajout de `mois`, `realisations`, `creations_par_mois` et `paiements_par_mois` (non cassant).
