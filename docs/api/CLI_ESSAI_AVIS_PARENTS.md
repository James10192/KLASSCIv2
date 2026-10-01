# API CLI : essai d'un avis aux parents

`POST /api/cli/courriels/avis-parents/essai` · jeton Sanctum `cli:admin` · `throttle:10,1`.

Envoie un **vrai** avis aux parents — la vraie classe Mailable (`app/Mail/Parents/*Mail.php`),
le vrai gabarit Blade (`resources/views/esbtp/emails/parents/`), le mailer de l'école
(MailPulse quand le réglage « Envoyer tous les e-mails de l'école par MailPulse » est coché) —
pour le contrôler dans une vraie boîte (Gmail, mode sombre compris), sans fabriquer de données
réelles.

**L'outil n'écrit jamais à un parent réel.** Les destinataires sont exclusivement les adresses de
test actives de l'école : réglage `mailpulse_test_email_recipients` (liste JSON
`[{"value": "…", "enabled": true}]`), repli sur `mailpulse_test_email`. C'est la même lecture que
la notification de test MailPulse (`App\Services\MailPulse\DestinatairesDeTest`). Le corps de la
requête n'accepte aucune adresse.

## Corps

| champ | type | défaut | rôle |
|---|---|---|---|
| `avis` | chaîne, obligatoire | — | un des onze gabarits, ou `tous` |
| `dryRun` | booléen | `true` | `true` : rien ne part, la réponse dit ce qui partirait |

Gabarits admis (`App\Mail\Parents\AvisDExemple::MAILABLES`) : `absence-notification`,
`bulletin-published`, `inscription-confirmation`, `low-attendance`, `low-grades`,
`note-published`, `paiement-created`, `paiement-rejete`, `paiement-relance`,
`paiement-valide`, `reinscription-confirmation`.

Les données sont des exemples fixes (`AvisDExemple::donnees()`, élève « Awa Koné », liens vers
`ecole.test`), les mêmes que celles du test `tests/Unit/Mail/BoutonsDesAvisAuxParentsTest`.
Le sujet est celui que pose la classe Mailable, préfixé de `[Essai] `.

## Réponse

```json
{
  "ok": true,
  "dryRun": false,
  "avis": [
    {"avis": "paiement-valide", "destinataire": "essais@ecole.ci",
     "sujet": "[Essai] Paiement validé - Awa Koné", "statut": "envoyé"},
    {"avis": "low-grades", "destinataire": "essais@ecole.ci",
     "sujet": "[Essai] Alerte performance académique - Awa Koné",
     "statut": "erreur", "erreur": "MailPulse 503 …"}
  ]
}
```

- Une ligne par avis **et** par adresse de test (un courriel par destinataire).
- `statut` : `simulé` (`dryRun`), `envoyé`, ou `erreur` avec le message du transport.
  Une erreur est aussi journalisée (`Log::warning`).
- HTTP 200 si aucune ligne en erreur, 502 sinon.

## Erreurs

| code | cas |
|---|---|
| 403 | jeton sans `cli:admin` |
| 422 `errors.destinataires` | aucune adresse de test active configurée |
| 422 `errors.avis` | avis absent ou inconnu |

## Exemple

```bash
curl -s -X POST "$URL/api/cli/courriels/avis-parents/essai" \
  -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"avis":"tous","dryRun":false}'
```

## Historique

- Octobre 2026 : création.
