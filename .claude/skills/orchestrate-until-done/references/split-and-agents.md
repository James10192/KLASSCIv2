# Découpe et agents

## Comment découper

1. Compter les verbes impératifs et les items numérotés.
2. Si un dossier d'architecture liste des lots (UCAO `11-backlog.md`), **ces lots sont la découpe**. Ne pas recréer un plan concurrent.
3. Fusionner deux points seulement s'ils touchent le **même** fichier et la même preuve.
4. Extraire les preuves (tests, captures) en points séparés : un code sans preuve reste une case ouverte.

## Taille d'un lot agent

Un agent : un objectif, une liste de fichiers max ~15, un critère d'acceptation, une interdiction claire (pas de commit, pas de deploy, pas d'effacement mobile).

Trop gros = il rend un résumé et « le reste ». Trop petit = overhead. Vise 30–90 minutes de travail réel.

## Parallélisme

Même tour, plusieurs Task :

- A explore LMD, B explore finance, C fetch URLs — OK.
- A et B écrivent `ESBTPSeanceCoursController.php` — **séquentiel**.

Après retour : tu intègres. Tu ne recopies pas l'agent. Tu vérifies ses chemins (Read) avant de cocher.

## Prompt d'agent (gabarit)

```
Objectif : <un livrable>
Dépôt : KLASSCIv2, ne pas modifier hors <fichiers>
Architecture : suivre <doc> ; pas de if(ucao) ; pas de hasRole métier nouveau
Surfaces : ne pas supprimer resources/views/**/_*-mobile.blade.php ni pdf*
Rapport final : chemins, file:line, commandes, ce qui manque
Interdit : commit, push, deploy, secrets
```

## Quand ne pas lancer d'agent

Question d'une phrase. Typo. Fichier unique déjà ouvert. L'orchestrateur travaille alors lui-même, mais **ouvre quand même** completude.md s'il y a ≥ 2 points.
