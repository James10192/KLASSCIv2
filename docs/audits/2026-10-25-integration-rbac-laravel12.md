# Audit de combinaison des PR #1687 (permissions) et #1689 (Laravel 12)

Date : 25 octobre 2026. **Validation temporaire : pas de fusion ni de déploiement.**

## Arbre audité

- Départ : `49c20f713336c79259bc270c54f2a3620e83cb57` (PR #1687).
- Migration superposée : `2f23de73ad7a4b124c060dd6374441198f42bea3` (PR #1689).
- Résolution explicite des conflits logiques : `app/Http/Kernel.php` (Spatie v6, CORS + choix de profil), `CHANGELOG.md` (les deux annonces), `tests/Feature/Assistant/NananLotATest.php` (correctifs de permissions et callback Laravel 12).
- `composer.lock` inchangé par rapport à la PR #1689 : Laravel 12.70.1.
- Configuration `config/sanctum.php` alignée Sanctum v4 et contrôle de régression dédié.

## Preuves requises pour conclure

- [ ] Audit Composer, npm et Laravel sur CE commit combiné.
- [ ] Laravel CI/lint sur CE commit combiné.
- [ ] Tests unitaires et intégration BTS/LMD, droits de rôles, profils, inscriptions, API CLI, Nanan sur CE commit combiné.
- [ ] Création du schéma sur MySQL 8 et MariaDB 10.11 sur CE commit combiné.
- [ ] Revue des règles `.claude/rules/permissions.md` et `customizable-roles.md`.
- [ ] Recette réelle de connexion/session/OTP/SSO/Sanctum, migrations sur un clone anonymisé de tenant, compatibilité PHP CLI cPanel et extensions, sauvegarde/rollback.
- [ ] Contrôle des risques résiduels de #1688 (autres écritures de permissions) et workflow maker-checker non livré.

## Limites

La CI peut être verte sans démontrer les effets d'un `composer install --no-dev` sur LWS/cPanel, les migrations des anciennes bases clients, ni l'absence de régression visuelle. Le lot permissions contient beaucoup de changements (81 fichiers). Cette branche de preuve ne vaut donc **pas** autorisation de fusion.
