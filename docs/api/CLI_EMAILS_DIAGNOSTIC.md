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
  pourquoi. `a_synchroniser` : la synchronisation les relira (même prédicat et même date de coupure qu'elle).
  Ce détail ne compte que le périmètre des rendez-vous (voir plus bas), alors que la
  synchronisation relit toutes les convocations de la fenêtre, quelle que soit leur année. Les autres
  sont des impasses : `sans_identifiant` (aucun identifiant MailPulse, typiquement les
  convocations d'avant le suivi du 22/09, marquées « envoyée » par la migration),
  `sans_date_envoi`, `hors_fenetre` (plus de 30 jours), `code_neutre`. `autre` doit valoir 0 ; négatif, il est journalisé.
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

## `POST /api/cli/emails/corriger-fautes` (`cli:admin`, 10 appels/min)

Corrige les adresses dont le domaine est une faute de frappe (`gmai.com`, `gmail.con`,
`gemail.com`…), une clé à la fois, après validation par l'école. Mêmes colonnes que le
diagnostic ; `users` seulement avec `inclure_comptes: true`. Faute retenue : faute de frappe
**certaine** (liste des fautes connues de `domaines-suspects.json`, ou faute probable dont le
domaine ne reçoit aucun courrier ; `sans_mx: true` s'en tient à la liste).

**Simulation** `{"execute": false}` : `propositions`, au plus 500 (`propositions_total` les compte toutes) :

```json
{"cle":"esbtp_candidatures:email:42","table":"esbtp_candidatures","colonne":"email","id":42,
 "email_masque":"k***@gmai.com","domaine_actuel":"gmai.com","domaine_propose":"gmail.com",
 "suggestion_masquee":"k***@gmail.com","dossier_reference_masquee":"AB**-****-**KL",
 "lie_a":[{"table":"esbtp_rdv_reservations","colonne":"email","id":7,"cle":"esbtp_rdv_reservations:email:7"}]}
```

`lie_a` : les autres lignes du **même dossier** qui portent **la même adresse** (candidature et
ses réservations actives ; réservation de réinscription et adresses de l'étudiant). Les valider
ensemble, sinon elles divergent.

**Exécution** `{"execute": true, "corrections": [{"cle": "…", "domaine_propose": "gmail.com", "domaine_actuel": "gmai.com"}]}`
(500 clés au plus, distinctes ; `domaine_actuel` optionnel mais conseillé) : seules les clés
listées sont traitées. Une clé n'est corrigée que si l'adresse porte encore une faute dont la
suggestion canonique est exactement `domaine_propose` : jamais un domaine choisi par l'appelant.
Seul le domaine change, la partie locale reste identique. Une sauvegarde
(`storage/app/backups/emails-fautes-*.json` : table, id, colonne, ancienne et nouvelle valeur)
est écrite et relue avant toute écriture ; chaque ligne est ensuite écrite sous verrou, de façon
conditionnelle, avec sa trace d'audit (`correction_faute_email`) dans la même transaction.

Réponse, dans l'enveloppe `data` :

```json
{"execute":true,"inclure_comptes":false,"propositions_total":0,"propositions":[],
 "corrigees":2,"ignorees":[{"cle":"esbtp_candidatures:email:43","motif":"domaine_non_canonique"}],
 "sauvegarde":"emails-fautes-20260925_101500.json","convocations_a_renvoyer":1}
```

- `ignorees[].motif` : `cle_invalide` (mal formée, colonne non inventoriée, compte sans
  `inclure_comptes`), `modifiee_entre_temps` (plus de faute, ou `domaine_actuel` différent, ou
  valeur changée avant l'écriture), `domaine_non_canonique`, `adresse_deja_utilisee` (unicité),
  `echec_ecriture` (écriture et audit annulés).
- `convocations_a_renvoyer` : réservations actives corrigées dont la convocation était en échec
  ou sans e-mail. **Rien n'est envoyé** : le renvoi se décide ensuite.

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

## `POST /api/cli/rendez-vous/rattrapage-convocations` (`cli:admin`, 5 appels/min)

Rattache aux convocations d'avant le suivi du 22/09 l'identifiant et la date du courriel
MailPulse qui les a portées, pour que la synchronisation relise leur remise. Simulation par défaut.

```json
{"execute":false,"coupure_max":"2026-09-22T22:48:00Z","messages":[{"reference":"ABCD-EFGH-IJKL",
  "message_id":"msg_…","envoye_at":"2026-09-10T09:00:00Z",
  "destinataire_sha256":"<sha256 de l'adresse en minuscules, sans espaces>",
  "destinataire_domaine":"gmail.com","action":"confirme|deplace|annule"}]}
```

- `envoye_at` : le `createdAt` du message chez MailPulse (instant où MailPulse l'a accepté).
- `coupure_max` : optionnel. Ne peut qu'avancer la coupure calculée (voir plus bas).
- Validation (422 pour tout le lot) : `execute` vrai booléen JSON ; 2000 courriels au plus ;
  `message_id` distincts ; `envoye_at` et `coupure_max` en ISO 8601 avec fuseau, `envoye_at`
  pas dans le futur ; `destinataire_sha256` présent (peut valoir `null`). Un courriel sans
  `reference` est écarté seul et compté dans `sans_reference`.
- **Tous les courriels d'une même référence doivent arriver dans le même appel** : le choix se
  fait parmi ceux que l'appel contient. Découper par référence, jamais au milieu d'une référence.
  Une réservation déjà rattachée n'est plus éligible : un appel suivant ne la retouche jamais.

**Éligibles** : réservations `confirmee`, convocation `envoyee`, sans identifiant MailPulse
**et** sans date d'envoi, dans le périmètre des rendez-vous.

**Coupure du suivi** = la plus ancienne de : maintenant ; `coupure_max` s'il est fourni ; le
plus ancien `convocation_envoyee_at` d'une réservation qui porte un identifiant MailPulse posé
par l'envoi lui-même (les lignes déjà rattrapées, tracées dans l'audit, sont exclues pour que la
coupure ne recule pas d'un passage à l'autre). La réponse donne la coupure retenue et sa source.

**Appariement**, par réservation :
1. même référence de dossier (`reference_publique`, tirets et casse indifférents) ; action
   compatible (`confirme` accepte `confirme` ou `deplace` ; `annule` n'accepte que `annule`) ;
2. fenêtre : `created_at − 60 s ≤ envoye_at <` la plus proche de la coupure et de la création
   de la réservation suivante du même dossier (le filtre sur la coupure est fait ici, côté serveur) ;
3. destinataire : adresse présente, même empreinte (un courriel sans empreinte ne se rattache
   pas) ; adresse vidée par le nettoyage, l'empreinte de l'ancienne adresse lue dans la
   sauvegarde `storage/app/backups/emails-factices-*.json` (fichiers de plus de 20 Mo ignorés),
   à défaut un domaine fabriqué ;
4. le plus récent parti au plus tard au dernier envoi au dossier (`rdv_invite_at`, tolérance
   120 s : il est posé après la réponse de MailPulse et tronqué à la seconde), à défaut le plus
   récent ; deux courriels au même instant : ambigu ;
5. retenu mais parti avant `created_at` (dans la tolérance de 60 s) alors que le dossier avait
   une réservation précédente : il peut être celui de la précédente, ambigu.

Un courriel déjà rattaché n'est jamais réutilisé ; choisi par deux réservations, il est ambigu
pour les deux. L'écriture est conditionnelle (identifiant et date encore vides) et partage sa
transaction avec la trace d'audit (`rattrapage_convocation`, source `rattrapage_mailpulse`,
anciennes et nouvelles valeurs) : sans trace, rien n'est écrit (`echecs_ecriture`).

Réponse, dans l'enveloppe `data` :

```json
{"execute":false,"coupure":"2026-09-22T22:48:00+00:00","coupure_source":"coupure_max",
 "eligibles":0,"appariees":0,"ambigues":0,"sans_message":0,"sans_reference":0,"deja_renseignees":0,
 "resolues_par_sauvegarde":0,"resolues_par_domaine":0,"ecrites":0,"echecs_ecriture":0,
 "exemples":[{"reference_masquee":"AB**-****-**KL","motif":"ambigue"}]}
```

- `coupure_source` : `premier_envoi_suivi`, `coupure_max` ou `maintenant`.
- `deja_renseignees` : nombre de **courriels** reçus déjà rattachés à une réservation (pas de réservations).
- `resolues_par_sauvegarde` / `resolues_par_domaine` : appariements d'adresses vidées, par
  l'empreinte de la sauvegarde ou par le domaine fabriqué (les autres l'ont été par l'adresse).
- `exemples` : au plus 20, ambigus ou sans courriel, référence masquée à deux plus deux caractères.

## Réglage `inscriptions.portail.verification_contact`

Lisible et modifiable par `GET/POST /api/cli/settings` ; la valeur est validée comme
booléen (`1/0`, `true/false`) et écrite en `1`/`0`.

- 2026-09-24 : nettoyage, synchronisation et familles par l'API CLI.
- 2026-09-25 : rattrapage des convocations d'avant le suivi ; correction des fautes de frappe.
