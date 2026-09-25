# Portail public — suivi d'un dossier déjà déposé

Une famille revient sur le portail (klassci.com) après avoir déposé une candidature
ou une demande de réinscription. Elle retrouve sa demande, voit l'adresse e-mail que
l'école a (masquée), la vérifie ou la corrige, et reçoit sa convocation.

Toutes les routes sont `POST`, signées par le site vitrine comme les autres routes
publiques (`X-Klassci-Signature`, `X-Klassci-Timestamp`), et passent par le canal
`verification` : **toujours ouvert**, une saison qui se ferme ne doit pas couper une
famille de son rendez-vous. Le plancher de durée (`reinscription.plancher`) rend
« trouvé » et « introuvable » indiscernables.

Code : `routes/api-portail-verification.php`, `SuiviDossierPortalController`,
`App\Services\Portail\SuiviDossierPortail`, `App\Services\Portail\ReferenceOubliee`.

## Identification

Corps commun : `identifiant`, `date_naissance` (`Y-m-d`), `ip_client`.

`identifiant` est la **référence du dossier** (candidature ou réinscription) ou, pour
une réinscription, le **matricule** (demande de l'année visée par la réinscription).

Échec, quelle qu'en soit la raison : `200 {"trouve": false}`. Débit : 10 essais ratés
par identifiant et par quart d'heure, puis `429`.

## `api/public/suivi/consulter`

```json
{
  "trouve": true,
  "type": "inscription | reinscription",
  "reference": "ABCD-1234",
  "statut": { "code": "en_attente", "libelle": "En cours d'examen par l'établissement" },
  "contact": { "email_masque": "a***@gmail.com", "email_verifie": false, "a_confirmer": false },
  "verification_active": true,
  "prise_rdv_ouverte": true,
  "rendez_vous": null,
  "peut_recevoir_convocation": false
}
```

`rendez_vous`, quand il existe : `date`, `heure_debut`, `heure_fin`, `statut`,
`convocation` (état de l'e-mail), `peut_modifier`.

`email_masque` vaut `null` si l'adresse du dossier ne reçoit pas de courrier
(absente, fabriquée, faute de frappe connue).

## `api/public/suivi/email`

Corps : identification + `email` (doit être joignable, règle `EmailJoignable`).

- **Réinscription** : l'adresse est posée sur la demande (`email_contact`). La fiche
  de l'étudiant n'est **jamais** modifiée depuis le site public.
- **Candidature** : l'adresse de la candidature est remplacée.
- Adresse inchangée : même effet que `verifier`.

Réponse `200` avec `code` :

| code | sens |
|---|---|
| `code_envoye` | un code est parti ; la réponse porte aussi `statut`, `demande_id`, `email_masque` (même forme qu'au dépôt) |
| `enregistre` | vérification désactivée par l'école : l'adresse vaut tout de suite, les convocations retenues repartent |
| `deja_verifie` | rien à faire |

`409` : `dossier_clos`, `envoi_impossible`. Toute réponse porte `situation` (même
forme que `consulter`).

Le code se saisit ensuite par `api/portail/email/verifier` (inchangé). Dès qu'il est
bon, les convocations retenues repartent **tout de suite**, pas au prochain passage
de la tâche planifiée.

## `api/public/suivi/verifier`

Relance un code pour l'adresse déjà au dossier. Une demande jamais marquée (déposée
avant l'activation de la vérification) ne se met pas à retenir sa convocation : le
code ne fait que dater son contact. Codes : `code_envoye`, `deja_verifie`,
`verification_inactive`, `envoi_impossible`.

## `api/public/suivi/convocation`

Renvoie l'e-mail de convocation, avec le lien vers le PDF, à l'adresse du dossier
(qui remplace celle de la réservation si elle a été corrigée). Trois par jour et par
réservation.

`200 {"code": "envoyee"}`, sinon `409` avec : `sans_rendez_vous`,
`rendez_vous_passe`, `dossier_clos`, `contact_a_confirmer`, `sans_email`,
`trop_de_demandes`, `envoi_impossible`.

## `api/public/suivi/reference-oubliee`

Corps : `email`, `date_naissance`, `ip_client`.

La référence part **à cette adresse**, si elle est celle d'un dossier avec cette date
de naissance (candidature, ou demande de réinscription de l'année visée : adresse
donnée sur le portail, adresse personnelle ou adresse du dossier). Elle n'est jamais
rendue à l'écran.

Réponse toujours `202 {"envoye": true}`, dossier trouvé ou non. L'envoi se fait après
la réponse, pour que la durée ne trahisse rien. Débit : 3 demandes par adresse et par
heure.

## Historique

- **Septembre 2026** — création.
