# KLASSCI LMD 360 · Decisions

## ADR-001 · Verite Git avant implementation

Decision: toute intervention sur `presentation` commence par `git fetch` puis alignement local. Les modifications locales non liees sont sauvegardees avant sync.

Consequence: le chantier part de `ac57b782`, pas de l'ancien local `2010faac`.

## ADR-002 · Compatibilite Laravel

Decision: ne pas effectuer d'upgrade Laravel dans le chantier LMD 360. Le depot declare `laravel/framework` compatible `^9.0|^10.0`, malgre le contrat produit mentionnant Laravel 12.

Consequence: toute migration Laravel 12 doit etre un chantier separe avec tests d'infrastructure.

## ADR-003 · Credit Wallet source

Decision: la premiere tranche du portefeuille de credits lit les bulletins LMD publies. Les brouillons ne capitalisent pas de credits.

Consequence: pas de seconde source de verite. Le ledger persistant viendra ensuite comme historique auditable derive ou backfille, avec dry-run.

## ADR-004 · CEV et verification

Decision: KLASSCI peut fournir une verification locale par reference, empreinte et QR public. Une signature electronique certifiee exige un fournisseur externe, une politique d'identite, des cles, horodatages, revocations et validation juridique.

Consequence: ne pas presenter le fallback KLASSCI comme une certification legale externe.

## ADR-005 · Offline

Decision: l'offline porte sur brouillons chiffres, outbox et reprise controlee. Pas de cache complet de pages sensibles authentifiees sur postes partages.
