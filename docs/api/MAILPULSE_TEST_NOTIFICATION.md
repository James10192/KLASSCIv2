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
```

`TEST_NOTIFICATION_EMAIL` et `TEST_NOTIFICATION_PHONE` sont obligatoires pour tout test. Aucun parent ou étudiant réel ne doit être utilisé.

Ces valeurs peuvent aussi être gérées depuis `ESBTP > Paramètres > MailPulse`. La clé API n'est jamais affichée dans le formulaire. Laisser le champ vide conserve la clé existante.

`TEST_API_SECRET` est réservé à un endpoint dev standalone. L'implémentation actuelle utilise l'option plus sûre : endpoint admin-only via Sanctum `cli:admin`.

## Endpoint CLI admin-only

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

Événements supportés :

- `payment_received`
- `absence_reported`
- `grade_published`
- `fee_reminder`

Canaux supportés :

- `email`
- `whatsapp`
- `both`

## Test email

Dry-run, sans appel MailPulse :

```bash
php artisan mailpulse:test --event=payment_received --channel=email --dry-run=true
```

Envoi réel vers `TEST_NOTIFICATION_EMAIL` :

```bash
php artisan mailpulse:test --event=payment_received --channel=email --dry-run=false
```

## Test WhatsApp

Dry-run :

```bash
php artisan mailpulse:test --event=absence_reported --channel=whatsapp --dry-run=true
```

Envoi réel vers `TEST_NOTIFICATION_PHONE` :

```bash
php artisan mailpulse:test --event=absence_reported --channel=whatsapp --dry-run=false
```

Via le wrapper `klassci-cli` :

```bash
klassci-cli mailpulse:test --event payment_received --channel both --dry-run false
```

## Limitations WhatsApp

- Meta exige des templates approuvés pour les messages hors fenêtre de conversation 24h.
- Si MailPulse retourne `TEMPLATE_REQUIRED`, il faut configurer le template WhatsApp dans Meta/MailPulse.
- Si MailPulse retourne `CHANNEL_NOT_CONFIGURED`, connecter le canal WhatsApp ou email dans MailPulse.
- Baileys peut se déconnecter. Dans ce cas, reconnecter la session côté MailPulse avant de relancer.

## Historique

- 2026-07-02 : ajout du test MailPulse CLI/admin-only pour notifications KLASSCI simulées.
