# 📚 Documentation API KLASSCI

Ce dossier contient la documentation complète des APIs REST de KLASSCI.

## 🎯 Objectif

Ces documentations serviront à :
- 🌐 Générer un site de documentation API public/privé
- 📖 Fournir une référence pour les développeurs externes (LMS, applications mobiles, intégrations tierces)
- 🔄 Maintenir la cohérence et la qualité des APIs
- ✅ Faciliter l'onboarding de nouveaux développeurs

## 📂 Structure

Chaque fichier représente une **famille d'endpoints** liée à une entité ou un domaine métier :

```
docs/api/
├── README.md                    # Ce fichier
├── LMS_ENSEIGNANTS.md          # API LMS - Gestion enseignants
├── LMS_CLASSES.md              # À venir - API LMS - Gestion classes
├── LMS_EVALUATIONS.md          # À venir - API LMS - Gestion évaluations
└── ...
```

## 📝 Convention de Nommage

**Format** : `[DOMAINE]_[ENTITE].md`

**Exemples** :
- `LMS_ENSEIGNANTS.md` - APIs LMS pour les enseignants
- `LMS_CLASSES.md` - APIs LMS pour les classes
- `PARENT_NOTIFICATIONS.md` - APIs notifications parents
- `ADMIN_COMPTABILITE.md` - APIs administration comptabilité

## 📖 Template Standard

Chaque documentation suit cette structure :

```markdown
# 📚 API - [Nom de l'API]

## Vue d'ensemble
[Description courte de l'API et son usage]

## Authentification
[Méthode d'authentification, tokens, permissions requises]

## Endpoints

### Endpoint 1
**GET/POST/PUT/DELETE** `/api/...`

[Paramètres]
[Réponse format JSON]
[Exemples curl]

## Paramètres disponibles
[Table complète des query params]

## Cas d'usage
[Exemples pratiques d'utilisation]

## Performance
[Métriques et optimisations]

## Codes d'erreur
[Liste des erreurs possibles]

## Historique des modifications
- [Date] : [Description changement]
```

## 🔐 Authentification

**Méthode globale** : Laravel Sanctum Bearer Token

Génération d'un token :
```bash
php artisan tinker
>>> $user = App\Models\User::find(1);
>>> $token = $user->createToken('api-access', ['lms:access'])->plainTextToken;
```

Utilisation :
```bash
curl -H "Authorization: Bearer {token}" http://domain/api/...
```

## 📊 APIs Documentées

| Fichier | Description | Status | Dernière MAJ |
|---------|-------------|--------|--------------|
| [LMS_ENSEIGNANTS.md](LMS_ENSEIGNANTS.md) | Gestion enseignants (classes, matières, volume horaire) | ✅ Complet | 25/10/2025 |

## 🚀 APIs à Documenter

- [ ] LMS_CLASSES.md - Gestion classes et étudiants
- [ ] LMS_EVALUATIONS.md - Gestion évaluations et notes
- [ ] LMS_MATIERES.md - Catalogue matières
- [ ] LMS_EMPLOI_TEMPS.md - Planning et séances
- [ ] PARENT_NOTIFICATIONS.md - Notifications parents
- [ ] ADMIN_STATS.md - Statistiques et KPIs
- [ ] AUTH.md - Authentification et gestion tokens

## 🌐 Site de Documentation (Futur)

Ce dossier servira de base pour générer un site de documentation type :
- **Swagger/OpenAPI** : Génération automatique UI interactive
- **Docusaurus** : Site statique moderne avec versioning
- **Postman Collection** : Import direct dans Postman

## 📝 Règles pour l'IA

Voir [CLAUDE.md](../../CLAUDE.md) section "⚠️ RÈGLES IMPORTANTES POUR IA - DOCUMENTATION API" pour les instructions complètes.

**Rappel rapide** :
- ✅ Nouvelle API → Créer nouveau fichier `docs/api/NOM.md`
- ✅ API modifiée → Mettre à jour fichier existant + historique
- ✅ Toujours inclure exemples curl complets
- ✅ Documenter performance et breaking changes

---

*Documentation maintenue par l'équipe KLASSCI*
