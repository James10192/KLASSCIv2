# MailPulse Test Notification

## Configuration

Ajouter ces variables dans `.env` du tenant KLASSCI :

```env
MAILPULSE_ENABLED=true
MAILPULSE_BASE_URL=https://mailpulse-two.vercel.app
MAILPULSE_API_KEY=
MAILPULSE_TIMEOUT=20
MAILPULSE_CONTACTS_ENDPOINT=/api/v1/contacts
MAILPULSE_MESSAGES_ENDPOINT=/api/v1/messages
MAILPULSE_SENDER_EMAIL=
MAILPULSE_SENDER_NAME=KLASSCI
TEST_API_SECRET=
TEST_NOTIFICATION_EMAIL=
TEST_NOTIFICATION_PHONE=
TEST_NOTIFICATION_PHONES=
```

`TEST_NOTIFICATION_EMAIL` est requis pour les tests email. `TEST_NOTIFICATION_PHONE` ou `TEST_NOTIFICATION_PHONES` est requis pour les tests WhatsApp. Aucun parent ou etudiant reel ne doit etre utilise.

`TEST_NOTIFICATION_PHONES` accepte plusieurs numeros separes par ligne, virgule ou point-virgule. Dans les parametres, `mailpulse_test_phones` est prioritaire sur l'ancien champ `mailpulse_test_phone`.

Ces valeurs peuvent aussi etre gerees depuis `ESBTP > Parametres > MailPulse`. La cle API n'est jamais affichee dans le formulaire. Laisser le champ vide conserve la cle existante. Les interrupteurs `Tests email` et `Tests WhatsApp` permettent d'activer seulement les canaux a utiliser.

`TEST_API_SECRET` est reserve a un endpoint dev standalone. L'implementation actuelle utilise l'option plus sure : endpoint admin-only via Sanctum `cli:admin`.

## Endpoint CLI Admin-Only

```http
POST /api/cli/mailpulse/test-notification
Authorization: Bearer <sanctum-token-cli-admin>
Content-Type: application/json
```

Body :

```json
{
  "event": "payment_received",
  "channel": "email",
  "dryRun": true
}
```

Evenements supportes :

- `payment_received`
- `absence_reported`
- `grade_published`
- `fee_reminder`

Canaux supportes :

- `email`
- `whatsapp`
- `both`

## Test Email

Dry-run, sans appel MailPulse :

```bash
php artisan mailpulse:test --event=payment_received --channel=email --dry-run=true
```

Envoi reel vers `TEST_NOTIFICATION_EMAIL` :

```bash
php artisan mailpulse:test --event=payment_received --channel=email --dry-run=false
```

## Test WhatsApp

Dry-run :

```bash
php artisan mailpulse:test --event=absence_reported --channel=whatsapp --dry-run=true
```

Envoi reel vers les numeros de test configures :

```bash
php artisan mailpulse:test --event=absence_reported --channel=whatsapp --dry-run=false
```

Via le wrapper `klassci-cli` :

```bash
klassci-cli mailpulse:test --event payment_received --channel both --dry-run false
```

## Limitations WhatsApp

- Meta exige des templates approuves pour les messages hors fenetre de conversation 24h.
- Si MailPulse retourne `TEMPLATE_REQUIRED`, il faut configurer le template WhatsApp dans Meta/MailPulse.
- Si MailPulse retourne `CHANNEL_NOT_CONFIGURED`, connecter le canal WhatsApp ou email dans MailPulse.
- Baileys peut se deconnecter. Dans ce cas, reconnecter la session cote MailPulse avant de relancer.

## Historique

- 2026-07-03 : ajout des numeros WhatsApp de test multiples et des toggles de canaux de test.
- 2026-07-02 : ajout du test MailPulse CLI/admin-only pour notifications KLASSCI simulees.
