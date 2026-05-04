# Rule: No GOD Code — Comptabilite

## Quand s'active

Cette rule s'active des que tu modifies:
- `app/Http/Controllers/ESBTPPaiementController.php`
- `app/Http/Controllers/ESBTPComptabiliteController.php`
- `app/Http/Controllers/ESBTPComptabilite*Controller.php`
- `app/Domain/Comptabilite/**`
- `resources/views/esbtp/comptabilite/**`

## Objectif

Eviter les classes "God" et garder un code comptable testable, securise, et evolutif en multi-instance.

## Regles absolues

1. **Controller = orchestration uniquement** (validation, appel d'action/service, response)
2. **Aucune regle metier dense dans Blade** (pas de calcul business dans la vue)
3. **1 cas metier critique = 1 Action/Service dedie**
4. **Permissions via `@can()` / `can()`** (pas de role hardcode hors exceptions systeme)
5. **Settings sensibles en configuration d'instance** (pas de seuil hardcode)

## Seuils anti-GOD code

- Methode controller > 40 lignes => extraction obligatoire
- Controller > 200 lignes => split obligatoire
- Service/Action > 250 lignes => split obligatoire
- Plus de 2 niveaux d'imbrication => simplifier

## Architecture cible

Quand la logique grossit, extraire vers:
- `app/Domain/Comptabilite/<SousDomaine>/Actions/*`
- `app/Domain/Comptabilite/<SousDomaine>/DTOs/*`
- `app/Domain/Comptabilite/<SousDomaine>/Services/*`
- `app/Domain/Comptabilite/<SousDomaine>/Events/*`
- `app/Domain/Comptabilite/<SousDomaine>/Listeners/*`

Exemples de sous-domaines:
- `CashClosure`
- `MobileMoney`
- `Reconciliation`
- `Receipts`

## Checklist PR obligatoire

- [ ] Le controller touche est reste mince (orchestration)
- [ ] Les regles metier vivent dans Domain/Actions/Services
- [ ] Les checks d'acces utilisent permissions (pas de role hardcode)
- [ ] Les seuils sont lisibles depuis settings d'instance
- [ ] Tests feature + unit ajoutes/maj
- [ ] CHANGELOG mis a jour si impact utilisateur

## Anti-patterns a bloquer en review

1. Ajouter 100+ lignes metier dans un controller existant
2. Dupliquer la meme logique dans 2 controllers
3. Hardcoder un seuil monetaire (`> 5000000`) dans le code
4. Mettre du SQL complexe dans Blade
5. Coupler webhooks, validation et notification dans une seule methode

## Voir aussi

- `.claude/rules/customizable-roles.md`
- `.claude/rules/permissions.md`
- `.claude/rules/multi-agent-git-safety.md`
