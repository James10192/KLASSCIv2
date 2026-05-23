# Onboarding tenant WhatsApp Business — Procédure complète

> Phase 17 Plan v4 — Documentation production-ready KLASSCI.

## Vue d'ensemble

Chaque tenant KLASSCI (école) possède **son propre numéro WhatsApp Business** + son propre compte Meta Business. Branding propre + isolation totale du risque de ban. Procédure ~5-15 jours calendar selon réactivité Meta.

## Pré-requis tenant

- **Numéro de téléphone dédié** : ligne dédiée WhatsApp (pas un téléphone perso utilisé). Mobile ou fixe avec SMS/voice. **Ne doit JAMAIS avoir été utilisé pour WhatsApp personnel** (Meta refuse).
- **Documents juridiques** : Registre de commerce (RCCM) + Attestation d'établissement scolaire MENA + ID gérant
- **Site web officiel** ou page Facebook avec branding cohérent (Meta vérifie l'identité)
- **Adresse physique vérifiable** (utilisée pour bill from)

## Étapes (5-15 jours calendar)

### 1. Meta Business Manager (jour 1)
1. https://business.facebook.com/ → Créer compte Business Manager
2. Renseigner : nom légal école, adresse, site web, secteur "Éducation"
3. Ajouter administrateurs (1-2 personnes école)

### 2. WhatsApp Business Account (jour 1-2)
1. Business Manager → Comptes → WhatsApp → Ajouter compte
2. Choisir "Compte WhatsApp Business API"
3. Renseigner numéro de téléphone dédié
4. Recevoir SMS/voice OTP pour vérification numéro

### 3. Business Verification (jour 3-10, Afrique)
1. Business Manager → Paramètres → Vérification entreprise
2. Upload Registre de commerce (RCCM) + Attestation école MENA
3. Meta envoie email de confirmation possession adresse + site
4. Délai moyen Afrique : 5-7 jours (vs 24-48h Europe)
5. **Si rejet** : revérifier cohérence Nom légal Business ↔ RCCM ↔ Email domaine

### 4. Templates UTILITY (jour 10-14, parallèle au step 3)
Soumettre les 6 templates KLASSCI en français via WhatsApp Manager :

```
Template : inscription_confirmation
Catégorie : UTILITY
Langue : fr
Body : "Bonjour {{1}}, l'inscription de {{2}} en {{3}} pour l'année {{4}} est confirmée le {{5}}. Identifiants envoyés par email."
```

Variables `{{1}}` à `{{N}}` à utiliser dans le bon ordre. Footer recommandé : "Répondez STOP pour ne plus recevoir."

Templates KLASSCI standards :
- `inscription_confirmation` (5 variables)
- `paiement_valide` (6 variables)
- `paiement_rejete` (5 variables)
- `absence_notification` (6 variables)
- `bulletin_publie` (5 variables)
- `alerte_notes_faibles` (5 variables)

**Taux de rejet first-pass** : ~30%. Si rejet "Marketing-ish" : reformuler en plus factuel/transactionnel.

### 5. Récupération credentials (jour ~14)
1. Business Manager → WhatsApp Account → API Settings
2. Copier :
   - **Phone Number ID** (long ID Meta)
   - **WhatsApp Business Account ID**
   - **Access Token** : générer un token "System User" permanent (60j auto-rotate)
3. Définir **Webhook URL** : `https://{tenant}.klassci.com/api/webhooks/whatsapp`
4. Définir **Webhook Verify Token** : générer string aléatoire 32 chars (sera vérifié par MetaWhatsAppWebhookController::verify)
5. Subscribe webhook events : `messages` + `message_template_status_update`

### 6. Configuration adminKlassci (jour 14, ~30 min)
SuperAdmin KLASSCI ops :
1. Login https://admin.klassci.com → Tenants → {Tenant}
2. Section "WhatsApp" (UI Filament Phase 2) :
   - Phone Number ID : *coller*
   - Access Token : *coller* (chiffré côté DB encrypted cast)
   - Business Account ID : *coller*
   - Webhook Verify Token : *coller*
   - Activer toggle : `whatsapp_enabled = true`
3. Save → invalide cache côté tenant via webhook
4. Tester avec message-test à 1-2 parents internes école

### 7. Activation progressive (jour 15+)
- **Soft launch** : 1 type de notification (relances paiement) sur 10% des parents pendant 3 jours
- Surveiller dashboard cost + delivery rate + opt-out rate dans adminKlassci portail groupe
- Si KPIs OK (taux livraison > 95%, < 5 plaintes parents), élargir aux autres types

## Coûts attendus

- **Setup Meta** : 0 EUR (gratuit)
- **Numéro téléphone dédié** : ~5 000 FCFA/an (carte SIM Orange/MTN)
- **Coût récurrent par tenant Élite** : ~12-20 USD/mois pour ~5000 msgs Utility
  ($0.0040 USD/msg "Rest of Africa" tier confirmé Meta 2026)
- **Marge KLASSCI suggérée** : refacturer ~50-75 USD/mois/tenant Élite (option WhatsApp dans formule premium)

## Troubleshooting

### "Business Verification rejetée"
- Vérifier : Nom Business Manager = Nom RCCM (caractères près)
- Site web HTTPS valide + adresse + téléphone visible
- Photo de l'établissement + plaque officielle MENA si possible
- Resoumission limitée à 3 par mois

### "Template approval pending depuis 5+ jours"
- Catégorie : doit être `UTILITY` (pas Marketing)
- Variables : ordre cohérent dans body
- Pas de promo / urgence ("URGENT", "Last chance", etc.) → catégorisé Marketing
- Contact support Meta via dashboard si > 7 jours

### "Numéro suspended Meta"
- Cause fréquente : 50+ STOP/spam complaints en 7 jours
- Recovery : 30+ jours, nouveau numéro souvent requis
- **Préventif** : opt-in obligatoire + footer STOP visible + respect fenêtre 24h Meta

## Voir aussi

- `docs/whatsapp/RUNBOOK_OPS.md` — runbook ops quotidien (Phase 17)
- `docs/whatsapp/DISASTER_RECOVERY.md` — récupération ban / numéro / templates (Phase 20)
- `.claude/rules/adminklassci-tenant-management.md` — architecture multi-instance
- Plan v4 Phase 5 — Templates Meta + onboarding KYC tenants (cette doc)
