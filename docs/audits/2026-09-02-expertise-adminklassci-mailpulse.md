# Expertise SaaS — adminKlassci et MailPulse (2 septembre 2026)

Expertise menée le 2 septembre 2026 sur les deux applications voisines de KLASSCI : **adminKlassci** (application maître SaaS, Laravel 12 + Filament 3.3, HEAD `6763622`) et **MailPulse** (plateforme de messagerie multicanal dont KLASSCI est un client parmi d'autres, Next.js 16 + Prisma + Convex, HEAD `8901d0c`). Elle prolonge l'expertise KLASSCI du matin avec le même dispositif : un workflow de douze experts spécialisés (six dimensions par dépôt), puis un contradicteur indépendant par constat critique, chargé de réfuter dans le code. Cinquante-cinq agents au total, 724 lectures et recherches, soixante-dix minutes.

**195 constats** sur 12 dimensions · **43 critiques** (42 confirmés par contradiction, 1 rétrogradé) · adminKlassci 93 (23 critiques) · MailPulse 102 (20 critiques) · 44 issues créées, 6 complétées.

Version HTML (artifact) : https://claude.ai/code/artifact/40e323de-b1be-48df-a90d-fc976daa3249 — compagnon de `2026-09-02-expertise-saas-klassci.md`.

## Verdict

### adminKlassci

adminKlassci est deux applications de maturité opposée cousues dans le même dépôt. Le **portail groupe** (fondateurs DG et DGA, KPIs consolidés, alertes, SSO vers les écoles) est structuré, typé, feature-flaggé et couvert par plusieurs centaines de tests : c'est du code de produit. La **couche ops** qui fait vivre le SaaS (provisionner, déployer, sauvegarder, surveiller, servir les limites au paywall des écoles) est restée au stade prototype d'octobre 2025 : logique métier entière dans des commandes artisan de 400 à 500 lignes, appelées en synchrone dans la requête HTTP ou mises en file sans aucun worker, zéro test, et des sauvegardes qui s'effacent elles-mêmes après trente jours. Le contrat maître↔tenant n'existe que sous forme d'un JSON implicite que l'école n'applique qu'à moitié : suspendre un tenant dans le panel n'a aucun effet.

Trois découvertes changent la priorité de tout le reste. Des secrets de production (mot de passe SMTP, clé d'application, client Orange, jeton d'un tenant) sont recopiés en clair dans la documentation versionnée depuis le premier commit. Un membre de groupe peut, en changeant son email de profil et en appelant une méthode Livewire publique, obtenir une session superAdmin sur n'importe quelle école, y compris hors de son groupe. Et le paramètre `branch` du déploiement est interpolé dans un shell sans échappement : quiconque détient le jeton du webhook GitHub ou un compte Filament de niveau facturation exécute des commandes sur le compte cPanel qui héberge les six écoles.

### MailPulse

MailPulse est à deux vitesses. Le rail « applications externes » (chiffrement sous clé maître, signatures HMAC versionnées, épinglage anti-SSRF, machine d'état idempotente, outbox de rappels avec retry) et la file SMS Orange (baux, claims, accusés durables) sont d'un niveau production : c'est le modèle à généraliser. Mais le cœur du dashboard n'a jamais eu de revue d'isolation : au moins huit server actions s'exécutent sans session ou sans filtre d'organisation (supprimer un tag le supprime chez tous les clients, sans même être connecté), trois pages agrègent les données de toute la plateforme parce que deux modèles hérités n'ont pas de colonne d'organisation, le rôle « admin » d'une organisation ouvre la console de tous les tenants, et le backend temps réel Convex est lisible et inscriptible par quiconque connaît un identifiant d'organisation, diffusé dans chaque webhook sortant.

Côté envois, les campagnes email et WhatsApp tournent en boucle synchrone dans une Server Action : au timeout Vercel, la campagne reste verrouillée sans reprise, et le seul chemin de sortie renvoie à tous les destinataires déjà servis. Les messages transactionnels des écoles n'ont aucun retry : un fournisseur en 503 pendant trente secondes fige les reçus de paiement en « soumission inconnue » définitive. La chaîne de migrations échoue réellement sur une base neuve. Enfin la promesse de conformité (consentement, désinscription, données personnelles) n'a aucun support technique : pas de preuve de consentement, désinscription par simple lien en anglais, conditions d'utilisation qui pointent vers « # ».

## Ce qui est solide

**adminKlassci**

- Portail groupe correctement scopé : chaque page et widget part de `auth('group')->user()->group`, aucun IDOR trouvé sur établissements, KPIs, alertes ou préférences.
- Flux d'invitation sérieux : mot de passe temporaire, jeton haché SHA-256, URL signée 24 h, rotation forcée du mot de passe.
- Vérification SSO côté tenant bien pensée : `hash_equals`, expiration, liste blanche de redirection stricte, rate limit, audit de chaque tentative.
- Architecture du portail : value objects de période, providers KPI et finances découplés par interface, agrégateur parallèle avec isolation d'erreur par tenant, feature flags documentés avec défauts sûrs.
- Journal de déploiement étape par étape en JSON avec durées, détection du binaire PHP CloudLinux, remise en ligne dans le `catch`.
- Mode `--dry-run` sur cinq commandes, ingestion du stockage réel défensive et testée, rotation de logs en streaming atomique.

**MailPulse**

- TypeScript strict réellement respecté : zéro `any`, zéro `ts-ignore` hors tests.
- Rail applications externes exemplaire : AES-256-GCM sous KEK, signatures HMAC versionnées avec fenêtre de 5 min et comparaison constant-time, résolution Meta fail-closed, épinglage DNS anti-SSRF des rappels.
- File SMS : lease unique avec renouvellement, claim conditionnel, backoff borné, récupération des claims expirés en « soumission inconnue » plutôt qu'en renvoi aveugle.
- Idempotence API : claim commité avant tout appel fournisseur, hash du corps, récupération des claims orphelins ; webhooks Resend en transaction sérialisable avec machine d'état.
- Préflights de déploiement bloquants (clé maître, secret cron, doublons d'identifiants fournisseur, historique de migrations), CI qui lint, typecheck et build sur chaque push.
- Design system appliqué (tokens, polices, composants shadcn), aide délivrabilité SPF/DKIM de qualité, consentement respecté à l'envoi par `canReceiveChannel`.

## Les 12 risques bloquants

1. **Secrets de production dans la documentation versionnée** ([adminKlassci #66](https://github.com/James10192/adminKlassci/issues/66)) — Mot de passe SMTP du support, clé d'application Laravel, identifiant et secret Orange SMS, jeton API du tenant presentation, présents depuis le premier commit. Rotation, purge de l'historique et détection en CI.
2. **Usurpation de n'importe quel compte de n'importe quelle école** ([adminKlassci #67](https://github.com/James10192/adminKlassci/issues/67)) — `getSsoUrl` est une méthode Livewire publique sans contrôle d'appartenance au groupe, l'email de profil est auto-modifiable, le nonce n'est jamais vérifié, `metadata.base_url` redirige le jeton vers un domaine arbitraire, aucune émission n'est journalisée.
3. **Exécution de commandes sur le compte cPanel via `branch`** ([adminKlassci #68](https://github.com/James10192/adminKlassci/issues/68)) — Interpolé dans `git checkout {$branch}` exécuté par `sh -c`, sans regex ni échappement, depuis le webhook GitHub et le formulaire Filament ; sans `tenant_code` le webhook déploie tout le parc.
4. **Les sauvegardes ne protègent rien** ([adminKlassci #69](https://github.com/James10192/adminKlassci/issues/69)) — Le nettoyage des expirés efface le dossier entier du tenant ; un dump échoué est marqué réussi (code retour de gzip) ; `full` devient `manual` vide ; la base maître n'est jamais sauvegardée ; tout reste sur le même compte ; aucune restauration.
5. **Suspendre un tenant n'a aucun effet** ([adminKlassci #77](https://github.com/James10192/adminKlassci/issues/77)) — Le tenant ne lit ni `status` ni `blocked_features` ; un dépassement de quota bloque toute l'application au lieu des seules fonctions concernées ; les fonctionnalités du plan ne sont jamais transmises ; `urgency` vaut « expiré » pour tout tenant sans date.
6. **La grille Signature bloque tout client au 6e employé** ([adminKlassci #71](https://github.com/James10192/adminKlassci/issues/71)) — `max_users = 5` sur Essentiel et PRO alors que le maître compte `users = staff` : dès que le paywall est actif, l'école est verrouillée. Le plan PRO et le statut « inactif » n'existent d'ailleurs pas dans l'ENUM MySQL.
7. **Server actions sans session ni organisation** ([mailpulse #2](https://github.com/James10192/mailpulse/issues/2)) — Supprimer un tag, un segment ou une automation d'un autre client, lire ses contacts, réécrire ses workflows ; planifier une campagne depuis le domaine vérifié d'un autre tenant.
8. **Trois pages affichent toute la plateforme** ([mailpulse #3](https://github.com/James10192/mailpulse/issues/3)) — Tags, Désabonnements et Analytics comptent et listent les données de tous les clients ; `ContactTag` et `EmailEvent` n'ont pas de colonne d'organisation ; la formule des taux est fausse.
9. **Convex ouvert à tous** ([mailpulse #5](https://github.com/James10192/mailpulse/issues/5)) — Aucune authentification sur les requêtes et mutations temps réel ; l'identifiant d'organisation suffit et il est diffusé comme `tenant_id` dans chaque webhook ; en plus, une seconde source de vérité en partie morte.
10. **Campagnes en boucle synchrone sans reprise** ([mailpulse #6](https://github.com/James10192/mailpulse/issues/6)) — Timeout Vercel = campagne bloquée en SENDING pour toujours ; annuler puis renvoyer = double envoi aux destinataires déjà servis ; le cron planifié n'est pas atomique et avale les erreurs.
11. **Transactionnels sans retry** ([mailpulse #7](https://github.com/James10192/mailpulse/issues/7)) — Un 5xx, un 429 ou un timeout Resend/Meta devient une « soumission inconnue » définitive, jamais réclamée, sans bouton de renvoi ; les appels Meta et Resend n'ont pas de timeout ; le repli sur un second format de numéro peut envoyer à un tiers.
12. **La chaîne de migrations échoue sur une base neuve** ([mailpulse #15](https://github.com/James10192/mailpulse/issues/15)) — Rejeu réel par le contradicteur : `column smsEnabled already exists` à cause d'un dossier de migration hors format ; la CI valide un schéma différent de la prod (index partiels absents) ; la base Neon a hébergé d'autres produits.

## adminKlassci par dimension

### Sécurité et isolation (14 constats, 3 critiques)

> La surface d'authentification est correctement câblée (deux guards séparés `web`/`group`, CSRF + AuthenticateSession sur les deux panels, scoping systématique du portail groupe sur `auth('group')->user()->group`, invitation à jeton haché + rotation forcée), et les briques SSO ont été conçues avec sérieux côté tenant (HMAC hash_equals, TTL 2 min, anti open-redirect strict, rate-limit, audit). Mais l'isolation réelle repose sur trois hypothèses fausses : que seul un administrateur puisse émettre un jeton SSO pour un tenant donné (faux : `getSsoUrl` est une méthode Livewire publique sans contrôle d'appartenance au groupe, et tout membre peut changer son propre email, donc usurper n'importe quel compte de n'importe quel tenant), que le webhook de déploiement ne reçoive que des branches sûres (faux : `branch` non filtré part dans `git checkout` via un shell), et que les secrets vivent hors du dépôt (faux : mot de passe SMTP, APP_KEY, secret Orange et un MASTER_API_TOKEN sont commis en clair dans la documentation). Le nonce SSO est généré mais jamais vérifié, le maître ne journalise aucune émission de jeton, et aucune mutation Filament sensible (régénération de token, suppression de tenant, changement de rôle) n'est tracée. Les identifiants MySQL et le token API de chaque école sont envoyés au navigateur de tout administrateur, quel que soit son rôle, et le panel maître n'a ni MFA ni restriction d'IP alors qu'il donne accès à l'exécution de `git`/`artisan` sur le serveur mutualisé. Enfin, la CI n'exécute que la suite Unit : aucun des mécanismes ci-dessus n'est protégé par un test. En l'état, un technicien support « billing » ou un DGA de groupe mécontent dispose de chemins concrets vers le super-administrateur de chaque école ; ce n'est pas encore un SaaS opérable par un tiers sans revue de code. Les correctifs sont majoritairement courts (S) et doivent précéder toute ouverture du panel à des profils support externes.

**Critiques confirmés**

- Secrets de production commis dans le dépôt : mot de passe SMTP, APP_KEY, secret Orange SMS et MASTER_API_TOKEN d'un tenant — `CLAUDE.md:1323`
- Usurpation de n'importe quel compte tenant par un membre de groupe : `getSsoUrl` est une méthode Livewire publique sans contrôle d'appartenance, et l'email SSO est auto-modifiable — `app/Filament/Group/Widgets/EstablishmentCardsWidget.php:35`
- Webhook /api/deploy : paramètre `branch` non filtré injecté dans un shell (`git checkout {$branch}`), déploiement de tout le parc par défaut, jobs empilés sans worker — `app/Http/Controllers/API/DeployWebhookController.php:44`

**Forces**

- Scoping du portail groupe systématique : `EstablishmentResource::getEloquentQuery()` filtre sur `group_id` (app/Filament/Group/Resources/EstablishmentResource.php:100-104) et chaque page/widget part de `auth('group')->user()->group` (GroupDashboard.php:45, KpiOverviewWidget.php:25, GroupAlertsWidget.php:21…) — aucun IDOR trouvé sur les établissements, KPI, alertes ou préférences.
- Deux guards distincts (`web` pour saas_admins, `group` pour group_members, config/auth.php:38-47) ; `GroupMember::canAccessPanel` exige `is_active` ET `panel id === 'group'` (GroupMember.php:108-111) ; `User::canAccessPanel` exige `is_active` et un rôle connu (User.php:63-67).
- Flux d'invitation solide : mot de passe temporaire `Str::password(16)` (TemporaryPasswordGenerator.php:27-32), jeton d'activation stocké haché SHA-256 (GroupMemberInvitationService.php:52), URL signée temporaire 24 h (:78-86), rotation forcée par `EnsurePasswordChanged` (EnsurePasswordChanged.php:25-38), `invitation_token` dans `$hidden` (GroupMember.php:33-37).
- Vérification SSO côté tenant bien pensée : `hash_equals` (KLASSCIv2 SsoTokenVerifier.php:28), exp obligatoire (:38), contrôle `tenant_code` (GroupPortalSsoController.php:57-61), whitelist anti open-redirect stricte (:108-129), rate-limit 10/min/IP (:38-43), audit de chaque tentative (:131-148), `Referrer-Policy: no-referrer` (:93-96).
- Webhook de déploiement : `hash_equals` sur le Bearer (DeployWebhookController.php:33), refus 500 si le secret n'est pas configuré (:26-29), `tenant_code` contraint par regex (:43), tentatives non autorisées journalisées avec IP/UA (:34-37).

**Issues** : [#66](https://github.com/James10192/adminKlassci/issues/66), [#67](https://github.com/James10192/adminKlassci/issues/67), [#68](https://github.com/James10192/adminKlassci/issues/68), [#80](https://github.com/James10192/adminKlassci/issues/80), [#59](https://github.com/James10192/adminKlassci/issues/59)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Secrets de production commis dans le dépôt : mot de passe SMTP, APP_KEY, secret Orange SMS et MASTER_API_TOKEN d'un tenant | `CLAUDE.md:1323` | S | confirmé — Tout confirmé, ligne exacte (1323). 4 secrets exploitables tels quels : MASTER_API_TOKEN accepté en query string par VerifyTenantApiToken.php sans expiration/scope ; MAIL_PASSWORD SMTP réel dupliqué dans 2 fichiers ; ORANGE_CLIENT_SECRET + son Ba… |
| Critique | Usurpation de n'importe quel compte tenant par un membre de groupe : `getSsoUrl` est une méthode Livewire publique sans contrôle d'appartenance, et l'email SSO est auto-modifiable | `app/Filament/Group/Widgets/EstablishmentCardsWidget.php:35` | M | confirmé — Entièrement confirmé, ligne exacte. EstablishmentCardsWidget.php:35-63 (méthode publique Livewire) fait Tenant::where('code',$tenantCode)->first() sans aucune vérification group_id (aucun global scope sur Tenant, vérifié). Le token signé (SsoToke… |
| Critique | Webhook /api/deploy : paramètre `branch` non filtré injecté dans un shell (`git checkout {$branch}`), déploiement de tout le parc par défaut, jobs empilés sans worker | `app/Http/Controllers/API/DeployWebhookController.php:44` | S | confirmé — Confirmé intégralement : ligne 44 valide `branch` sans regex (contraste avec `tenant_code` :43 qui a `regex:/^[a-z0-9\-]+$/`) ; la valeur passe telle quelle dans `--branch` (61-62) puis Artisan::queue (74) jusqu'à TenantDeploy.php:155-156 où elle… |
| Majeur | Rejeu du jeton SSO : nonce généré mais jamais vérifié, jeton transporté en GET, aucune journalisation d'émission côté maître | `app/Services/SsoTokenSigner.php:34` | S |  |
| Majeur | `metadata.base_url` non documenté redirige le SSO vers un domaine arbitraire, et `metadata` est éditable par tout administrateur maître | `app/Filament/Group/Widgets/EstablishmentCardsWidget.php:65` | S |  |
| Majeur | Identifiants MySQL et token API de chaque école envoyés au navigateur de tout administrateur via l'état Livewire de la page tenant | `app/Filament/Resources/TenantResource.php:106` | M |  |
| Majeur | Aucune piste d'audit sur les mutations sensibles du panel maître (token API, branche, plan, statut, suppression, rôles de groupe) | `app/Filament/Resources/TenantResource/Pages/ViewTenant.php:233` | M |  |
| Majeur | La CI n'exécute que la suite Unit : aucun test sur l'authentification, le middleware API, le SSO, le webhook ni les rôles | `.github/workflows/tests.yml:53` | S |  |
| Majeur | Les logs applicatifs des tenants (messages d'exception, SQL, emails) sont copiés dans la base maître et affichés à tout administrateur | `app/Console/Commands/TenantHealthCheck.php:387` | S |  |
| Majeur | Panel maître sans MFA, sans restriction d'IP ni politique de mot de passe alors qu'il donne l'exécution de commandes serveur et les identifiants de toutes les écoles | `app/Providers/Filament/AdminPanelProvider.php:138` | M |  |
| Mineur | Lien de désabonnement signé sans expiration, et tout `type` inconnu coupe définitivement tous les emails d'un membre | `app/Mail/Group/AbstractGroupAlertMail.php:42` | S |  |
| Mineur | Middleware `tenant.api` accepte les jetons des tenants suspendus/archivés, `/api/lms/tenants` énumère tout le parc avec n'importe quel jeton, et `trigger` non borné part dans les logs | `app/Http/Middleware/VerifyTenantApiToken.php:30` | S |  |
| Mineur | Modèle d'autorisation dupliqué et mort : `User` et `SaasAdmin` sur la même table, méthodes `canDeploy/canManageBilling/canViewReports` jamais appelées | `app/Models/SaasAdmin.php:97` | M |  |
| Mineur | Table `password_reset_tokens` partagée par les deux brokers (`users` et `group_members`) : un jeton de réinitialisation est interchangeable entre un admin maître et un membre de groupe ayant le même email | `config/auth.php:106` | S |  |

</details>

### Architecture et maintenabilité (14 constats, 3 critiques)

> adminKlassci est en réalité deux applications de maturité opposée cousues dans le même dépôt. Le portail groupe (app/Services/Group, Contracts, Enums, Support/Period, TenantAggregator) est structuré, typé, feature-flaggé et couvert par ~300 tests : c'est du code de produit. La couche « ops » qui fait vivre le SaaS (provision, deploy, health-check, backup, limits) est restée au stade prototype d'octobre 2025 : la logique métier vit intégralement dans des commandes artisan de 400-500 lignes, invoquées depuis Filament par Artisan::call synchrone, par Artisan::queue sans worker planifié, ou par proc_open + nohup, et parse sa réussite en cherchant un « ❌ » dans la sortie console. Cette couche n'a aucun test, la CI n'exécute que la suite Unit, et une partie des tests existants sont des greps sur le code source. Le modèle Tenant n'a ni enum de statut ni enum de plan : le provisioning écrit un statut que l'ENUM MySQL refuse, Filament en propose un autre inconnu, et les plans sont configurés à trois endroits différents. Le contrat /api/tenants/{code}/limits consommé par tous les PaywallMiddleware est un tableau ad hoc non testé qui recalcule ses propres seuils au lieu d'utiliser Tenant::daysRemaining(), avec une incohérence >= / > entre isOverLimit et isOverQuota. Le scheduler est déclaré à deux endroits (bootstrap/app.php et routes/console.php) plus un Kernel.php mort, et la documentation racine (13 fichiers, 9 700 lignes, CLAUDE.md de 5 391 lignes) contredit le code sur les fréquences et décrit des fichiers qui n'existent pas. Ce qui bloque un « vrai SaaS opérable sans code » n'est pas un manque de fonctionnalités mais l'absence d'une couche domaine : tant que déployer/provisionner/surveiller ne sont pas des Actions/Jobs typés avec résultat structuré et tests, chaque bouton Filament reste un pari sur le timeout PHP et chaque nouveau plan/statut exige un commit. Structure cible : app/Domain/Tenant/{Provisioning,Deployment,Health,Backup,Limits} (Actions + Jobs + DTO de résultat), config/hosting.php pour tout ce qui est LWS/cPanel, enums TenantStatus/PlanSlug castés sur le modèle, un TenantReadRepository unique pour les requêtes esbtp_*, commandes artisan réduites à des adaptateurs, Feature suite en CI avec MySQL de service et tests contractuels sur /limits.

**Critiques confirmés**

- tenant:provision écrit status='provisioning' que l'ENUM MySQL refuse — aucun enum TenantStatus, 3 vocabulaires de statut divergents — `app/Console/Commands/TenantProvision.php:230`
- Déploiement synchrone dans la requête HTTP (ViewTenant) et déploiement en file sans worker (TenantDeploymentResource, webhook) : deux boutons, deux sémantiques, aucun fiable — `app/Filament/Resources/TenantResource/Pages/ViewTenant.php:184`
- CI n'exécute que la suite Unit ; zéro test sur provisioning/deploy/limits/middleware/health/backup ; une partie des tests sont des greps sur le code source — `.github/workflows/tests.yml:53`

**Forces**

- Le portail groupe est correctement découpé : interfaces App\Contracts\Group (GroupKpiProviderInterface, GroupFinancialsProviderInterface) liées dans GroupServiceProvider (l.23-24), TenantBillingContext scoped (l.20), value objects Support/Period, enums AlertSeverity/AlertType/SubscriptionTier, AlertPayload typé.
- TenantAggregator (app/Services/Group/TenantAggregator.php l.27-59) isole proprement la parallélisation Concurrency::run avec fallback synchrone et isolation d'erreur par tenant — un vrai pattern réutilisable.
- config/group_portal.php est exemplaire : chaque flag est documenté, par défaut OFF pour les fonctions à risque (notifications, storage ingestion, invitation flow), seuils métier externalisés.
- TenantDeploy journalise chaque étape avec durée dans deployment_log JSON (l.139-229), remet le site en ligne dans le catch (l.256), tolère les migrations « already exists » (l.309) et détecte le binaire PHP CloudLinux (l.346-369).
- Le pattern RelationManagers en lecture seule (form()->schema([]) dans Backups/Deployments/HealthChecksRelationManager l.17-23) est appliqué de façon cohérente, conforme à la rule adminklassci-tenant-management.

**Issues** : [#78](https://github.com/James10192/adminKlassci/issues/78), [#79](https://github.com/James10192/adminKlassci/issues/79), [#70](https://github.com/James10192/adminKlassci/issues/70), [#74](https://github.com/James10192/adminKlassci/issues/74), [#77](https://github.com/James10192/adminKlassci/issues/77)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | tenant:provision écrit status='provisioning' que l'ENUM MySQL refuse — aucun enum TenantStatus, 3 vocabulaires de statut divergents | `app/Console/Commands/TenantProvision.php:230` | M | confirmé — Confirmé sans réserve. TenantProvision.php:230 insère bien `'status' => 'provisioning'` (Tenant::create, appelé étape 1/17 ligne 70) alors que la migration 2025_10_11_135529 (aucune migration ultérieure ne modifie cet ENUM) définit `enum('status'… |
| Critique | Déploiement synchrone dans la requête HTTP (ViewTenant) et déploiement en file sans worker (TenantDeploymentResource, webhook) : deux boutons, deux sémantiques, aucun fiable | `app/Filament/Resources/TenantResource/Pages/ViewTenant.php:184` | M | confirmé — Toutes les preuves confirmées telles quelles : ViewTenant.php:184-191 (Artisan::call synchrone avec --skip-backup forcé, succès jugé sur absence de '❌' dans l'output), TenantDeploy.php (down l.146, migrate avec throw l.190-195, up normal l.227, u… |
| Critique | CI n'exécute que la suite Unit ; zéro test sur provisioning/deploy/limits/middleware/health/backup ; une partie des tests sont des greps sur le code source | `.github/workflows/tests.yml:53` | M | confirmé — Confirmé intégralement. tests.yml:53 exécute uniquement --testsuite=Unit ; aucun autre job CI n'appelle Feature (grep sur le workflow ne montre qu'une seule step pest). Les 21 fichiers tests/Feature/{Group,Livewire} ne contiennent aucun match sur… |
| Majeur | Contrat /api/tenants/{code}/limits : tableau ad hoc non typé, seuils recalculés localement, `urgency='expired'` pour tout tenant sans date de fin, >= vs > incohérents | `app/Http/Controllers/API/TenantLimitsController.php:86` | S |  |
| Majeur | Scheduler déclaré à deux endroits + Kernel.php mort : tenant:update-stats tourne deux fois par heure, docs et code contradictoires sur les fréquences | `bootstrap/app.php:22` | S |  |
| Majeur | La logique métier ops vit dans des commandes artisan de 400-500 lignes, appelées par 3 mécanismes différents depuis Filament — aucune Action/Job/résultat typé | `app/Console/Commands/TenantHealthCheck.php:85` | L |  |
| Majeur | env() lu hors config dans 9 fichiers (cassé par config:cache) + hébergeur LWS codé en dur partout (c2569688c, .klassci.com, chemins PHP CloudLinux dupliqués) | `app/Console/Commands/TenantDeploy.php:30` | M |  |
| Majeur | Plans tarifaires configurés à 3 endroits (match hardcodé du provisioning, table subscription_plans, littéraux Filament) — la grille Filament ne pilote pas le provisioning | `app/Console/Commands/TenantProvision.php:383` | M |  |
| Majeur | TenantAggregationService (1145 lignes) reste un god service : split annoncé et différé, computeGroupHealthMetrics 140 lignes avec 8 collecteurs par référence, connaissance du schéma tenant dupliquée 10× | `app/Services/TenantAggregationService.php:362` | L |  |
| Majeur | Deux mécanismes de connexion aux DB tenants, credentials stockés en clair (cast array) et mot de passe mysqldump passé sur la ligne de commande | `app/Console/Commands/TenantHealthCheck.php:161` | S |  |
| Majeur | Monitoring en faux vert : queue_workers toujours healthy « non implémenté », disk_space lit une valeur qui vaut 0 tant que l'ingestion storage est OFF | `app/Console/Commands/TenantHealthCheck.php:461` | S |  |
| Mineur | Documentation racine obsolète et contradictoire : 13 fichiers .md (9 700 lignes), CLAUDE.md-journal de 5 391 lignes, README qui décrit un projet non initialisé et des symlinks inexistants | `README.md:53` | S |  |
| Mineur | Conventions Filament : logique shell/git dans les closures ->action(), doublons constantes+enum et TenantActivityLog::create vs ::log | `app/Filament/Resources/TenantResource/Pages/ViewTenant.php:314` | S |  |
| Mineur | Outillage qualité absent : pas de Pint, pas de Larastan, pas de tests d'architecture malgré pest-plugin-arch autorisé, CI mono-PHP avec --ignore-platform-req | `composer.json:38` | S |  |

</details>

### Provisioning, déploiement, sauvegardes, supervision (17 constats, 6 critiques)

> Cette dimension est un ensemble de scripts Artisan écrits d'un seul jet en octobre 2025 (`git log` : aucun commit sur TenantDeploy/TenantBackup/CleanupOldBackups depuis), jamais testés (0 test sur provision/deploy/backup/health-check, la suite Pest ne couvre que le portail groupe) et jamais éprouvés par un exercice de restauration. Le déploiement est la brique la plus aboutie (journal JSON par étape, détection du binaire CloudLinux, remise en ligne en `catch`), mais il déclare « ok » un backup qui a pu échouer, « success » une migration partiellement appliquée, et il exécute une branche non échappée dans un shell. La chaîne de sauvegarde est la faiblesse la plus grave : un dump vide passe en `completed`, le nettoyage des expirés efface le dossier entier de sauvegardes du tenant, la base maître n'est jamais sauvegardée, tout reste sur le même compte cPanel, et il n'existe ni commande de restauration ni test de restauration. La supervision produit des signaux faux (stockage remis à 0 chaque heure, `application_errors` structurellement « degraded » face à des logs `daily`, `queue_workers` fictif) et un outage survenu à 11 h n'est jamais remonté par `tenant:send-alerts`. Le cycle de vie « SaaS » est encore manuel : `tenant:discover` importe une école Élite en plan Free et la fait bloquer par le paywall à la prochaine mise à jour des stats. Pour qu'un technicien non-développeur opère 20 tenants, il manque : un runbook de restauration exécuté et chronométré, une file de jobs réellement consommée, un verrou de déploiement, une alerte temps réel dédupliquée, des sondes qui mesurent la réalité (disque, workers, logs du jour), un nettoyage des tables de télémétrie, et une documentation alignée sur le code (CLAUDE.md décrit un `reset --hard`, un SSH et un `isOnProductionServer()` qui n'existent pas). Les forces existent (dry-run sur plusieurs commandes, journal de déploiement, ingestion stockage bien conçue et testée) mais elles ne suffisent pas à qualifier la plateforme d'opérable sans code.

**Critiques confirmés**

- Le nettoyage des backups expirés supprime le dossier ENTIER de sauvegardes du tenant (toutes générations confondues) — `app/Console/Commands/CleanupOldBackups.php:363`
- Un dump MySQL qui échoue est marqué `completed` (exit code de gzip, pas de mysqldump) ; les backups `full` planifiés deviennent `manual` et un retry `manual` produit un backup vide « réussi » — `app/Console/Commands/TenantBackup.php:167`
- La base maître `klassci_master` n'est jamais sauvegardée et tout (maître, 6 tenants, backups) vit sur le même compte cPanel : SPOF sans copie hors site ni chiffrement — `app/Console/Commands/TenantBackup.php:88`
- Injection de commande shell via le paramètre `branch` du déploiement (formulaire Filament et webhook GitHub) — `app/Console/Commands/TenantDeploy.php:155`
- `tenant:update-stats` (horaire) écrase `current_storage_mb` avec 0 et annule l'ingestion nocturne : quota stockage, sonde `disk_space` et alertes stockage sont morts — `app/Services/TenantConnectionManager.php:156`
- `tenant:discover` (bouton Filament) importe une école existante en plan Free/50 étudiants et statut `active` : le paywall bloque l'école à la prochaine mise à jour des stats — `app/Console/Commands/TenantDiscover.php:126`

**Forces**

- Déploiement journalisé étape par étape en JSON persisté (`tenant_deployments.deployment_log`, TenantDeploy.php:131-239) avec durées, commit/auteur/message — bonne base pour une console support.
- Détection robuste du binaire PHP CloudLinux/cPanel et de Composer (TenantDeploy.php:346-391), et remise en ligne garantie dans le `catch` (`artisan up`, :253-259).
- Mode `--dry-run` présent sur `tenant:discover`, `tenant:cleanup-backups`, `tenant:configure-env`, `tenant:rotate-logs` et `tenant:send-alerts` — réflexe sain pour un opérateur.
- Ingestion du stockage réel (`StorageIngestionService`) bien conçue : sémantique `null` = mesure échouée (jamais d'écrasement par 0), `escapeshellarg`, timeout, exit code non nul si tout le parc est ignoré (TenantUpdateStorage.php:210-216), et tests unitaires + feature dédiés.
- Scheduler centralisé dans `routes/console.php` avec `withoutOverlapping()`, horodatage avant/après dans des logs dédiés, et doc d'activation cron précise pour LWS (`SCHEDULER_ACTIVATION.md`, chemin `/opt/alt/php83`).

**Issues** : [#69](https://github.com/James10192/adminKlassci/issues/69), [#68](https://github.com/James10192/adminKlassci/issues/68), [#72](https://github.com/James10192/adminKlassci/issues/72), [#73](https://github.com/James10192/adminKlassci/issues/73), [#74](https://github.com/James10192/adminKlassci/issues/74), [#85](https://github.com/James10192/adminKlassci/issues/85)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Le nettoyage des backups expirés supprime le dossier ENTIER de sauvegardes du tenant (toutes générations confondues) | `app/Console/Commands/CleanupOldBackups.php:136` | S | confirmé — Confirmé intégralement : TenantBackup.php:101 stocke `backup_path` = storage_path('app/backups/{code}'), dossier commun à TOUTES les générations (pas de sous-dossier horodaté par run). CleanupOldBackups.php:121-141 (deleteBackupFiles) fait `is_di… |
| Critique | Un dump MySQL qui échoue est marqué `completed` (exit code de gzip, pas de mysqldump) ; les backups `full` planifiés deviennent `manual` et un retry `manual` produit un backup vide « réussi » | `app/Console/Commands/TenantBackup.php:167` | M | confirmé — Confirmé intégralement, lignes exactes. :166-176 `exec($command,...)` sur pipe mysqldump\|gzip renvoie le code de gzip (toujours 0) — un mysqldump en échec (mauvais mdp, host down) n'est jamais détecté, le .sql.gz vide/corrompu passe status=comple… |
| Critique | La base maître `klassci_master` n'est jamais sauvegardée et tout (maître, 6 tenants, backups) vit sur le même compte cPanel : SPOF sans copie hors site ni chiffrement | `app/Console/Commands/TenantBackup.php:88` | M | confirmé — Confirmé intégralement. `tenant:backup` (routes/console.php:47-53 pour boucle sur Tenant::query(), backupDatabase L157-178 via mysqldump) ne sauvegarde jamais `klassci_master` — seul `env('DB_DATABASE')` du process (=klassci_master) tourne l'app,… |
| Critique | Injection de commande shell via le paramètre `branch` du déploiement (formulaire Filament et webhook GitHub) | `app/Console/Commands/TenantDeploy.php:155` | S | confirmé — Confirmé intégralement : ligne 155-156 exactes, aucun escapeshellarg dans le fichier, validation branch sans regex (contrairement à tenant_code), TextInput Filament sans rules(), aucune Policy. Aucun garde-fou trouvé. Injection shell réelle via w… |
| Critique | `tenant:update-stats` (horaire) écrase `current_storage_mb` avec 0 et annule l'ingestion nocturne : quota stockage, sonde `disk_space` et alertes stockage sont morts | `app/Services/TenantConnectionManager.php:156` | S | confirmé — Confirmé intégralement : TenantConnectionManager::getTenantStats() retourne toujours current_storage_mb=0 (TODO non implémenté), et TenantUpdateStats::updateTenantStats() fait $tenant->update($stats) sans exclure cette clé — aucun garde-fou (fill… |
| Critique | `tenant:discover` (bouton Filament) importe une école existante en plan Free/50 étudiants et statut `active` : le paywall bloque l'école à la prochaine mise à jour des stats | `app/Console/Commands/TenantDiscover.php:126` | S | confirmé — Chaîne intégralement confirmée : TenantDiscover.php:126-133 crée bien status=active/plan=free/max_students=50/max_inscriptions_per_year=50 (léger décalage : le bloc commence exactement en 126, pas 126-133 approximatif — les clés citées sont toute… |
| Majeur | L'étape « backup » du déploiement est toujours notée `ok`, et le bouton Déployer de la fiche tenant force `--skip-backup` | `app/Console/Commands/TenantDeploy.php:138` | S |  |
| Majeur | Déploiement déclaré `success` alors que les migrations sont partiellement appliquées ; `git pull` non fast-forward ; aucun smoke test après `artisan up` | `app/Console/Commands/TenantDeploy.php:309` | M |  |
| Majeur | Aucun verrou de déploiement concurrent et jobs `Artisan::queue` sans worker : trois chemins d'entrée peuvent se chevaucher ou ne jamais s'exécuter | `app/Http/Controllers/API/DeployWebhookController.php:74` | M |  |
| Majeur | Sonde `application_errors` et rotation des logs visent `laravel.log` alors que les tenants loguent en `daily` : sonde structurellement « degraded », rotation inopérante, fenêtre de 500 lignes | `app/Console/Commands/TenantHealthCheck.php:297` | S |  |
| Majeur | Alerting : une panne HTTP/DB n'est remontée qu'à 09:00 le lendemain et seulement si elle dure encore ; aucune déduplication ; « tenant inactif » est calculé sur `updated_at` | `app/Console/Commands/SendTenantAlerts.php:158` | M |  |
| Majeur | `env('PRODUCTION_PATH')` lu à l'exécution dans 8 commandes : nul dès que `config:cache` est activé, ce que la doc de déploiement prescrit | `app/Console/Commands/TenantHealthCheck.php:201` | S |  |
| Majeur | Un processus tué entre `artisan down` et `artisan up` laisse le tenant hors ligne : actions longues exécutées en synchrone dans la requête HTTP et mutex `withoutOverlapping` de 24 h | `app/Filament/Resources/TenantResource/Pages/ViewTenant.php:184` | M |  |
| Majeur | Tables de télémétrie sans purge : `tenant_health_checks` croît de 144 lignes/tenant/jour, `deployment_log` embarque la sortie complète de Composer | `app/Console/Commands/TenantHealthCheck.php:108` | S |  |
| Mineur | Backups : dump sans `--single-transaction`, nettoyage limité aux tenants `active`, aucune vérification base↔disque | `app/Console/Commands/TenantBackup.php:167` | S |  |
| Mineur | Secret de maintenance codé en dur et `composer install --ignore-platform-reqs` masquant les incompatibilités PHP | `app/Console/Commands/TenantDeploy.php:146` | S |  |
| Mineur | Runbooks et documentation d'exploitation incohérents avec le code : pas de procédure de restauration ni d'incident, scheduler auto-journalisé sans rotation | `routes/console.php:26` | M |  |

</details>

### Modèle de données, plans, limites, facturation (16 constats, 4 critiques)

> Le schéma klassci_master est correctement posé au niveau structurel (FK constrained, soft deletes sur tenants/invoices/groups, unicité code/subdomain/api_token, table subscription_plans normalisée avec une grille Signature 2026 riche), mais la couche « plans → limites → paywall » est aujourd'hui incohérente au point d'être dangereuse en production : la grille Signature fixe max_users=5 alors que le master compte les utilisateurs comme le personnel (34/60 autorisés), ce qui déclenche is_over_quota et un blocage total de l'application tenant dès le 6e enseignant ; la colonne ENUM tenants.plan ne connaît pas le slug « pro » et tenants.status ne connaît ni « inactive » (proposé dans Filament) ni « provisioning » (écrit par tenant:provision), donc les écritures échouent ou tronquent. Le stockage n'est jamais réellement plafonné (écrasé à 0 toutes les heures), les credentials DB des 6 tenants sont stockés en clair malgré le commentaire « cryptés » et loggés en debug, et la facturation n'existe que sous forme de table : aucune génération de facture, aucune relance, aucun paiement mobile money, aucune UI, un scope overdue bugué. Le contrat /limits est unilatéral : blocked_features et status sont ignorés par le PaywallMiddleware tenant, les entitlements (features du plan, tenant_features) ne sont jamais transmis, et les modifications de plan/limites via Filament ne laissent aucune trace d'audit. Aucun test ne couvre le contrôleur limits, le modèle Tenant ou Invoice, et la CI n'exécute que la suite Unit. En l'état, un technicien support ne peut pas « ajuster plans/features » sans risquer un blocage client ou une erreur SQL ; il faut d'abord réaligner ENUM, sémantique des compteurs, entitlements et audit avant toute ouverture de la facturation.

**Critiques confirmés**

- Grille Signature : max_users=5 vs compteur 'users = staff' → blocage total du tenant dès le 6e membre du personnel — `database/migrations/2026_04_23_232238_update_subscription_plans_to_signature_v2.php:375`
- ENUM tenants.plan/status désynchronisés : 'pro', 'inactive' et 'provisioning' n'existent pas en base — `database/migrations/2025_10_11_135529_create_tenants_table.php:35`
- database_credentials stockés en clair (cast 'array') et loggés en debug malgré le commentaire 'cryptés' — `app/Models/Tenant.php:50`
- current_storage_mb écrasé à 0 toutes les heures par tenant:update-stats → quota stockage jamais appliqué — `app/Services/TenantConnectionManager.php:156`

**Forces**

- Schéma relationnel propre : FK constrained avec cascade/nullOnDelete cohérents (invoices, tenant_features, tenant_activity_logs, subscription_plan_id nullOnDelete, group_id nullOnDelete), unicités code/subdomain/api_token/invoice_number, index sur status/plan/subscription_end_date et (tenant_id, created_at) — database/migrations/2025_10_11_135529_create_tenants_table.php:66-69, 2025_10_11_135554_create_invoices_table.php:96-116.
- Table subscription_plans normalisée et éditable via Filament (SubscriptionPlanResource) avec garde-fou anti-suppression si des tenants y sont rattachés — app/Filament/Resources/SubscriptionPlanResource.php:299-310 ; migration Signature v2 idempotente (hasColumn, upsert par slug) et réversible — 2026_04_23_232238:336-356, 468-527.
- Modèle Tenant avec helpers lisibles (daysRemaining, isOverLimit, isOverQuota, latestOfMany pour les health checks) et casts explicites — app/Models/Tenant.php:88-104, 180-222.
- Portail groupe : lecture des factures impayées en une requête groupée avec seuils configurables par env (config/group_portal.php:113-114) et test dédié (tests/Feature/Group/UnpaidInvoicesAlertTest.php).
- Discipline de correction des ENUM par ALTER brut déjà connue (2026_03_03_200000_add_success_status..., 2026_04_22_224454_add_dga...) — le pattern existe, il suffit de l'appliquer à tenants.plan/status.

**Issues** : [#71](https://github.com/James10192/adminKlassci/issues/71), [#70](https://github.com/James10192/adminKlassci/issues/70), [#72](https://github.com/James10192/adminKlassci/issues/72), [#77](https://github.com/James10192/adminKlassci/issues/77), [#63](https://github.com/James10192/adminKlassci/issues/63), [#79](https://github.com/James10192/adminKlassci/issues/79)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Grille Signature : max_users=5 vs compteur 'users = staff' → blocage total du tenant dès le 6e membre du personnel | `database/migrations/2026_04_23_232238_update_subscription_plans_to_signature_v2.php:75` | S | confirmé — Confirmé intégralement : migration l.75-76 (Essentiel: max_users=5/max_staff=34) et l.99-100 (PRO: max_users=5/max_staff=60) — pas l.375-399 (fichier ne fait que 228 lignes, corriger la citation). TenantConnectionManager.php:166-167 fixe bien cur… |
| Critique | ENUM tenants.plan/status désynchronisés : 'pro', 'inactive' et 'provisioning' n'existent pas en base | `database/migrations/2025_10_11_135529_create_tenants_table.php:35` | S | confirmé — Confirmé intégralement. Ligne exacte pour status l.32 (pas 35, 35=plan) : create_tenants_table.php l.32 `enum('status',['active','suspended','maintenance','cancelled'])`, l.35 `enum('plan',['free','essentiel','professional','elite'])`. Aucune mig… |
| Critique | database_credentials stockés en clair (cast 'array') et loggés en debug malgré le commentaire 'cryptés' | `app/Models/Tenant.php:50` | M | confirmé — Confirmé intégralement : Tenant.php:50 cast 'array' (pas 'encrypted:array'), aucun $hidden (grep vide), migration l.24 promet "cryptés" sans chiffrement réel, TenantConnectionManager.php:27-32 loggue host/username/password en clair via Log::debug… |
| Critique | current_storage_mb écrasé à 0 toutes les heures par tenant:update-stats → quota stockage jamais appliqué | `app/Services/TenantConnectionManager.php:156` | S | confirmé — Confirmé intégralement : TenantConnectionManager.php:156 fixe current_storage_mb=0 (TODO non fait) ; TenantUpdateStats.php:108 persiste ce 0 sans filtrer la clé ; scheduler lance update-stats hourly sans garde, tandis que le seul writer réel (Ten… |
| Majeur | Contrat /limits unilatéral : blocked_features et status ne sont jamais consommés, is_over_quota = blocage global | `app/Http/Controllers/API/TenantLimitsController.php:39` | M |  |
| Majeur | Calcul d'urgence faux pour les tenants sans date de fin (null <= 0) et seuils >= / > contradictoires | `app/Http/Controllers/API/TenantLimitsController.php:86` | S |  |
| Majeur | Limites dupliquées tenants.max_* vs subscription_plans sans synchronisation ; plans désactivés toujours référencés ; grille hardcodée dans tenant:provision | `app/Filament/Resources/TenantResource.php:224` | M |  |
| Majeur | Facturation : une table sans moteur — aucune génération, relance, paiement, mobile money ni UI ; scope overdue bugué | `app/Models/Invoice.php:72` | L |  |
| Majeur | Entitlements jamais transmis : features du plan et tenant_features invisibles pour le tenant, qui gère ses propres 'paywall_features' locales | `app/Http/Controllers/API/TenantLimitsController.php:75` | M |  |
| Majeur | Aucun audit trail sur les changements de plan, limites, statut ou dates via Filament ; performed_by_user_id sans FK ; logs supprimés en cascade | `app/Filament/Resources/TenantResource/Pages/EditTenant.php:13` | S |  |
| Majeur | Aucun test sur le contrat /limits, le modèle Tenant/Invoice ou les plans ; la CI n'exécute que la suite Unit | `.github/workflows/tests.yml:53` | M |  |
| Majeur | Résilience de l'API limits : aucun throttle et réponse null non mise en cache → tempête d'appels à 10 s de timeout quand le master tombe | `routes/api.php:15` | S |  |
| Mineur | KPI 'MRR' du dashboard = somme de tenants.monthly_fee, incohérent avec le modèle annuel Signature et courbe explicitement fictive | `app/Filament/Widgets/StatsOverviewWidget.php:26` | S |  |
| Mineur | Widgets, filtres et badges de plan hardcodés sur l'ancienne grille (free/essentiel/professional/elite) — 'pro' invisible | `app/Filament/Widgets/TenantsByPlanChart.php:22` | S |  |
| Mineur | Colonnes promises mais absentes (grandfathered_until_date, storage_measured_at, trial) et valeur de statut fantôme 'deleted' | `app/Http/Controllers/API/TenantLimitsController.php:22` | S |  |
| Mineur | Index et cycles de vie secondaires : invoices sans index (status, due_date), group_members sans soft delete, logs sans index action | `database/migrations/2025_10_11_135554_create_invoices_table.php:115` | S |  |

</details>

### Portail groupe DG/DGA (17 constats, 3 critiques)

> Le portail groupe est architecturalement mûr : Period value objects, providers KPI/Financials découplés, aggregator parallèle avec isolation d'erreur, dispatcher d'alertes avec préférences, dedup, rôles et bounces, feature flags partout, 45 fichiers de tests. Mais les chiffres qu'il affiche au DG ne sont pas fiables : assiduité à 0 % sur tout tenant dont la colonne s'appelle `statut` (exception avalée), encaissements calculés sur l'année civile au lieu de l'année universitaire, avoirs et paiements supprimés comptés comme recettes, revenus attendus LMD retombant sur le montant par défaut, charge enseignante additionnée sur tous les emplois du temps de l'année. Le pire est que ces erreurs sont silencieuses : un tenant en panne est mis en cache 5 min avec `error=true`, le hero annonce « synchro il y a moins de 15 min » et les widgets affichent des zéros propres. Côté exploitation, toute la chaîne email (invitations, alertes critiques, digests) est `ShouldQueue` sur une queue `database` sans table `jobs` ni worker documenté : le technicien voit « Invitation envoyée » alors que rien ne part, et le journal de dedup est écrit avant l'envoi. Les credentials DB des tenants sont loggés en clair à chaque calcul de KPI. Deux pipelines d'alertes coexistent (notifications DB non planifiées vs emails), l'acquittement est une session de 4h qui n'influence pas les emails, et le SSO n'est utilisé que sur les cards du dashboard. L'UX Filament admin est correcte pour un technicien (actions Deploy/Health/Token/Backup avec confirmations, terminal live sur HealthDashboard) mais la CI n'exécute que la suite Unit, laissant 21 tests Feature du portail hors CI. Priorités : corriger la sémantique métier des KPI avec des tests sur schéma tenant réel, rendre les erreurs visibles, fiabiliser l'envoi d'emails, puis unifier alertes/acquittement/SSO.

**Critiques confirmés**

- Credentials DB des tenants écrits en clair dans laravel.log à chaque calcul de KPI — `app/Services/TenantConnectionManager.php:495`
- Emails du portail (invitations, alertes critiques, digests) jamais envoyés : mailables ShouldQueue sur queue `database` sans table jobs ni worker — `app/Services/Group/AlertNotificationDispatcher.php:105`
- KPI « Assiduité » affiche 0 % : colonne `status` inexistante sur les tenants (`statut`) et exception avalée — `app/Services/Group/GroupKpiProvider.php:163`

**Forces**

- Architecture propre et testable : PeriodInterface/PeriodFactory/PeriodType (app/Support/Period), interfaces GroupKpiProviderInterface / GroupFinancialsProviderInterface bindées dans GroupServiceProvider, TenantBillingContext scoped pour la mémoïsation
- TenantAggregator (app/Services/Group/TenantAggregator.php) : fan-out parallèle avec isolation d'erreur par tenant, fallback sync automatique, résolution container par sous-process
- Pipeline notifications complet : préférences par membre (email_enabled, immediate_critical, digest_time, dedup_hours, disabled_alert_types), matrice rôle→alerte (AlertRoleMatcher), fingerprint stable (AlertFingerprintGenerator), lien de désabonnement signé, BounceTracker avec seuil configurable
- Feature flags systématiques (config/group_portal.php) avec commentaires d'exploitation et valeurs par défaut sûres (OFF) pour le sélecteur de période, les notifications, le bounce auto-disable et l'invitation
- Flux d'invitation sérieux : mot de passe temporaire via Str::password, token stocké en sha256, URL signée à TTL, middleware EnsurePasswordChanged, login email OU username (GroupLogin)

**Issues** : [#76](https://github.com/James10192/adminKlassci/issues/76), [#75](https://github.com/James10192/adminKlassci/issues/75), [#84](https://github.com/James10192/adminKlassci/issues/84), [#67](https://github.com/James10192/adminKlassci/issues/67), [#64](https://github.com/James10192/adminKlassci/issues/64)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Credentials DB des tenants écrits en clair dans laravel.log à chaque calcul de KPI | `app/Services/TenantConnectionManager.php:27` | S | confirmé — Confirmé intégralement : Log::debug avec 'credentials_value' => $credentials (host/username/password en clair) est bien présent, mais aux lignes 27-29, pas 495 (décalage de position, même fichier/méthode createConnection()). Aucun garde-fou trouv… |
| Critique | Emails du portail (invitations, alertes critiques, digests) jamais envoyés : mailables ShouldQueue sur queue `database` sans table jobs ni worker | `app/Services/Group/AlertNotificationDispatcher.php:105` | M | confirmé — Confirmé intégralement, aucune ligne décalée (l.105-106 dispatcher, l.19 AbstractGroupAlertMail, l.20 GroupMemberInvitationMail, l.57 InvitationService, l.119-125 MembersRelationManager tous vérifiés exacts). Point technique clé validé : Laravel … |
| Critique | KPI « Assiduité » affiche 0 % : colonne `status` inexistante sur les tenants (`statut`) et exception avalée | `app/Services/Group/GroupKpiProvider.php:169` | S | confirmé, majeur — Confirmé : requête SQL brute sur `status` sans détection du schéma tenant (statut vs status), catch générique (l.169-171, pas 172-174) qui avale l'exception SANS aucun log (contrairement au catch parent computeTenantKpis qui lui logge bie… |
| Majeur | Encaissements et taux de recouvrement faux : fenêtre année civile, avoirs comptés comme recettes, paiements supprimés inclus | `app/Services/Group/GroupKpiProvider.php:109` | M |  |
| Majeur | Erreurs d'agrégation invisibles : tenant en panne mis en cache avec des zéros, hero annonce « synchro < 15 min » | `app/Filament/Group/Pages/GroupDashboard.php:56` | M |  |
| Majeur | Revenus attendus faux pour les inscriptions LMD et périmètre incohérent (statut `validée` inexistant) | `app/Services/Group/TenantBillingContext.php:60` | M |  |
| Majeur | Deux pipelines d'alertes incohérents et acquittement session de 4h sans effet sur les emails | `app/Console/Commands/GroupAlertCheck.php:71` | M |  |
| Majeur | Charge enseignante surestimée : somme de tous les emplois du temps de l'année présentée comme heures hebdomadaires | `app/Services/TenantAggregationService.php:1104` | M |  |
| Majeur | KPI « Personnel » incohérent entre pages et faux pour les écoles à rôles personnalisés | `app/Services/Group/GroupKpiProvider.php:120` | S |  |
| Majeur | Agrégation cross-DB exécutée dans la requête HTTP et driver Concurrency `process` inadapté à LWS | `app/Filament/Group/Pages/GroupDashboard.php:107` | M |  |
| Majeur | SSO utilisé seulement sur les cartes du dashboard ; URL tenant hardcodée `*.klassci.com` ailleurs et aucun log SSO côté master | `app/Filament/Group/Resources/EstablishmentResource.php:183` | S |  |
| Majeur | CI n'exécute que les tests Unit : 21 tests Feature du portail et toute la logique SQL tenant hors couverture | `.github/workflows/tests.yml:53` | M |  |
| Mineur | Libellés bruts et labels d'alertes dupliqués en trois endroits (i18n FR incomplète) | `resources/views/filament/group/widgets/group-alerts.blade.php:61` | S |  |
| Mineur | Écarts au design system KLASSCI : violet dans le graphique effectifs et primaire cyan sur le panel admin | `app/Filament/Group/Widgets/EnrollmentWidget.php:33` | S |  |
| Mineur | Accessibilité et interactions : SVG sans aria-hidden, barres de progression sans rôle, acquittement en form POST avec rechargement, créneau digest :30 ignoré | `resources/views/filament/group/widgets/establishment-cards.blade.php:54` | S |  |
| Mineur | Sélecteur de période : « Encaissés ce mois » et le hero ignorent la période choisie, invalidation partielle du cache | `app/Filament/Group/Widgets/KpiOverviewWidget.php:77` | M |  |
| Mineur | Flux d'invitation : comparaison de token non constant-time, token non invalidé après activation, membres username-only bloqués sur le profil | `routes/web.php:70` | S |  |

</details>

### Produit : un SaaS opérable sans code (15 constats, 4 critiques)

> adminKlassci n'est pas encore un control-plane : c'est un CRUD Filament sur la table `tenants` auquel on a greffé des boutons qui lancent des commandes Artisan locales. Sur les dix scénarios support, seuls trois sont réellement possibles sans code depuis le panel (mettre à jour les stats, lancer un health check / backup, déployer un tenant déjà provisionné — et encore, en synchrone dans la requête HTTP). Les autres sont soit inopérants (suspendre pour impayé : le tenant ignore totalement `status` et `blocked_features`), soit cassés par une dérive de schéma (passer au plan PRO ou au statut « inactive » viole les ENUM MySQL en mode strict), soit purement absents (restaurer une sauvegarde, activer/désactiver un module, exporter/offboarder un client, rotater un secret avec période de grâce, réinitialiser un accès). Le contrat master↔tenant n'existe que sous forme d'un JSON implicite : aucune version, aucun test des deux côtés, et il a déjà dérivé (`services.master.url` vs `api_url`), la CI n'exécute que les tests unitaires du portail groupe. Le monitoring, le backup, le deploy et la configuration lisent le disque local via `env('PRODUCTION_PATH')` et un domaine `klassci.com` codé en dur : ouvrir un deuxième serveur ou un deuxième pays impose de réécrire ces six commandes. Les forces sont réelles — un portail groupe testé, un scheduler complet, l'invalidation du cache paywall après édition, la découverte automatique des tenants, un terminal de health-check en arrière-plan — mais elles ne compensent pas l'absence d'un modèle d'exécution asynchrone (la queue du webhook de déploiement n'a aucun worker), d'un journal d'audit sur les actions sensibles et d'une matrice de rôles effectivement appliquée. Pour atteindre l'objectif du fondateur, il faut d'abord formaliser le contrat (limits + features + enforcement, versionné et testé), corriger les ENUM, mettre un worker de jobs, puis construire les écrans manquants (suspension, features, restauration, offboarding, rotation) sur cette base ; un control-plane mature (type Tenancy for Laravel + Filament, ou Stancl/Spatie multitenancy avec job pipeline) ne fait rien d'autre que cela : un registre de serveurs, un pipeline de jobs observable, un contrat d'enforcement poussé au tenant, et un audit log immuable.

**Critiques confirmés**

- Suspendre un tenant pour impayé n'a aucun effet : le tenant ignore `status`, `plan` et `blocked_features` renvoyés par /limits — `app/Http/Controllers/API/TenantLimitsController.php:79`
- Changer de plan (PRO) ou de statut (« inactive ») depuis le panel viole les ENUM MySQL en mode strict → erreur SQL — `app/Filament/Resources/TenantResource.php:227`
- Aucune restauration de sauvegarde ; backups stockés sur le même hôte que les tenants ; mot de passe MySQL exposé dans la ligne de commande — `app/Console/Commands/TenantBackup.php:88`
- Pas de modèle d'exécution asynchrone : le webhook de déploiement met en file une queue sans worker, le bouton Déployer tourne dans la requête HTTP — `app/Http/Controllers/API/DeployWebhookController.php:74`

**Forces**

- Scheduler complet et journalisé (routes/console.php:40-172) : health-check horaire, backups DB quotidiens + full hebdo, cleanup 30 j, stats horaires, alertes quotidiennes, rotation logs, digests groupe — avec `withoutOverlapping`, `runInBackground` et log horodaté par tâche.
- Invalidation immédiate du cache paywall du tenant après sauvegarde du tenant dans le panel (ViewTenant.php:61-74 → `tenant:clear-limits-cache`), ce qui rend un changement de dates/limites effectif sans attendre les 5 min de TTL.
- HealthDashboard exécute `tenant:health-check --all` réellement détaché (proc_open + nohup, HealthDashboard.php:174-190) avec terminal poll : le bon pattern pour LWS/LiteSpeed, à généraliser aux autres actions longues.
- Découverte automatique des tenants présents sur disque mais absents en base (`tenant:discover`, bouton « Actualiser les tenants » ListTenants.php:22-40) : réduit le drift disque↔DB.
- Auto-application du token API dans le .env du tenant avec `config:clear` (`tenant:configure-env`, TenantConfigureEnv.php:64-98) et affichage du bloc .env de repli si l'écriture échoue.

**Issues** : [#77](https://github.com/James10192/adminKlassci/issues/77), [#70](https://github.com/James10192/adminKlassci/issues/70), [#69](https://github.com/James10192/adminKlassci/issues/69), [#74](https://github.com/James10192/adminKlassci/issues/74), [#83](https://github.com/James10192/adminKlassci/issues/83), [#80](https://github.com/James10192/adminKlassci/issues/80), [#81](https://github.com/James10192/adminKlassci/issues/81), [#82](https://github.com/James10192/adminKlassci/issues/82), [#62](https://github.com/James10192/adminKlassci/issues/62), [#60](https://github.com/James10192/adminKlassci/issues/60)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Suspendre un tenant pour impayé n'a aucun effet : le tenant ignore `status`, `plan` et `blocked_features` renvoyés par /limits | `app/Http/Controllers/API/TenantLimitsController.php:79` | M | confirmé — Entièrement confirmé, aucune ligne décalée. TenantLimitsController.php:78-79 renvoie bien 'plan' et 'status' ($tenant->status), et 'blocked_features' est construit l.40-65 (retourné l.125 dans le payload JSON). Côté KLASSCIv2, PaywallMiddleware::… |
| Critique | Changer de plan (PRO) ou de statut (« inactive ») depuis le panel viole les ENUM MySQL en mode strict → erreur SQL | `app/Filament/Resources/TenantResource.php:228` | S | confirmé — Confirmé intégralement. `$set('plan', $plan->slug)` est ligne 228 (pas 227). Aucune migration n'altère l'enum plan (`free\|essentiel\|professional\|elite`) ni status (`active\|suspended\|maintenance\|cancelled`) après la création table — grep exhaustif… |
| Critique | Aucune restauration de sauvegarde ; backups stockés sur le même hôte que les tenants ; mot de passe MySQL exposé dans la ligne de commande | `app/Console/Commands/TenantBackup.php:88` | L | confirmé — Confirmé intégralement. l.88 backup_path=storage_path('app/backups/'.code) : dump stocké sur le même hôte que l'app (pas d'offload S3/distant). l.166-176 (réel: mysqldump -p%s dans un sprintf exec()) : mot de passe en clair dans l'argument shell,… |
| Critique | Pas de modèle d'exécution asynchrone : le webhook de déploiement met en file une queue sans worker, le bouton Déployer tourne dans la requête HTTP | `app/Http/Controllers/API/DeployWebhookController.php:74` | M | confirmé — Tout confirmé littéralement : DeployWebhookController.php:74 `Artisan::queue('tenant:deploy', $args)` + réponse 202 "mis en file d'attente" — mais routes/console.php (schedule complet, 10+ tâches) ne contient aucun `queue:work`/Horizon/Supervisor… |
| Majeur | Activer/désactiver un module ou une feature depuis le master est impossible : `tenant_features` et `subscription_plans.features` ne sont ni exposés ni consommés | `app/Models/Tenant.php:195` | M |  |
| Majeur | La page « Rôles & Permissions » écrit en SQL direct dans la base du tenant et purge un cache Spatie qui n'existe pas (le tenant utilise le driver `file`) | `app/Filament/Pages/TenantConfig/RolePermissionPage.php:157` | M |  |
| Majeur | Contrat master↔tenant non versionné, non testé, et déjà dérivé (`services.master.url` vs `api_url`) ; la CI n'exécute que les tests unitaires du portail groupe | `routes/api.php:15` | S |  |
| Majeur | Rotation des secrets non industrialisée : token en clair dans la base, l'UI et les notifications ; ancien token révoqué instantanément ; credentials DB écrits dans les logs | `app/Filament/Resources/TenantResource/Pages/ViewTenant.php:231` | M |  |
| Majeur | Aucun journal d'audit sur les actions sensibles du panel et matrice de rôles jamais appliquée (support/billing peuvent tout faire) | `app/Models/SaasAdmin.php:97` | M |  |
| Majeur | Ouvrir un 2e serveur ou un 2e pays est impossible : chemin de production unique via `env()`, domaine `klassci.com` et FCFA codés en dur, aucune notion de serveur/région sur le tenant | `app/Console/Commands/TenantHealthCheck.php:201` | L |  |
| Majeur | Onboarding depuis le panel : « Créer un établissement » n'insère qu'une ligne en base — aucun provisioning, aucun compte admin, aucun email d'accueil, aucun garde-fou | `app/Filament/Resources/TenantResource/Pages/CreateTenant.php:17` | L |  |
| Majeur | Offboarding / export des données d'un client qui part : inexistant — la suppression est un soft-delete de la ligne master qui laisse DB, dossier, token et SSO actifs | `app/Filament/Resources/TenantResource.php:560` | M |  |
| Majeur | Diagnostic d'un tenant lent ou en erreur : le monitoring lit le disque local du master, `queue_workers` est un stub, aucune latence historisée ni consultation de logs depuis le panel | `app/Console/Commands/TenantHealthCheck.php:461` | M |  |
| Majeur | Réinitialiser un accès (superAdmin d'une école verrouillé, 2FA perdu, code d'urgence) n'existe qu'à l'intérieur du tenant et exige le rôle serviceTechnique local | `app/Console/Commands/ResetGroupMemberPassword.php:19` | M |  |
| Mineur | Les quotas (`current_staff`, `current_users`) sont calculés sur une liste de rôles codée en dur, incompatible avec les rôles récents et les rôles custom | `app/Services/TenantConnectionManager.php:111` | S |  |

</details>

## MailPulse par dimension

### Sécurité, isolation multi-organisation, conformité (18 constats, 4 critiques)

> MailPulse est à deux vitesses. La couche « external applications » (KEK AES-256-GCM, signatures HMAC versionnées avec horodatage, épinglage DNS anti-SSRF, résolution Meta fail-closed, rotation Baileys avec grâce, 22 fichiers de tests) et l'API v1 sont d'un niveau production, systématiquement scopés par organizationId. En revanche, le cœur du dashboard (server actions Next.js) n'a jamais eu de revue d'isolation : au moins huit actions sont exécutables sans session ou sans filtre d'organisation (suppression de tags de toutes les organisations par nom, suppression/lecture de segments, réécriture des workflows d'automation, retrait de tags de contacts), et la planification de campagne permet d'usurper l'expéditeur vérifié d'un autre tenant. Le rôle « admin » d'une organisation confère l'accès à la console plateforme (tous les utilisateurs, organisations et paiements), et Convex est un backend temps réel entièrement sans authentification, lisible et inscriptible par quiconque connaît un organizationId (exposé comme tenant_id dans les webhooks). Sur la conformité, la promesse « consentement, désinscription, données personnelles Afrique francophone » n'a aucun support technique : pas de champ de preuve de consentement, pas de double opt-in, réabonnement silencieux via les pages de capture, désinscription par simple GET, aucune documentation, un seul usage du journal d'audit. Aucun secret n'est présent dans le dépôt ni dans l'historique git, ce qui est une bonne base. Avant tout client externe supplémentaire, il faut fermer les IDOR des server actions, retirer l'équivalence admin d'org = admin plateforme, authentifier Convex, et poser le socle de consentement ; sans cela, le produit n'est ni étanche ni conforme, même s'il est bien protégé sur ses interfaces machine-à-machine.

**Critiques confirmés**

- Server actions sans authentification ni scoping organisation (IDOR cross-tenant destructif) — `src/app/(dashboard)/dashboard/tags/actions.ts:65`
- Le rôle « admin » d'une organisation donne accès à la console plateforme (tous tenants) — `src/lib/queries/get-current-context.ts:71`
- Backend temps réel Convex totalement non authentifié : lecture/écriture cross-tenant par organizationId — `convex/dashboard.ts:5`
- Planification de campagne : expéditeur vérifié d'un autre tenant utilisable (spoofing de domaine) — `src/app/(dashboard)/dashboard/campaigns/actions.ts:139`

**Forces**

- Module external-applications exemplaire : chiffrement AES-256-GCM sous KEK (src/lib/external-applications/crypto.ts:7-29), signatures HMAC versionnées avec fenêtre 5 min et comparaison constant-time (signatures.ts:9-33), idempotency par opération, limites de taille de corps (meta-webhook.ts:34).
- Anti-SSRF sérieux sur les callbacks vers les applications externes : HTTPS obligatoire, refus des IP privées/link-local/CGNAT, épinglage de l'adresse résolue et deadline absolue (src/lib/external-applications/network.ts:70-105).
- Résolution Meta fail-closed : un webhook n'est accepté que si exactement un compte vérifie le HMAC (application.ts:66-97) ; Baileys rooté sur l'id opaque d'application, pas sur le nom d'instance (application.ts:172-191).
- Webhooks Resend vérifiés via svix et traités en transaction Serializable (src/app/api/webhooks/email/route.ts:70-95) ; jeton Orange comparé en constant-time (sms/orange/route.ts:15-23).
- API v1 : toutes les routes relues filtrent par auth.organizationId (contacts, messages, templates, conversations, webhooks) ; clés stockées hashées SHA-256 avec aperçu (api-keys.ts:12-18) ; gating de plan sur api_access.

**Issues** : [#2](https://github.com/James10192/mailpulse/issues/2), [#4](https://github.com/James10192/mailpulse/issues/4), [#5](https://github.com/James10192/mailpulse/issues/5), [#9](https://github.com/James10192/mailpulse/issues/9), [#10](https://github.com/James10192/mailpulse/issues/10), [#11](https://github.com/James10192/mailpulse/issues/11), [#12](https://github.com/James10192/mailpulse/issues/12), [#13](https://github.com/James10192/mailpulse/issues/13), [#19](https://github.com/James10192/mailpulse/issues/19), [#20](https://github.com/James10192/mailpulse/issues/20)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Server actions sans authentification ni scoping organisation (IDOR cross-tenant destructif) | `src/app/(dashboard)/dashboard/tags/actions.ts:65` | M | confirmé — Toutes les preuves vérifiées ligne par ligne et exactes : tags/actions.ts:65 deleteTag (deleteMany sans where organizationId, aucun appel getCurrentUserAndOrg) ; segments/actions.ts:116 deleteSegment (delete by id brut) et :98 resolveSegmentConta… |
| Critique | Le rôle « admin » d'une organisation donne accès à la console plateforme (tous tenants) | `src/lib/queries/get-current-context.ts:71` | S | confirmé — Tout confirmé tel quel : ligne 71 exacte (isAdmin = role admin OU allowlist env), admin/page.tsx:94-96 protège bien par isAdmin puis fait findMany sur users(l.109)/organizations(l.122)/feedback(l.138)/billingPayment(l.143) — lignes exactes. auth.… |
| Critique | Backend temps réel Convex totalement non authentifié : lecture/écriture cross-tenant par organizationId | `convex/dashboard.ts:5` | L | confirmé — Vérifié : getStats l.5 sans ctx.auth, args.organizationId contrôle uniquement l'index, aucune vérif d'identité. grep ctx.auth\|getUserIdentity sur convex/*.ts = 0 résultat confirmé (aucun garde-fou nulle part dans le backend Convex). Pas de policy… |
| Critique | Planification de campagne : expéditeur vérifié d'un autre tenant utilisable (spoofing de domaine) | `src/app/(dashboard)/dashboard/campaigns/actions.ts:139` | S | confirmé — Confirmé : findUnique sans organizationId (l.138-139), copié dans fromName/fromEmail (l.152-153) puis utilisé par le cron. Aucun garde-fou trouvé (pas de policy/middleware/contrainte DB scoping EmailSender). Ligne exacte correcte. |
| Majeur | Webhooks sortants v1 : SSRF, http:// accepté, secret en clair, pas de timeout ni de retry | `src/lib/mailpulse/webhooks.ts:98` | M |  |
| Majeur | L'organisation « courante » est toujours la première adhésion : organisation active ignorée | `src/lib/queries/get-current-context.ts:43` | M |  |
| Majeur | Inscription sans vérification d'email combinée à l'account linking Google/GitHub (pré-hijack de compte) | `src/lib/auth.ts:28` | M |  |
| Majeur | Upload R2 : SVG accepté, type MIME déclaré par le client, clé non scopée, aucune limite de volume | `src/app/api/upload/route.ts:23` | S |  |
| Majeur | Consentement et désinscription non conformes : aucune preuve de consentement, désinscription par GET, réabonnement silencieux | `src/app/capture/[slug]/actions.ts:52` | M |  |
| Majeur | Clés API v1 sans scopes, sans expiration, environnement TEST non isolé, clé FILON = accès complet | `src/lib/mailpulse/api-keys.ts:28` | M |  |
| Majeur | Redirection ouverte via le tracking de clics et absence de séparation de domaine entre tokens tracking/désinscription | `src/app/api/track/click/route.ts:64` | S |  |
| Majeur | Paystack : montant/devise non vérifiés, upgrade permanent sans échéance, dépendance au retour navigateur | `src/app/api/billing/paystack/verify/route.ts:35` | M |  |
| Mineur | Cron Filon ouvert si CRON_SECRET est absent et comparaisons de secrets non constant-time | `src/app/api/cron/filon-recoveries/route.ts:8` | S |  |
| Mineur | Idempotency-Key : rejeu accepté sans comparer le corps de la requête | `src/lib/mailpulse/idempotency.ts:9` | S |  |
| Mineur | Emails personnels et URL Vercel codés en dur, formulaire de contact sans limite | `src/app/api/contact/route.ts:8` | S |  |
| Mineur | Relances de recouvrement Filon envoyées sans respect du STOP/opt-out et messages d'erreur internes renvoyés | `src/lib/filon-recovery/runner.ts:153` | S |  |
| Mineur | Jeton d'accès Meta legacy stocké en clair sur Organization, contrairement au module chiffré | `prisma/schema.prisma:127` | M |  |
| Mineur | Journal d'audit quasi inexistant : un seul usage d'AuditLog dans tout le produit | `src/app/(dashboard)/dashboard/sms/reconciliation-actions.ts:89` | M |  |

</details>

### Architecture et maintenabilité (15 constats, 2 critiques)

> MailPulse est un monolithe Next.js de ~43 600 lignes TypeScript strict, sans `any`, avec des îlots de très bonne ingénierie (file SMS avec bail et claim, idempotence de l'API v1, transactions sérialisables sur les webhooks, préflights de déploiement). Mais l'architecture n'a pas suivi le pivot du produit : le « moteur de notifications multicanal » a été greffé sur un outil d'email marketing, et il en résulte cinq chemins d'envoi indépendants (cron, action campagne, API v1, commandes externes, Filon) dont deux contournent le registre `communication_message`, et un cron d'envoi planifié qui rejoue tout le pipeline sans claim atomique ni idempotence. Convex est devenu une seconde source de vérité non authentifiée et en partie morte (table miroir écrite jamais lue, compteurs jamais réconciliés, composant orphelin), ce qui contredit la promesse « multi-organisation étanche ». Le découpage `src/lib` (mailpulse / whatsapp / whatsapp-baileys / external-applications / email / plans×3) ne reflète pas des frontières métier mais l'ordre chronologique des chantiers, et 481 lignes de logique d'envoi vivent sous `src/app/`. La stratégie de tests est illusoire : un tiers des fichiers de test font du `readFileSync` + regex sur le code source, la CI n'exécute qu'un seul groupe de tests, et six fichiers de test ne sont référencés par aucune commande. Pour être « opérable sans être dans le code », il faut d'abord un dispatcher unique adossé à l'outbox déjà existant, une authentification Convex (ou son retrait), une suite de tests comportementaux unifiée sur le Postgres que la CI provisionne déjà, et une documentation d'ingénierie remise en cohérence avec ce que fait réellement le produit.

**Critiques confirmés**

- Cron send-scheduled : pipeline d'envoi dupliqué, sans claim atomique ni idempotence → double envoi possible — `src/app/api/cron/send-scheduled/route.ts:94`
- Convex sans authentification : lecture/écriture inter-organisations possible avec l'URL publique — `convex/communication.ts:42`

**Forces**

- TypeScript strict réellement respecté : 0 `any`/`ts-ignore` hors tests (les 27 occurrences sont des `@ts-expect-error` d'extension `.ts` pour node:test et des `no-img-element`), 9 `as unknown as` seulement.
- Pipeline SMS exemplaire : `src/lib/sms/queue.ts` (claim par bail `SmsDispatchLease`, reprise des baux expirés l.314, deadline, inbox d'accusés `SmsDeliveryReceiptInbox`) — c'est le modèle à généraliser aux autres canaux.
- API v1 : `Idempotency-Key` obligatoire, claim committé avant dispatch (`src/app/api/v1/messages/route.ts` l.19-73), limites de plan et rate-limit appliquées avant création.
- Webhook Resend traité dans une transaction `Serializable` avec machine d'état de transition (`resend-message-status.ts` l.49-55) et reconciliation par tags.
- Erreurs fournisseur qualifiées déterministe/retryable (`EvolutionApiError.deterministic`, `whatsapp-baileys.ts` l.27-47) avec commentaire métier expliquant le choix.

**Issues** : [#6](https://github.com/James10192/mailpulse/issues/6), [#5](https://github.com/James10192/mailpulse/issues/5), [#17](https://github.com/James10192/mailpulse/issues/17), [#18](https://github.com/James10192/mailpulse/issues/18), [#10](https://github.com/James10192/mailpulse/issues/10), [#2](https://github.com/James10192/mailpulse/issues/2)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Cron send-scheduled : pipeline d'envoi dupliqué, sans claim atomique ni idempotence → double envoi possible | `src/app/api/cron/send-scheduled/route.ts:94` | M | confirmé — Entièrement confirmé par le code. route.ts:45 findMany puis route.ts:94-97 campaign.update({status:"SENDING"}) inconditionnel (pas de updateMany+where status+count comme le claim atomique de campaign-sending-helpers.ts:139-148, qui lui lève Campa… |
| Critique | Convex sans authentification : lecture/écriture inter-organisations possible avec l'URL publique | `convex/communication.ts:42` | M | confirmé — Confirmé intégralement : listRecent l.42-50 (pas de filtre d'appartenance sur organizationId client), notifications.list/markAllAsRead l.5-16/l.36-47 (userId client sans vérif), assertOrgExists (lib.ts) ne vérifie que l'existence de l'org, pas l'… |
| Majeur | Prisma vs Convex : double source de vérité non réconciliée, en partie morte | `src/lib/mailpulse/messages.ts:431` | M |  |
| Majeur | Cinq chemins d'envoi indépendants ; deux contournent le registre communication_message | `src/lib/filon-recovery/runner.ts:138` | L |  |
| Majeur | Suite de tests illusoire : tests « grep de source », CI qui n'en exécute qu'un tiers, aucun test comportemental sur routes/DB/UI | `.github/workflows/ci.yml:89` | M |  |
| Majeur | getCurrentUserAndOrg ignore l'organisation active et crée des données en lecture | `src/lib/queries/get-current-context.ts:43` | M |  |
| Majeur | Abstraction email circulaire et wrappers identité | `src/lib/resend.ts:2` | S |  |
| Majeur | Frontières src/lib chronologiques, pas métier : WhatsApp éclaté sur 3 dossiers, 3 modules « plans », logique de campagne sous src/app | `src/lib/whatsapp-baileys.ts:1` | L |  |
| Majeur | Webhooks sortants clients sans retry, doublon conceptuel avec l'outbox des callbacks externes | `src/lib/mailpulse/webhooks.ts:88` | M |  |
| Majeur | Sécurité de périmètre : expéditeur non scopé par organisation lors de la planification | `src/app/(dashboard)/dashboard/campaigns/actions.ts:139` | S |  |
| Majeur | Documentation d'ingénierie contradictoire avec le code (CLAUDE.md, AGENTS.md, .env.example) | `CLAUDE.md:7` | S |  |
| Mineur | Composants et modules monolithiques (rich-editor 796, contact-detail 767, workflow-editor 638, landing 599, meta-webhook 613) | `src/components/editor/rich-editor.tsx:212` | M |  |
| Mineur | Gestion d'erreurs par chaînes et absence de journalisation structurée | `src/types/action-state.ts:1` | M |  |
| Mineur | Historique de migrations bricolé et CI qui ne valide pas les migrations | `prisma/migrations/20260331235959_prepare_twilio_replacement_order/migration.sql:1` | S |  |
| Mineur | URL de production hardcodée à 7 endroits | `src/lib/auth.ts:20` | S |  |

</details>

### Moteurs d'envoi : email, SMS, WhatsApp, files (17 constats, 4 critiques)

> La dimension est à deux vitesses. Le rail « externe » (commandes KLASSCI/LMS, opérations de transport, outbox de callbacks) et la file SMS Orange sont sérieux : leases en base, jetons de claim, état SUBMISSION_UNKNOWN explicite, backoff exponentiel, inbox durable pour les accusés, transactions Serializable rejouées. En revanche, tout ce qui touche aux campagnes (email/WhatsApp manuelles et planifiées) reste une boucle synchrone dans une Server Action ou une route sans maxDuration : une coupure Vercel laisse la campagne verrouillée en SENDING sans reprise, et le seul chemin de sortie (annuler → brouillon → renvoyer) renvoie à tous les destinataires déjà servis. Le cron planifié n'est pas atomique (findMany puis update) et avale les erreurs. Les messages transactionnels email/WhatsApp de l'API n'ont aucun retry : un 5xx ou un timeout Resend/Meta devient un SUBMISSION_UNKNOWN définitif qui n'est jamais réclamé, et il n'existe ni dead-letter ni bouton « renvoyer ». Le SMS est de fait mono-organisation (ORANGE_SMS_OWNER_ORGANIZATION_ID) et son débit dépend d'un scheduler GitHub Actions toutes les 5 minutes, ce qui contredit la promesse de moteur de notifications multi-organisation et temps réel du groupe. Les webhooks sortants vers les applications clientes ne sont tentés qu'une fois, sans timeout. Enfin, l'observabilité se limite à console.log et catch vides, et les « tests » de livraison sont des regex sur le code source, non exécutés en CI. Sans un worker persistant par destinataire pour les campagnes, un retry borné pour l'email/WhatsApp direct, un scheduler fiable avec alerte et une supervision des états inconnus, le produit n'est pas opérable sans intervention dans le code ou la base.

**Critiques confirmés**

- Campagnes email/WhatsApp envoyées en boucle synchrone dans une Server Action : timeout Vercel = campagne bloquée en SENDING sans reprise — `src/app/(dashboard)/dashboard/campaigns/campaign-sending-helpers.ts:183`
- Annuler une campagne SENDING puis la renvoyer renvoie à tous les destinataires déjà servis (double envoi) — `src/app/(dashboard)/dashboard/campaigns/actions.ts:238`
- Cron send-scheduled : revendication non atomique, erreurs avalées, pas de maxDuration ni de reprise — `src/app/api/cron/send-scheduled/route.ts:45`
- Email/WhatsApp transactionnels : un échec transitoire (5xx, timeout, 429) devient un SUBMISSION_UNKNOWN définitif, sans retry, sans dead-letter, sans « renvoyer » — `src/lib/mailpulse/message-direct-dispatch.ts:344`

**Forces**

- File SMS Orange robuste : lease unique avec ownerToken et renouvellement (src/lib/sms/queue.ts:263-290, 380-386), claim conditionnel par processingToken (388-398), backoff exponentiel 2/4/8 min borné à 3 retries (96-105), récupération des claims expirés en SUBMISSION_UNKNOWN plutôt qu'en renvoi aveugle (314-329), séparation nette 429 (retryable) / 5xx-timeout (unknown) côté provider (src/lib/sms/orange.ts:176-186).
- Accusés de réception Orange durables : inbox SmsDeliveryReceiptInbox avec unicité organisation/provider/providerMessageId et rattachement différé si le DR arrive avant la finalisation (src/lib/sms/delivery-receipts.ts:59-104, prisma/schema.prisma:843-865).
- Idempotence API bien pensée : la revendication IdempotencyRecord est commise avant tout appel fournisseur, hash du body avec 409 en cas de réutilisation, récupération d'une claim orpheline (src/lib/mailpulse/messages.ts:66-128, 298-330 ; src/app/api/v1/messages/route.ts:19-25, 66-73).
- Rail applications externes de qualité : outbox de callbacks avec 8 tentatives, backoff exponentiel plafonné à 6 h, leases et déclenchement borné par deadline (src/lib/external-applications/meta-webhook.ts:123-183), payload chiffré et HTTPS épinglé avec timeout 10 s et taille bornée (src/lib/external-applications/callback.ts:7-8, 62-99), opérations de transport idempotentes par operationKey avec distinction accepted/rejected/unknown (src/lib/external-applications/commands.ts:29-76).
- Politique WhatsApp explicite : fenêtre 24 h appliquée uniquement à Meta en texte libre, rendu de template Baileys qui refuse toute fuite de placeholder (src/lib/external-applications/whatsapp-transport-policy.ts:33-72) ; classification Evolution « recipient unreachable » par allow-list et non par plage HTTP (src/lib/whatsapp-baileys.ts:27-46).

**Issues** : [#6](https://github.com/James10192/mailpulse/issues/6), [#7](https://github.com/James10192/mailpulse/issues/7), [#8](https://github.com/James10192/mailpulse/issues/8), [#9](https://github.com/James10192/mailpulse/issues/9), [#19](https://github.com/James10192/mailpulse/issues/19), [#18](https://github.com/James10192/mailpulse/issues/18)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Campagnes email/WhatsApp envoyées en boucle synchrone dans une Server Action : timeout Vercel = campagne bloquée en SENDING sans reprise | `src/app/(dashboard)/dashboard/campaigns/campaign-sending-helpers.ts:183` | L | confirmé — Entièrement confirmé, ligne exacte. sendEmailsToRecipients (helpers.ts:183-264) boucle séquentiellement await sendCampaignEmail + await delay(1000)/100 contacts ; sendWhatsAppToRecipients (293-356) idem sans lot. Appelé sync depuis sendCampaign (… |
| Critique | Annuler une campagne SENDING puis la renvoyer renvoie à tous les destinataires déjà servis (double envoi) | `src/app/(dashboard)/dashboard/campaigns/actions.ts:238` | S | confirmé — Confirmé intégralement par lecture du code. actions.ts:238-244 : cancelCampaign accepte SCHEDULED/SENDING et fait `status:"DRAFT", scheduledAt:null` sans toucher à CampaignRecipient. campaign-sending-helpers.ts:132-166 : initializeCampaignSending… |
| Critique | Cron send-scheduled : revendication non atomique, erreurs avalées, pas de maxDuration ni de reprise | `src/app/api/cron/send-scheduled/route.ts:45` | M | confirmé — Confirmé intégralement. L45-49 findMany sans lock, L94-97 update inconditionnel par id (pas de updateMany where status=SCHEDULED avec contrôle de count comme le prétend l'auditeur en comparaison — vérifié : aucun pattern de ce type n'existe nulle… |
| Critique | Email/WhatsApp transactionnels : un échec transitoire (5xx, timeout, 429) devient un SUBMISSION_UNKNOWN définitif, sans retry, sans dead-letter, sans « renvoyer » | `src/lib/mailpulse/message-direct-dispatch.ts:344` | M | confirmé — Tout confirmé dans le code réel. settleProviderFailure (344-358, ligne exacte) route tout ce qui n'est pas classé déterministe (matching mots-clés 321-342, aucun code HTTP 5xx/429/timeout dedans) vers status:SUBMISSION_UNKNOWN sans retry ; commen… |
| Majeur | Le SMS est mono-organisation : une seule org (ORANGE_SMS_OWNER_ORGANIZATION_ID) peut envoyer, toutes les autres échouent | `src/lib/sms/queue.ts:62` | L |  |
| Majeur | Scheduler = GitHub Actions toutes les 5 min : latence SMS transactionnelle, retards non maîtrisés, aucune alerte en cas d'arrêt | `.github/workflows/send-scheduled.yml:5` | M |  |
| Majeur | Webhooks sortants vers les applications clientes : une seule tentative, pas de timeout, pas de retry malgré la colonne nextRetryAt | `src/lib/mailpulse/webhooks.ts:316` | M |  |
| Majeur | Rate limits API codés en dur (30 SMS/min) et quota email mensuel non décompté pour les envois API | `src/lib/mailpulse/delivery-limits.ts:6` | S |  |
| Majeur | Corrélation des accusés Orange non prouvée : callbackData utilisé comme identifiant de message alors que l'envoi n'en fixe aucun | `src/app/api/webhooks/sms/orange/route.ts:36` | S |  |
| Majeur | Appels Meta Cloud API et Resend sans timeout, dans des chemins tenus par une lease ou une requête API | `src/lib/whatsapp-meta.ts:28` | S |  |
| Majeur | Repli automatique sur un second format de numéro WhatsApp après un échec ambigu : risque de double envoi, voire à un autre abonné | `src/lib/whatsapp.ts:64` | S |  |
| Majeur | Observabilité inexistante : console.log, catch vides, aucune alerte sur les états inconnus ni sur l'arrêt du scheduler | `src/app/api/webhooks/email/route.ts:61` | M |  |
| Majeur | Tests de livraison = expressions régulières sur le code source, et non exécutés en CI | `src/lib/sms/delivery-receipts.test.ts:6` | M |  |
| Mineur | Messages email de campagne sans providerMessageId ni tag message_id : jamais mis à jour par les webhooks Resend | `src/app/(dashboard)/dashboard/campaigns/campaign-sending-helpers.ts:220` | S |  |
| Mineur | IdempotencyRecord sans expiration ni purge ; webhooks Resend sans déduplication svix-id | `prisma/schema.prisma:1155` | S |  |
| Mineur | URL de base de secours codée en dur (mailpulse-two.vercel.app) pour les liens de désinscription et de tracking | `src/app/api/cron/send-scheduled/route.ts:150` | S |  |
| Mineur | Désinscription email : STOP scopé par canal mais `subscribed` inchangé, page de confirmation en anglais, aucune trace d'organisation | `src/app/api/unsubscribe/route.ts:17` | S |  |

</details>

### Modèle de données, migrations, performance (17 constats, 5 critiques)

> Le schéma Prisma est à deux vitesses. La couche récente « plateforme de communication » (CommunicationMessage, transport externe, inbox SMS, callbacks) est de très bonne facture : clés d'idempotence composites, uniques (organizationId, id) qui forcent l'étanchéité des FK, index partiels réfléchis, leases de dispatch, scripts preflight. La couche héritée « email marketing » (Contact, ContactTag, EmailEvent, CampaignRecipient, CampaignAnalytics) n'a jamais été conçue multi-organisation : ContactTag et EmailEvent n'ont pas de organizationId, et trois pages du dashboard (tags, désinscriptions, analytics) ainsi que la suppression de tag agrègent ou détruisent des données de TOUTES les organisations — c'est une fuite inter-tenant avérée, pas un risque théorique. Côté performance, la plateforme est dimensionnée pour quelques milliers de contacts : recalcul des analytics par chargement de tous les destinataires à chaque ouverture de mail, envoi de campagne synchrone en mémoire dans une fonction serverless, page contacts limitée aux 50 derniers avec recherche côté client, import CSV en un seul appel. À 100k contacts / 1M événements, les points de rupture sont concrets : pixel d'ouverture qui relit 100k lignes par hit, campagne bloquée en SENDING au timeout Vercel, compteurs email_event qui traversent la table contact faute de colonne tenant, tables sans rétention (événements avec IP/UA, idempotence avec corps de réponse) qui grossissent sans fin. Enfin la chaîne de migrations n'est pas reproductible avec certitude : la CI fait `db push` et non `migrate deploy`, les index partiels vivent uniquement en SQL, l'ordonnancement Twilio repose sur un nom de dossier hors format et des blocs DO $$ conditionnels, et le preflight d'historique révèle une base de production ayant hébergé d'autres produits (tables klassci*, parent_chatbot*). Le fondateur ne peut pas aujourd'hui confier l'exploitation à quelqu'un hors du code : il faut d'abord fermer les fuites tenant (S), puis désynchroniser l'envoi et les agrégats (M/L), puis instaurer une politique de rétention et un test de migration sur base neuve en CI (S/M).

**Critiques confirmés**

- Fuite inter-organisation : la page Tags et deleteTag opèrent sur toutes les organisations — `src/app/(dashboard)/dashboard/tags/page.tsx:6`
- Fuite inter-organisation : pages Désinscriptions et Analytics comptent les données de toute la base — `src/app/(dashboard)/dashboard/unsubscribes/page.tsx:28`
- Recalcul des analytics de campagne par chargement complet des destinataires à chaque ouverture/clic — `src/lib/campaign-analytics.ts:107`
- Envoi de campagne synchrone en mémoire dans une fonction serverless, sans file ni reprise — `src/app/api/cron/send-scheduled/route.ts:113`
- Chaîne de migrations non reproductible : CI en db push, index partiels hors schéma, ordonnancement Twilio par nom de dossier hors format, base partagée avec d'autres produits — `.github/workflows/ci.yml:80`

**Forces**

- Étanchéité tenant forcée au niveau SQL sur la couche transport : @@unique([organizationId, id]) sur CommunicationMessage (schema.prisma:824), ExternalApplication (909), ProviderAccount (958) et FK composites (organizationId, applicationId) — un enregistrement ne peut pas référencer une ressource d'une autre organisation.
- Idempotence et déduplication bien pensées : @@unique([organizationId, idempotencyKey]) (schema.prisma:817), providerMessageId scopé (organizationId, channel, provider) (823), IdempotencyRecord unique sur (org, key, method, path) (1169), inbox de reçus SMS pour absorber les callbacks Orange arrivés avant l'ID provider (843-866).
- Index partiels intentionnels documentés dans la migration transport-core (20260801120000 lignes 144, 154, 155) et verrouillés par un test (prisma/external-application-transport-schema.test.ts:14-38).
- Dispatch SMS robuste : lease SmsDispatchLease, claim atomique par updateMany conditionnel (src/lib/sms/queue.ts:389-396), batch borné SMS_BATCH_SIZE=200 (queue.ts:23), index (channel, status, nextRetryAt, queuedAt) qui colle exactement à la requête candidate (schema.prisma:825).
- Claim de campagne par updateMany conditionnel sur status DRAFT dans une transaction (campaign-sending-helpers.ts:140-152) : pas de double envoi concurrent.

**Issues** : [#3](https://github.com/James10192/mailpulse/issues/3), [#14](https://github.com/James10192/mailpulse/issues/14), [#6](https://github.com/James10192/mailpulse/issues/6), [#15](https://github.com/James10192/mailpulse/issues/15), [#16](https://github.com/James10192/mailpulse/issues/16), [#10](https://github.com/James10192/mailpulse/issues/10), [#12](https://github.com/James10192/mailpulse/issues/12), [#13](https://github.com/James10192/mailpulse/issues/13), [#5](https://github.com/James10192/mailpulse/issues/5)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Fuite inter-organisation : la page Tags et deleteTag opèrent sur toutes les organisations | `src/app/(dashboard)/dashboard/tags/page.tsx:6` | M | confirmé — Confirmé intégralement. tags/page.tsx:6-9 groupBy sans where organizationId → tous les noms de tags/effectifs de toutes les orgs affichés. tags/actions.ts:65 deleteMany({where:{name:tagName}}) supprime le tag chez toutes les organisations — et pi… |
| Critique | Fuite inter-organisation : pages Désinscriptions et Analytics comptent les données de toute la base | `src/app/(dashboard)/dashboard/unsubscribes/page.tsx:28` | M | confirmé — Confirmé : unsubscribes/page.tsx (contact.count, emailEvent.count/findMany) et email-stats.ts (getEmailEventStats, appelée par analytics/page.tsx) n'ont aucun filtre organizationId, alors que transactional/page.tsx prouve le pattern correct (scop… |
| Critique | Recalcul des analytics de campagne par chargement complet des destinataires à chaque ouverture/clic | `src/lib/campaign-analytics.ts:107` | M | confirmé — Confirmé intégralement. campaign-analytics.ts:107-128 charge bien TOUS les CampaignRecipient de la campagne (7 colonnes de dates) puis fait 7 `.filter().length` en mémoire + un upsert — aucune agrégation SQL (`count`/`groupBy`), aucun cache, aucu… |
| Critique | Envoi de campagne synchrone en mémoire dans une fonction serverless, sans file ni reprise | `src/app/api/cron/send-scheduled/route.ts:113` | L | confirmé — Confirmé intégralement : contact.findMany sans take (113-121), createMany bulk (139-142), boucle for synchrone avec delay 200ms/envoi (156-238), aucun maxDuration déclaré (seuls process-sms:3 et process-external-callbacks:5 en ont), cron GitHub A… |
| Critique | Chaîne de migrations non reproductible : CI en db push, index partiels hors schéma, ordonnancement Twilio par nom de dossier hors format, base partagée avec d'autres produits | `.github/workflows/ci.yml:80` | M | confirmé — Confirmé et même pire que décrit. Preuve empirique (rejeu réel des .sql dans l'ordre lexicographique de `migrate deploy` sur Postgres 16 propre) : la chaîne casse dur — `20260401023743_add_twilio_subaccount` lève ERROR "column smsEnabled already … |
| Majeur | Page Contacts limitée aux 50 derniers avec recherche côté client : inutilisable à 100k contacts | `src/app/(dashboard)/dashboard/contacts/page.tsx:16` | M |  |
| Majeur | Import CSV en un seul appel serveur : plafond de taille et N+1 sur les tags | `src/app/(dashboard)/dashboard/contacts/actions.ts:120` | M |  |
| Majeur | Résolution de l'organisation active ignorée : premier membership arbitraire, Member sans index ni unicité | `src/lib/queries/get-current-context.ts:61` | M |  |
| Majeur | Page Campagnes : N+1 avec filtre JSON `metadata.campaignId` non indexé pour chaque campagne WhatsApp | `src/app/(dashboard)/dashboard/campaigns/page.tsx:15` | S |  |
| Majeur | Aucune politique de rétention : EmailEvent (IP + User-Agent), IdempotencyRecord, AuditLog, WebhookDelivery croissent sans borne | `prisma/schema.prisma:401` | M |  |
| Majeur | Intégrité billing : montant/devise Paystack non comparés à la vérification, compteur mensuel d'emails réinitialisé sans atomicité | `src/app/api/billing/paystack/verify/route.ts:34` | M |  |
| Majeur | Convex duplique des agrégats Prisma (dashboardStats, campaignProgress, liveMessages) avec dérive et tables mortes | `convex/schema.ts:6` | M |  |
| Mineur | Index manquants sur des colonnes filtrées à chaque requête | `prisma/schema.prisma:183` | S |  |
| Mineur | Statuts et providers en String libre à côté d'enums : incohérence et absence de contrainte | `prisma/schema.prisma:1017` | S |  |
| Mineur | CampaignAnalytics : « uniqueOpens/uniqueClicks » sont des copies de totalOpened/totalClicked, taux en Float, pas de source événementielle | `src/lib/campaign-analytics.ts:139` | S |  |
| Mineur | R2 sans table d'assets ni nettoyage : objets orphelins, clés non scopées par organisation | `src/app/api/upload/route.ts:29` | S |  |
| Mineur | Déduplication des EmailEvent par findFirst+create sans contrainte unique | `src/app/api/webhooks/email/route.ts:172` | S |  |

</details>

### UX produit, onboarding, accessibilité (20 constats, 3 critiques)

> Le socle visuel est cohérent avec DESIGN.md (palette zinc + orange, Plus Jakarta/Space Mono chargées dans src/app/layout.tsx, tokens dans globals.css, shadcn sidebar/table/card réellement utilisés sur le dashboard, les campagnes, la facturation, la plateforme) et quelques écrans sont exemplaires (aide domaines SPF/DKIM pas-à-pas, avertissement plan lecture seule, widget avis avec aria). Mais la promesse « multi-organisation étanche » est cassée au niveau des écrans : /dashboard/unsubscribes, /dashboard/tags et /dashboard/analytics agrègent et affichent les données de toutes les organisations, et la suppression d'un tag efface ce tag chez tous les clients. La promesse « conforme consentement/désinscription » est contredite par une page de désinscription en anglais, une désinscription par lien que l'interface ne montre jamais (le contact reste « ABONNE »), des CGU/politique de confidentialité pointant vers « # » et aucune page légale. L'onboarding réel n'existe pas : le wizard de 606 lignes est orphelin, corrompu (accents remplacés par « ? »), ne persiste rien, et la checklist se coche en visitant une page. Le dashboard privilégie des KPIs développeur (API, clés, webhooks) et un sélecteur de dates décoratif, l'analytics est un placeholder permanent, la landing affiche des métriques inventées et des fonctionnalités inexistantes (churn, RFM, Mobile Money). L'accessibilité est au niveau « bonne intention » : focus ring global correct, mais 11 modales artisanales sans role/Escape/focus-trap, alert() natif, aucun toast, mode clair illisible sur plusieurs écrans, reduced-motion ignoré hors view-transitions, éditeur riche et boutons d'en-tête sans nom accessible, aucun test axe en CI. Pour être opérable par une équipe marketing non technique sans passer par le code, il faut d'abord fermer les fuites inter-organisations (1 jour), puis livrer un vrai parcours import → expéditeur → première campagne → analytics multicanal, remplacer les modales maison par les composants shadcn, corriger le français (accents, anglicismes) et rendre le mode clair et le clavier utilisables.

**Critiques confirmés**

- Page Désabonnements : statistiques et noms de campagnes de toutes les organisations — `src/app/(dashboard)/dashboard/unsubscribes/page.tsx:28`
- Page Tags : liste inter-organisations et suppression d'un tag chez tous les clients — `src/app/(dashboard)/dashboard/tags/actions.ts:63`
- Analytics : chiffres globaux plateforme, formule fausse et graphiques placeholder permanents — `src/lib/queries/email-stats.ts:4`

**Forces**

- Design system réellement appliqué : tokens zinc/orange, Plus Jakarta Sans + Space Mono, focus ring global (globals.css:169-172), composants shadcn (sidebar, table, card, alert, tabs) sur dashboard, campagnes, facturation, plateforme, admin.
- Aide délivrabilité de qualité sur /dashboard/domains : explication SPF/DKIM, sous-domaine recommandé, guide par registrar (Cloudflare/OVH/Gandi), note cPanel, interprétation des statuts (domains-client.tsx:385-497).
- Consentement respecté à l'envoi : canReceiveChannel filtre les contacts opt-out par canal dans campaign-sending-helpers.ts:107, cron send-scheduled, file SMS et dispatch direct.
- Pattern « consultation uniquement » clair pour les fonctionnalités Pro (PlanReadOnlyNotice avec Alert, badge cadenas dans la sidebar avec aria-label) plutôt qu'un blocage brutal.
- Skeleton de chargement au niveau /dashboard (loading.tsx), error boundary SMS bien écrit en français avec bouton Réessayer, breadcrumb avec nav aria-label, palette de commandes Ctrl+K en français.

**Issues** : [#3](https://github.com/James10192/mailpulse/issues/3), [#12](https://github.com/James10192/mailpulse/issues/12), [#22](https://github.com/James10192/mailpulse/issues/22), [#23](https://github.com/James10192/mailpulse/issues/23)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Page Désabonnements : statistiques et noms de campagnes de toutes les organisations | `src/app/(dashboard)/dashboard/unsubscribes/page.tsx:230` | S | confirmé — Confirmé intégralement : aucun orgId/getCurrentUserAndOrg dans le fichier, requêtes Prisma globales non scopées, pas de middleware/policy/extends Prisma faisant office de garde-fou tenant. Rendu campagne réel en l.230 (`{c.name}`), pas l.229-231 … |
| Critique | Page Tags : liste inter-organisations et suppression d'un tag chez tous les clients | `src/app/(dashboard)/dashboard/tags/actions.ts:63` | S | confirmé — Confirmé intégralement. actions.ts l.63-65 : deleteTag(tagName) fait deleteMany({where:{name:tagName}}) sans appeler getCurrentUserAndOrg (contrairement à createTag juste au-dessus qui l'appelle) — aucune borne organisationId. page.tsx l.5-10 : g… |
| Critique | Analytics : chiffres globaux plateforme, formule fausse et graphiques placeholder permanents | `src/lib/queries/email-stats.ts:4` | M | confirmé — Confirmé intégralement, aucun garde-fou trouvé. email-stats.ts l.4-8 : prisma.emailEvent.count() global sans where organizationId (le modèle EmailEvent, schema.prisma l.401-415, n'a même pas de colonne organizationId — agrégation structurellement… |
| Majeur | Désinscription par lien invisible dans l'interface : le contact reste « ABONNE » et la page Désabonnements compte 0 | `src/app/api/unsubscribe/route.ts:17` | M |  |
| Majeur | Page publique de désinscription en anglais, sans confirmation ni possibilité de revenir | `src/app/api/unsubscribe/route.ts:52` | S |  |
| Majeur | Wizard d'onboarding orphelin, corrompu et sans persistance | `src/app/(dashboard)/dashboard/onboarding/page.tsx:74` | M |  |
| Majeur | Checklist « Premiers pas » cochée par simple visite de page et stockée dans le navigateur | `src/components/dashboard/onboarding-checklist.tsx:118` | M |  |
| Majeur | Onze modales fabriquées à la main sans rôle, Escape ni focus-trap, alert() natif, aucun toast | `src/components/dashboard/confirm-dialog.tsx:27` | M |  |
| Majeur | Mode clair illisible sur plusieurs écrans (textes zinc-100/200 sur fond blanc, popover tour et canvas forcés en sombre) | `src/components/dashboard/feature-gate.tsx:66` | M |  |
| Majeur | prefers-reduced-motion ignoré hors view-transitions : landing, ping, edges animés en boucle | `src/app/globals.css:473` | S |  |
| Majeur | Landing : métriques décoratives inventées, fonctionnalités annoncées inexistantes, liens légaux morts | `src/app/page.tsx:168` | M |  |
| Majeur | Dashboard orienté développeur et sélecteur de période décoratif | `src/app/(dashboard)/dashboard/page.tsx:186` | M |  |
| Majeur | Navigation clavier et noms accessibles manquants sur les contrôles clés | `src/app/(dashboard)/dashboard/senders/senders-client.tsx:121` | M |  |
| Majeur | Français sans accents et anglicismes dans la navigation et les écrans clés | `src/app/(dashboard)/dashboard/contacts/[id]/contact-detail.tsx:294` | S |  |
| Mineur | Import CSV : en-têtes français accentués non reconnus, aucun champ consentement/source | `src/app/(dashboard)/dashboard/contacts/import/csv-import.tsx:59` | S |  |
| Mineur | Flux campagne : pas d'envoi de test, carte canal Email surlignée statiquement | `src/app/(dashboard)/dashboard/campaigns/new/page.tsx:78` | M |  |
| Mineur | Segments : filtres tags en texte libre et « score d'engagement » non expliqué | `src/app/(dashboard)/dashboard/segments/segments-client.tsx:275` | M |  |
| Mineur | Docs « Démarrage » orientées développeur et désynchronisées du code | `content/docs/demarrage.mdx:14` | S |  |
| Mineur | En-tête surchargé, navigation dupliquée et recherche absente sur mobile | `src/components/dashboard/dashboard-shell.tsx:520` | M |  |
| Mineur | Aucune frontière d'erreur ni page 404 pour le dashboard hors SMS | `src/app/(dashboard)/dashboard/sms/error.tsx:7` | S |  |

</details>

### Expérience client : brancher une application externe (KLASSCI comme client de référence) (15 constats, 2 critiques)

> Le rail « applications externes » de MailPulse est la brique la plus soignée du dépôt : HMAC versionné avec fraîcheur, secrets chiffrés AES-GCM sous KEK, machine d'état idempotente avec SUBMISSION_UNKNOWN et réconciliation par biz_opaque_callback_data, outbox de callbacks immuable avec retry exponentiel et HTTPS épinglé, et une UI Settings complète qui permet déjà de créer une application, ses credentials, son compte Meta/Baileys, ses endpoints et ses templates sans toucher au script. Mais ce rail ne couvre que WhatsApp : les notifications SMS et email de KLASSCI passent par un second rail (/api/v1/messages) authentifié par une simple clé API d'organisation, sans scope, sans attribution par application, et dont les envois WhatsApp en texte libre échouent structurellement hors fenêtre 24h sur Meta officiel (bascule SMS payante). Côté KLASSCI, la moitié du câblage reste manuelle : six variables externes et quatre secrets chatbot uniquement en .env, absents de l'UI Settings et de tenant:provision, sans commande de génération. Le modèle multi-école est ambigu : le code de scoping par tenant_code montre que plusieurs écoles peuvent partager une organisation MailPulse, et dans ce cas l'upsert de contact par email/téléphone et le STOP à l'échelle de l'organisation mélangent les données des établissements. Le LMS, lui, n'a aucune intégration MailPulse aujourd'hui (front Angular qui ne parle qu'à KLASSCI). L'observabilité par application est inexistante (aucun log, aucun compteur, callbacks FAILED silencieux) et les tests « external applications » de la CI sont des assertions sur le texte source, pas sur le comportement. Pour qu'un technicien branche une école en 10 minutes il faut : une organisation MailPulse par école (plan avec api_access), un assistant « nouvelle application » qui sort un bloc .env complet (clé API + 6 valeurs externes + callback), une page Settings KLASSCI qui accepte ces valeurs chiffrées, une commande artisan qui génère les secrets chatbot, un bouton « tester le circuit » de bout en bout, et un runbook publié dans content/docs avec la liste exhaustive des codes de rejet.

**Critiques confirmés**

- Rail commands sans limite de débit, sans quota ni contrôle de plan par application — `src/app/api/v1/external-applications/[applicationKey]/commands/route.ts:23`
- Isolation inter-écoles nulle si plusieurs tenants KLASSCI partagent une organisation : upsert contact par email/téléphone et STOP à l'échelle de l'organisation — `src/app/api/v1/contacts/route.ts:49`

**Forces**

- UI d'administration complète sur /dashboard/settings/external-applications : création d'application, rotation/révocation des credentials, compte Meta ou Baileys, endpoints de rappel, templates, jeton entrant — réservée owner/admin (guards.ts:14-23) et sans script obligatoire.
- Script scripts/provision-external-application.ts idempotent, refuse les réaffectations silencieuses de compte fournisseur (l.213-217, 236-240) et n'affiche les secrets qu'une fois (l.363-368).
- Signature HMAC versionnée v1:keyId=hex sur timestamp.body, fraîcheur 5 min / skew 30 s, comparaison en temps constant (signatures.ts:9-33) ; implémentation miroir côté KLASSCI (MailPulseClient.php:96-108, ParentChatbotInboundSignature.php:14-45).
- Secrets d'application chiffrés AES-256-GCM sous EXTERNAL_APPLICATION_KEK avec vérification de longueur de clé (crypto.ts:7-46).
- Machine d'état de commande robuste : lease, SUBMISSION_UNKNOWN jamais transformé en rejet, réconciliation par biz_opaque_callback_data (commands.ts:61-110, 206-227), conflit 409 sur payload divergent (l.46).

**Issues** : [#20](https://github.com/James10192/mailpulse/issues/20), [#21](https://github.com/James10192/mailpulse/issues/21), [#19](https://github.com/James10192/mailpulse/issues/19), [#18](https://github.com/James10192/mailpulse/issues/18), [#12](https://github.com/James10192/mailpulse/issues/12)

<details><summary>Tous les constats</summary>

| Sévérité | Constat | Preuve | Effort | Contradicteur |
|---|---|---|---|---|
| Critique | Rail commands sans limite de débit, sans quota ni contrôle de plan par application | `src/app/api/v1/external-applications/[applicationKey]/commands/route.ts:23` | M | confirmé — Confirmé sur le code réel : route.ts l.23-57 enchaîne bien signature (l.29-40) → parse (l.44) → dispatchExternalApplicationCommand (l.49) sans aucun appel rate-limit/throttle/quota. grep sur src/app/api/v1 et src/lib/external-applications ne renv… |
| Critique | Isolation inter-écoles nulle si plusieurs tenants KLASSCI partagent une organisation : upsert contact par email/téléphone et STOP à l'échelle de l'organisation | `src/app/api/v1/contacts/route.ts:48` | M | confirmé — Confirmé intégralement. route.ts:48-54 (léger décalage, pas 49) : upsert Prisma sur where:{email_organizationId:{email,organizationId}} — clé composite = contrainte @@unique([email,organizationId]) (schema.prisma:240), external_id n'est jamais da… |
| Majeur | Notifications WhatsApp KLASSCI en texte libre sur le rail API : échec structurel hors fenêtre 24h sur Meta officiel, bascule SMS payante | `app/Services/MailPulse/MailPulseWorkflowNotificationService.php:324` | M |  |
| Majeur | Deux rails, deux modèles d'authentification : la clé API d'organisation n'a ni scope, ni application, ni expiration | `src/lib/mailpulse/api-keys.ts:28` | L |  |
| Majeur | Branchement d'une école côté KLASSCI : dix valeurs uniquement en .env, absentes de l'UI Settings et de tenant:provision | `config/services.php:89` | M |  |
| Majeur | Aucune observabilité par application : ni log, ni métrique, ni alerte sur callbacks échoués | `src/lib/external-applications/meta-webhook.ts:549` | M |  |
| Majeur | Les tests « external applications » de la CI vérifient le texte du code source, pas le comportement | `src/lib/external-applications/commands.test.ts:6` | M |  |
| Majeur | Le LMS n'a aucune intégration MailPulse : la promesse « moteur de notifications du groupe » n'est vraie que pour KLASSCI | `/home/user/james10192/klassci-lms/lms-klassci-frontend/src/environments/environment.prod.ts:3` | M |  |
| Majeur | Contrat documenté incomplet : codes de rejet, 409/503 et runbook de provisioning absents | `content/docs/api-reference.mdx:151` | S |  |
| Majeur | Token Meta d'organisation stocké en clair alors que le même numéro est chiffré côté ProviderAccount : deux sources de vérité, une non protégée | `prisma/schema.prisma:127` | M |  |
| Majeur | Rotation du secret de rappel sans recouvrement : coupure des réponses parents entre la rotation MailPulse et la mise à jour du .env | `src/app/(dashboard)/dashboard/settings/external-applications/endpoint-actions.ts:96` | S |  |
| Majeur | Rétention illimitée des payloads chiffrés (numéro + texte des parents) côté MailPulse, sans purge | `prisma/schema.prisma:1016` | S |  |
| Mineur | Code legacy d'envoi direct Meta/Orange encore instancié dans KLASSCI, contredisant « KLASSCI n'appelle jamais un fournisseur » | `app/Services/NotificationService.php:31` | S |  |
| Mineur | Jeton entrant Baileys transporté en query string et credentials sans expiration | `src/lib/external-applications/baileys-webhook.ts:50` | S |  |
| Mineur | Dépendance de toutes les écoles au plan de l'organisation : rétrogradation = 401 « Invalid API key » indistinct | `src/lib/mailpulse/api-keys.ts:55` | S |  |

</details>

## Ce qui engage KLASSCI

Trois chantiers ne se livrent qu'à deux dépôts à la fois.

- **Contrat maître↔tenant v2** (adminKlassci #77) : le maître doit exposer `enforcement`, `entitlements`, une version et un throttle ; KLASSCI doit les consommer dans `PaywallMiddleware` (mode bloqué ou lecture seule, features par `Gate::before`, cache d'échec, stale-while-revalidate) et corriger `GroupCacheInvalidator` qui lit `services.master.url` au lieu de `api_url`. Sans cela, ni la suspension pour impayé, ni l'activation d'un module depuis le panel n'existent.
- **SSO groupe** (adminKlassci #67, KLASSCI #813) : côté tenant, résoudre le membre autrement que par email seul et rejeter un nonce déjà servi.
- **KLASSCI, client de MailPulse** (mailpulse #20 et #21, KLASSCI #821). Le cadre est clair : KLASSCI consomme les services génériques que MailPulse offre à tous ses clients (API v1, applications externes, templates, webhooks), sans intégration sur mesure côté MailPulse. Ce qui relève de MailPulse est donc générique : `Contact.externalId` unique pour tout client qui gère plusieurs sous-ensembles de contacts, débit et quota par application, documentation exhaustive des codes de rejet, assistant de branchement qui produit la configuration pour n'importe quel client, SDK minimal. Ce qui relève de KLASSCI, comme de tout client : choisir son découpage (une organisation MailPulse par établissement), envoyer des templates WhatsApp approuvés au lieu de texte libre (sinon chaque notification hors fenêtre de 24 h bascule en SMS payant), stocker sa configuration dans ses réglages plutôt que dans dix variables `.env`, et supprimer son code d'envoi direct legacy.

Le LMS n'est pas encore client de MailPulse ; ce n'est pas un constat MailPulse. Le jour venu, il utilisera les mêmes services génériques, et la documentation « brancher une application » (mailpulse #20) doit suffire sans développement spécifique.

## Issues GitHub

adminKlassci : épic [#65](https://github.com/James10192/adminKlassci/issues/65), 20 issues nouvelles, 6 existantes complétées. MailPulse : épic [#1](https://github.com/James10192/mailpulse/issues/1), 22 issues. KLASSCIv2 : [#821](https://github.com/James10192/KLASSCIv2/issues/821) rattachée à l'épic #738.

### adminKlassci

| N° | Type | Objet | Sévérité / état |
|---|---|---|---|
| [#59](https://github.com/James10192/adminKlassci/issues/59) | `fix(security)` | IDOR cross-tenant sur /limits, jetons en clair et en query string, identifiants non chiffrés, aucune Policy | complétée |
| [#60](https://github.com/James10192/adminKlassci/issues/60) | `fix(provisioning)` | tenant:provision prototype : URL placeholder, sous-domaine et SSL simulés, aucun rollback | complétée |
| [#61](https://github.com/James10192/adminKlassci/issues/61) | `feat(deploy)` | chemin serveur par tenant, rollback, drift, asynchrone, sondes réelles | existante |
| [#62](https://github.com/James10192/adminKlassci/issues/62) | `feat(support)` | console de support : restauration, logs, impersonation, doctor, reset admin école | complétée |
| [#63](https://github.com/James10192/adminKlassci/issues/63) | `feat(billing)` | plans jamais propagés aux quotas, Facture sans écran, aucune suspension automatique | complétée |
| [#64](https://github.com/James10192/adminKlassci/issues/64) | `feat(groupes)` | droits délégués, consolidation via l'API tenant, contrat versionné | complétée |
| [#65](https://github.com/James10192/adminKlassci/issues/65) | `épic` | Expertise SaaS adminKlassci : 93 constats, 22 critiques, plan de remise à niveau | nouvelle |
| [#66](https://github.com/James10192/adminKlassci/issues/66) | `fix(security)` | secrets de production commis dans la documentation | critique |
| [#67](https://github.com/James10192/adminKlassci/issues/67) | `fix(security)` | usurpation de n'importe quel compte tenant par un membre de groupe (SSO) | critique |
| [#68](https://github.com/James10192/adminKlassci/issues/68) | `fix(security)` | exécution de commandes via le paramètre branch du déploiement | critique |
| [#69](https://github.com/James10192/adminKlassci/issues/69) | `fix(backups)` | cleanup destructeur, dumps vides « completed », base maître jamais sauvegardée, aucune restauration | critique |
| [#70](https://github.com/James10192/adminKlassci/issues/70) | `fix(model)` | ENUM status/plan désynchronisés, tenant:provision échoue au premier INSERT | critique |
| [#71](https://github.com/James10192/adminKlassci/issues/71) | `fix(quotas)` | max_users=5 bloque tout tenant au 6e membre du personnel | critique |
| [#72](https://github.com/James10192/adminKlassci/issues/72) | `fix(monitoring)` | faux vert : stockage écrasé, sondes fictives, alertes tardives sans dédup | critique |
| [#73](https://github.com/James10192/adminKlassci/issues/73) | `fix(discover)` | « Scanner les tenants » importe une école Élite en Free/50 | critique |
| [#74](https://github.com/James10192/adminKlassci/issues/74) | `feat(jobs)` | worker de file, Artisan::call synchrone, verrou, watchdog, faux succès | critique |
| [#75](https://github.com/James10192/adminKlassci/issues/75) | `fix(group-portal)` | emails du portail jamais envoyés (ShouldQueue sans worker) | critique |
| [#76](https://github.com/James10192/adminKlassci/issues/76) | `fix(group-portal)` | KPIs faux ou silencieusement vides | majeur |
| [#77](https://github.com/James10192/adminKlassci/issues/77) | `feat(contract)` | contrat maître↔tenant v2 : enforcement, entitlements, calculs, version, throttle | critique |
| [#78](https://github.com/James10192/adminKlassci/issues/78) | `refactor(architecture)` | revue thermo-nucléaire : couche domaine, god service, plans ×3, scheduler ×2, env() | majeur |
| [#79](https://github.com/James10192/adminKlassci/issues/79) | `fix(ci)` | CI Unit seulement, tests grep, factories | critique |
| [#80](https://github.com/James10192/adminKlassci/issues/80) | `fix(security)` | durcissement du panel : secrets dans Livewire, audit trail, rôles, MFA, en-têtes, rotation | majeur |
| [#81](https://github.com/James10192/adminKlassci/issues/81) | `feat(hosting)` | registre de serveurs et régions | majeur |
| [#82](https://github.com/James10192/adminKlassci/issues/82) | `feat(lifecycle)` | wizard d'onboarding, offboarding/export, réinitialisation d'accès | majeur |
| [#83](https://github.com/James10192/adminKlassci/issues/83) | `fix(tenant-config)` | pages TenantConfig en SQL direct, cache jamais purgé | majeur |
| [#84](https://github.com/James10192/adminKlassci/issues/84) | `feat(group-portal)` | exploitation et UX du portail groupe | majeur |
| [#85](https://github.com/James10192/adminKlassci/issues/85) | `docs` | documentation d'exploitation et runbooks | mineur |

### MailPulse

| N° | Type | Objet | Sévérité / état |
|---|---|---|---|
| [#1](https://github.com/James10192/mailpulse/issues/1) | `épic` | Expertise SaaS MailPulse : 102 constats, 21 critiques | nouvelle |
| [#2](https://github.com/James10192/mailpulse/issues/2) | `fix(security)` | server actions sans session ni scoping, expéditeur d'un autre tenant | critique |
| [#3](https://github.com/James10192/mailpulse/issues/3) | `fix(security)` | pages Tags, Désabonnements, Analytics agrègent toute la plateforme | critique |
| [#4](https://github.com/James10192/mailpulse/issues/4) | `fix(security)` | admin d'organisation = admin de plateforme | critique |
| [#5](https://github.com/James10192/mailpulse/issues/5) | `fix(security)` | Convex non authentifié + double source de vérité | critique |
| [#6](https://github.com/James10192/mailpulse/issues/6) | `fix(delivery)` | campagnes en boucle synchrone, cancel/resend double, cron non atomique | critique |
| [#7](https://github.com/James10192/mailpulse/issues/7) | `fix(delivery)` | transactionnels sans retry, timeouts fournisseurs, repli de numéro | critique |
| [#8](https://github.com/James10192/mailpulse/issues/8) | `fix(sms)` | SMS mono-organisation, scheduler GitHub Actions, accusés Orange, limites | majeur |
| [#9](https://github.com/James10192/mailpulse/issues/9) | `fix(webhooks)` | webhooks sortants : SSRF, une tentative, secret en clair | majeur |
| [#10](https://github.com/James10192/mailpulse/issues/10) | `fix(auth)` | organisation active ignorée, index Member, FK userId | majeur |
| [#11](https://github.com/James10192/mailpulse/issues/11) | `fix(security)` | vérification d'email, account linking, clés API sans scopes, upload R2, redirection ouverte | majeur |
| [#12](https://github.com/James10192/mailpulse/issues/12) | `feat(compliance)` | consentement, désinscription, Filon et STOP, pages légales, rétention | majeur |
| [#13](https://github.com/James10192/mailpulse/issues/13) | `fix(billing)` | Paystack : montant/devise, échéance, webhook, ledger | majeur |
| [#14](https://github.com/James10192/mailpulse/issues/14) | `fix(perf)` | analytics O(N) par ouverture, dédup EmailEvent | critique |
| [#15](https://github.com/James10192/mailpulse/issues/15) | `fix(db)` | chaîne de migrations qui échoue sur base neuve, CI en db push | critique |
| [#16](https://github.com/James10192/mailpulse/issues/16) | `fix(scale)` | contacts 50, import CSV, campagnes N+1, index | majeur |
| [#17](https://github.com/James10192/mailpulse/issues/17) | `refactor(architecture)` | outbox unique, frontières domaine, cycle email, composants, erreurs, docs | majeur |
| [#18](https://github.com/James10192/mailpulse/issues/18) | `fix(tests)` | suite illusoire : Vitest, comportement, Postgres CI, Playwright + axe | majeur |
| [#19](https://github.com/James10192/mailpulse/issues/19) | `feat(observability)` | logger, alertes d'état, AuditLog, métriques par application | majeur |
| [#20](https://github.com/James10192/mailpulse/issues/20) | `feat(external-apps)` | débit/quota par application, deux rails, rotation, token Meta, doc des codes | critique |
| [#21](https://github.com/James10192/mailpulse/issues/21) | `fix(multi-school)` | isolation inter-écoles, externalId, une organisation par établissement | critique |
| [#22](https://github.com/James10192/mailpulse/issues/22) | `feat(onboarding)` | onboarding réel, checklist serveur, dashboard marketing, envoi de test | majeur |
| [#23](https://github.com/James10192/mailpulse/issues/23) | `fix(a11y)` | modales, mode clair, reduced-motion, clavier, erreurs, français | majeur |

## Feuille de route

**30 jours — Fermer ce qui expose ou perd des données**

- adminKlassci : [#66](https://github.com/James10192/adminKlassci/issues/66), [#68](https://github.com/James10192/adminKlassci/issues/68), [#67](https://github.com/James10192/adminKlassci/issues/67), [#80](https://github.com/James10192/adminKlassci/issues/80), [#69](https://github.com/James10192/adminKlassci/issues/69), [#70](https://github.com/James10192/adminKlassci/issues/70), [#71](https://github.com/James10192/adminKlassci/issues/71), [#73](https://github.com/James10192/adminKlassci/issues/73), [#75](https://github.com/James10192/adminKlassci/issues/75)
- MailPulse : [#2](https://github.com/James10192/mailpulse/issues/2), [#3](https://github.com/James10192/mailpulse/issues/3), [#4](https://github.com/James10192/mailpulse/issues/4), [#21](https://github.com/James10192/mailpulse/issues/21), [#6](https://github.com/James10192/mailpulse/issues/6), [#7](https://github.com/James10192/mailpulse/issues/7), [#15](https://github.com/James10192/mailpulse/issues/15), [#14](https://github.com/James10192/mailpulse/issues/14)

**90 jours — Rendre l'exploitation fiable sans code**

- adminKlassci : [#74](https://github.com/James10192/adminKlassci/issues/74), [#77](https://github.com/James10192/adminKlassci/issues/77), [#72](https://github.com/James10192/adminKlassci/issues/72), [#79](https://github.com/James10192/adminKlassci/issues/79), [#76](https://github.com/James10192/adminKlassci/issues/76), [#83](https://github.com/James10192/adminKlassci/issues/83)
- MailPulse : [#5](https://github.com/James10192/mailpulse/issues/5), [#9](https://github.com/James10192/mailpulse/issues/9), [#8](https://github.com/James10192/mailpulse/issues/8), [#12](https://github.com/James10192/mailpulse/issues/12), [#13](https://github.com/James10192/mailpulse/issues/13), [#11](https://github.com/James10192/mailpulse/issues/11), [#10](https://github.com/James10192/mailpulse/issues/10), [#16](https://github.com/James10192/mailpulse/issues/16)
- KLASSCI : [#813](https://github.com/James10192/KLASSCIv2/issues/813), [#821](https://github.com/James10192/KLASSCIv2/issues/821)

**180 jours — Fondations et produit**

- adminKlassci : [#78](https://github.com/James10192/adminKlassci/issues/78), [#84](https://github.com/James10192/adminKlassci/issues/84), [#81](https://github.com/James10192/adminKlassci/issues/81), [#82](https://github.com/James10192/adminKlassci/issues/82), [#85](https://github.com/James10192/adminKlassci/issues/85)
- MailPulse : [#17](https://github.com/James10192/mailpulse/issues/17), [#18](https://github.com/James10192/mailpulse/issues/18), [#19](https://github.com/James10192/mailpulse/issues/19), [#22](https://github.com/James10192/mailpulse/issues/22), [#23](https://github.com/James10192/mailpulse/issues/23), [#20](https://github.com/James10192/mailpulse/issues/20)

## Méthode et limites

Le workflow a lancé douze experts (un par dimension et par dépôt, modèle de la session, effort standard) avec un mandat identique : explorer réellement le code, citer un fichier et une ligne par constat, estimer impact, correction, critères d'acceptation et effort. Chaque constat classé critique a ensuite été confié à un contradicteur (Sonnet, effort réduit) chargé de le réfuter en cherchant les garde-fous qui l'annuleraient : middleware, policy, test, contrainte de base. Sur 43 critiques, 42 sont confirmés (plusieurs avec une ligne corrigée, deux jugés plus graves que l'énoncé : `deleteTag` sans aucune authentification, `migrate deploy` qui échoue réellement) et un est rétrogradé en majeur (assiduité à 0 % : KPI d'affichage, pas de perte de données). Les constats majeurs et mineurs n'ont pas été contredits individuellement ; leurs preuves sont citées et vérifiables.

Limites. Aucun test de bout en bout n'a été fait sur admin.klassci.com faute d'identifiants du panel maître : seules des sondes publiques non destructives ont été jouées (API refusant sans jeton, message d'erreur confirmant l'acceptation du jeton en paramètre d'URL, absence d'en-têtes de sécurité HTTP, page 500 générique sur `/api/user` pour un navigateur). Sur MailPulse, sondes publiques uniquement (HSTS présent, `/api/health` public détaillant base et Resend, crons refusant sans secret). Les comptages exacts (74 littéraux de statut, 216 `return { error }`) proviennent des experts et n'ont pas été recomptés un par un ; les constats structurels qu'ils illustrent, eux, ont été vérifiés.

---

Voir aussi : [Expertise produit KLASSCI](2026-09-02-expertise-saas-klassci.md) et [Maintenabilité et reproductibilité des quatre dépôts](2026-09-02-maintenabilite-reproductibilite.md) (même jour, même dispositif).
