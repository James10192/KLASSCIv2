# Rule: KLASSCI est un client de MailPulse — jamais d'intégration sur mesure

## Quand s'active

Dès que tu touches à `app/Services/MailPulse/*`, `app/Services/ParentChatbot/*`, `config/services.php` (bloc `mailpulse`), aux réglages MailPulse de `ESBTPSettingsController`, ou que tu rédiges une issue, un rapport ou un plan qui mentionne MailPulse, ses applications externes, ses webhooks ou ses templates.

## Le principe (Marcel, 2 septembre 2026)

> « KLASSCI est un client de MailPulse. On ne fait pas une intégration sur mesure : KLASSCI utilise les services que MailPulse donne de manière générique à ses clients. »

MailPulse est un produit à part entière, avec ses propres clients. KLASSCI n'a aucun statut particulier chez lui : il consomme l'API v1, le rail « applications externes », les templates, les webhooks et les quotas exactement comme n'importe quel client. Le LMS, s'il devient client un jour, fera de même.

## Comment appliquer

**Côté MailPulse (dépôt `James10192/mailpulse`)** : tout ce qui est demandé doit servir n'importe quel client.
- Une fonctionnalité utile à KLASSCI se formule comme une capacité générique : `Contact.externalId` unique, quota et débit par application, codes de rejet documentés, assistant de branchement qui produit la configuration pour tout client, SDK minimal.
- Jamais de code, de constante, de branche ou de chemin `klassci` dans MailPulse. Jamais de mention de KLASSCI dans une règle produit MailPulse autrement que comme exemple de client.
- Une issue MailPulse ne cite KLASSCI que pour illustrer un scénario client (« un client qui gère plusieurs établissements… »).

**Côté KLASSCI (ce dépôt)** : KLASSCI s'adapte au contrat public de MailPulse, pas l'inverse.
- Choisir son propre découpage (une organisation MailPulse par établissement est la recommandation), ses templates WhatsApp approuvés, ses clés et secrets, et les stocker dans ses réglages.
- Si le contrat MailPulse manque d'une capacité, ouvrir une issue **générique** côté MailPulse et, en attendant, s'accommoder du contrat existant côté KLASSCI.
- Ne jamais réimplémenter côté KLASSCI ce que MailPulse offre (file, retry, consentement STOP, réconciliation) et ne jamais appeler un fournisseur (Meta, Orange, Resend) en direct.

## Anti-patterns à BLOQUER en review

1. Une issue ou un plan intitulé « intégration KLASSCI ↔ MailPulse » qui décrit du travail spécifique à KLASSCI dans le dépôt MailPulse.
2. Un endpoint, un champ, un flag ou une organisation « spéciale KLASSCI » dans MailPulse.
3. Le mot « moteur de notifications du groupe » pour décrire MailPulse : c'est un fournisseur de messagerie dont le groupe est client.
4. Du code d'envoi direct (`WhatsAppService`, `SmsService`) réactivable dans KLASSCI.

## Voir aussi

- `docs/audits/2026-09-02-expertise-adminklassci-mailpulse.md` — section « Ce qui engage KLASSCI »
- Issues mailpulse #20, #21 (capacités génériques) et KLASSCIv2 #821 (travail côté client)
