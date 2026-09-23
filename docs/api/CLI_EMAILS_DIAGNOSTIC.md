# API CLI : diagnostic des adresses e-mail

`GET /api/cli/emails/diagnostic` · jeton Sanctum `cli:read` · lecture seule.
Contrat partagé avec la commande klassci-cli : la forme ne change pas sans elle.
Le corps est rendu tel quel (pas d'enveloppe `success/data`).

```json
{"tenant":"esbtp-abidjan","genere_le":"2026-09-23T18:00:00+00:00",
 "adresses":{"total":0,"valides":0,"factices":0,"fautes_de_frappe":0,"sans_email":0,
   "par_domaine_suspect":[{"domaine":"gmail.con","nombre":3,"type":"faute_de_frappe","suggestion":"gmail.com"}]},
 "convocations":{"acceptees":0,"delivrees":0,"en_attente":0,"echecs":0,"rebonds":0,"supprimees":0,"non_synchronisees":0,"derniere_synchro":null},
 "familles_a_prevenir":0,
 "demandes_non_verifiees":0}
```

- `type` : `factice` (domaine fabriqué, ex. `esbtp.edu.ci`), `faute_de_frappe`, `sans_mx`.
- Colonnes inventoriées : `esbtp_etudiants.email`, `esbtp_etudiants.email_personnel`,
  `esbtp_parents.email`, `users.email`, `esbtp_candidatures.email`,
  `esbtp_rdv_reservations.email`. `total` = lignes de ces colonnes.
- `convocations.acceptees` : acceptées par MailPulse ; `delivrees` : remise confirmée
  par la synchronisation (`inscriptions:synchroniser-convocations-rdv`, toutes les 15 min).
- `demandes_non_verifiees` : champ optionnel, demandes du portail en attente de code.
- `?details=1` ajoute `exemples` : au plus 20 `{"email_masque":"k***@gmail.con","table":"…","motif":"…"}`.
  Aucune adresse complète n'est jamais rendue.

## Historique

- 2026-09-23 : création.
