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
MAILPULSE_REAL_WORKFLOWS_ENABLED=false
TEST_API_SECRET=
TEST_NOTIFICATION_EMAIL=
TEST_NOTIFICATION_PHONE=
TEST_NOTIFICATION_PHONES=
```

`TEST_NOTIFICATION_EMAIL` est requis pour les tests email. `TEST_NOTIFICATION_PHONE` ou `TEST_NOTIFICATION_PHONES` est requis pour les tests WhatsApp et SMS. Aucun parent ou etudiant reel ne doit etre utilise.

`TEST_NOTIFICATION_PHONES` accepte plusieurs numeros separes par ligne, virgule ou point-virgule. Dans les parametres, `mailpulse_test_phone_recipients` est prioritaire sur `mailpulse_test_phones` et l'ancien champ `mailpulse_test_phone`.

Ces valeurs peuvent aussi etre gerees depuis `ESBTP > Parametres > MailPulse`. La cle API n'est jamais affichee dans le formulaire. Laisser le champ vide conserve la cle existante. Chaque email et chaque numero WhatsApp/SMS dispose de son propre interrupteur actif/inactif. Le canal `both` reste limite a email + WhatsApp pour conserver le comportement historique.

`TEST_API_SECRET` est reserve a un endpoint dev standalone. L'implementation actuelle utilise l'option plus sure : endpoint admin-only via Sanctum `cli:admin`.

## Workflows KLASSCI reels

Le moteur de test reste limite aux destinataires de test. Les workflows reels sont branches separement et restent desactives par defaut.

Pour activer les envois MailPulse vers les contacts parents reels d'un tenant :

```env
MAILPULSE_REAL_WORKFLOWS_ENABLED=true
```

ou activer le setting tenant `mailpulse_real_workflows_enabled`.

Workflows branches :

- `payment_received` : notification parent lors d'un paiement recu.
- `absence_reported` : notification parent lors d'une absence signalee.
- `grade_published` : notification parent lorsqu'une note publiee est enregistree.
- `fee_reminder` : notification parent lors d'un rappel de paiement.

Les envois reels respectent les preferences parent existantes (`email`, `whatsapp`) et journalisent uniquement `event`, `channel`, `status`, `requestId`, `parent_id` et `student_id`. Les secrets et la cle API ne sont jamais logges.

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
- `sms`
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

## Test SMS

Dry-run :

```bash
php artisan mailpulse:test --event=grade_published --channel=sms --dry-run=true
```

Envoi reel vers les numeros de test configures :

```bash
php artisan mailpulse:test --event=grade_published --channel=sms --dry-run=false
```

Le canal SMS cree un contact MailPulse avec `preferred_channel=sms`, puis soumet une intention SMS a `/api/v1/messages`. MailPulse livre ensuite le SMS via son worker Orange, pas depuis KLASSCI.

## Limitations WhatsApp

- Meta exige des templates approuves pour les messages hors fenetre de conversation 24h.
- Si MailPulse retourne `TEMPLATE_REQUIRED`, il faut configurer le template WhatsApp dans Meta/MailPulse.
- Si MailPulse retourne `CHANNEL_NOT_CONFIGURED`, connecter le canal WhatsApp ou email dans MailPulse.
- Baileys peut se deconnecter. Dans ce cas, reconnecter la session cote MailPulse avant de relancer.

## Historique

- 2026-07-03 : ajout des numeros WhatsApp de test multiples et des toggles de canaux de test.
- 2026-07-02 : ajout du test MailPulse CLI/admin-only pour notifications KLASSCI simulees.
