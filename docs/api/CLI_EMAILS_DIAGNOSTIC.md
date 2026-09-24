# API CLI : diagnostic des adresses e-mail

`GET /api/cli/emails/diagnostic` · jeton Sanctum `cli:read` · lecture seule.
Contrat partagé avec la commande klassci-cli : la forme ne change pas sans elle.
Le corps est rendu tel quel (pas d'enveloppe `success/data`).

```json
{"tenant":"esbtp-abidjan","genere_le":"2026-09-23T18:00:00+00:00",
 "adresses":{"total":0,"valides":0,"factices":0,"fautes_de_frappe":0,"sans_email":0,
   "par_domaine_suspect":[{"domaine":"gmail.con","nombre":3,"type":"faute_de_frappe","suggestion":"gmail.com"}]},
 "convocations":{"acceptees":0,"delivrees":0,"en_attente":0,"echecs":0,"rebonds":0,"supprimees":0,"non_synchronisees":0,
   "non_synchronisees_detail":{"a_synchroniser":0,"sans_identifiant":0,"sans_date_envoi":0,"hors_fenetre":0,"code_neutre":0,"autre":0},
   "derniere_synchro":null},
 "familles_a_prevenir":0,
 "demandes_non_verifiees":0}
```

- `type` : `factice` (domaine fabriqué, ex. `esbtp.edu.ci`), `faute_de_frappe`, `sans_mx`.
- Colonnes inventoriées : `esbtp_etudiants.email`, `esbtp_etudiants.email_personnel`,
  `esbtp_parents.email`, `users.email`, `esbtp_candidatures.email`,
  `esbtp_rdv_reservations.email`. `total` = lignes de ces colonnes.
- `convocations.acceptees` : acceptées par MailPulse ; `delivrees` : remise confirmée
  par la synchronisation (`inscriptions:synchroniser-convocations-rdv`, toutes les 15 min).
- `convocations.non_synchronisees` : envoyées jamais relues ; `non_synchronisees_detail` dit
  pourquoi. `a_synchroniser` : la synchronisation les relira (même prédicat qu'elle). Les autres
  sont des impasses : `sans_identifiant` (aucun identifiant MailPulse, typiquement les
  convocations d'avant le suivi du 22/09, marquées « envoyée » par la migration),
  `sans_date_envoi`, `hors_fenetre` (plus de 30 jours), `code_neutre`. `autre` doit valoir 0.
- Périmètre des convocations, du diagnostic `GET /api/cli/rendez-vous/diagnostic` et des
  familles : les rendez-vous de l'année cible des inscriptions (réglage `inscriptions.annee_cible`,
  à défaut l'année courante, à défaut les douze derniers mois).
- `demandes_non_verifiees` : champ optionnel, demandes du portail en attente de code.
- `?details=1` ajoute `exemples` : au plus 20 `{"email_masque":"k***@gmail.con","table":"…","motif":"…"}`.
  Aucune adresse complète n'est jamais rendue.

## Historique

- 2026-09-23 : création.

---

# API CLI : nettoyage, synchronisation, familles à recontacter

Le SSH des cPanel des écoles n'étant pas disponible, tout passe par `/api/cli/*`
(`auth:sanctum`). Routes déclarées dans `routes/api-cli-emails.php`.

## `POST /api/cli/emails/nettoyer-factices` (`cli:admin`)

Corps : `{"execute": false, "inclure_comptes": false}`, les deux facultatifs mais,
s'ils sont fournis, de **vrais booléens JSON** (sinon `422`). Simulation par défaut.
`?sans_mx=1` évite le DNS. Réponse `data` :

```json
{"lignes":[{"table":"esbtp_etudiants","colonne":"email","domaine":"esbtp.edu.ci","nombre":48,"type":"factice","suggestion":null}],
 "comptes_par_role":{"etudiant":12},
 "exemples":[{"email_masque":"m***@esbtp.edu.ci","table":"esbtp_etudiants","colonne":"email","type":"factice"}],
 "execute":false,"inclure_comptes":false,"modifiees":0,"sauvegarde":null}
```

Enveloppe habituelle des routes CLI : `{"success":true,"data":{…},"message":"…"}`.
`type` : `factice`, `faute_de_frappe` ou `sans_mx`. Avec `execute: true` : vide les
adresses factices (comptes utilisateurs seulement avec `inclure_comptes: true`) après
une sauvegarde relue ; `modifiees` et `sauvegarde` (nom du fichier, jamais son chemin sur le serveur) sont remplis.
Seules les lignes dont l'adresse porte encore le domaine factice sont vidées. Chaque appel est tracé dans le journal d'audit
(`cli.emails.nettoyer_factices`).

## `POST /api/cli/rendez-vous/synchroniser-convocations` (`cli:admin`)

Un passage de la synchronisation planifiée (`?max=` 1 à 200, 100 par défaut, 20 s au plus) :

```json
{"lus":0,"delivrees":0,"echecs":0,"rebonds":0,"supprimees":0,"en_attente":0,"arretee_sur":null,"erreurs":0}
```

`arretee_sur` : code de la panne qui a arrêté le lot, `budget_temps_epuise` si les 20 s sont
écoulées (le reste passe en tête à l'appel suivant), `null` sinon ; la réponse reste `200`,
dans l'enveloppe `data`. Tracé dans l'audit.

## `GET /api/cli/rendez-vous/familles[?details=1]` (`cli:read`, lecture seule)

Une ligne par FAMILLE (candidature ou demande), réservations dédupliquées. Périmètre :
les rendez-vous de l'année cible des inscriptions, le même que le diagnostic (voir plus haut). Dans
l'enveloppe `data`, la synthèse est toujours présente :

```json
{"synthese":{"familles":0,"A_au_moins_une_remise":0,"B_uniquement_des_echecs":0,"C_remise_inconnue":0,
  "D_sans_email":0,"E_B_ou_D_avec_telephone":0,
  "F_adresse_fabriquee":{"total":0,"email_alternatif":0,"telephone_seul":0,"rien":0}}}
```

`?details=1` ajoute `familles` : `type`, `reservations`, `statut_reservation`,
`statut_convocation`, `remise` (`delivree`|`rebond`|`suppression`|`echec`|`inconnue`),
`email_masque`, `email_etat` (`valide`|`factice`|`faute_de_frappe`|`invalide`|`vide`),
`telephones_masques` (`+225 07 ** ** ** 12`), `emails_alternatifs_masques`,
`a_telephone`, `a_email_alternatif_joignable`, `categorie`. Aucun nom, aucune donnée en clair.

## Réglage `inscriptions.portail.verification_contact`

Lisible et modifiable par `GET/POST /api/cli/settings` ; la valeur est validée comme
booléen (`1/0`, `true/false`) et écrite en `1`/`0`.

- 2026-09-24 : nettoyage, synchronisation et familles par l'API CLI.
