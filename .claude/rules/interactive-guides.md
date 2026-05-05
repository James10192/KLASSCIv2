---
name: Interactive guides for dense admin screens
description: Quand une page KLASSCI devient dense, ajouter un guide interactif contextuel plutot que seulement du texte d'aide statique.
type: project
---

# Rule: Guides interactifs pour ecrans denses

## Quand s'active

Cette rule s'active quand tu travailles sur une page qui cumule plusieurs zones fonctionnelles :
- tableaux de bord avec KPIs + filtres + actions;
- configuration avancee avec plusieurs listes, etats, formulaires et actions en masse;
- roles / permissions / custom roles;
- analytics, relances, echeanciers, exports, dashboards comptables;
- tout ecran ou l'utilisateur risque de ne plus savoir "par ou commencer".

## Regle produit

Quand une page devient dense, ne pas se contenter d'ajouter encore plus de texte visible.

Ajouter deux niveaux d'accompagnement :
1. **Aide** : modal documentaire complet, consultable quand l'utilisateur veut relire.
2. **Guide** : tour interactif court, en contexte, qui surligne les zones une par une.

Le guide doit aider l'utilisateur a agir, pas seulement expliquer l'interface.

## Pattern recommande

### 1. Boutons en header

Placer les boutons dans les actions du header :

```blade
<button type="button" class="btn-acasi primary" data-page-tour-open>
    <i class="fas fa-route"></i>Guide
</button>

<button type="button" class="btn-acasi secondary" data-page-help-open>
    <i class="fas fa-question-circle"></i>Aide
</button>
```

### 2. Tour interactif

Le tour doit :
- surligner la zone cible;
- afficher une carte courte avec titre + texte;
- proposer `Retour`, `Suivant`, `Terminer`, `Quitter`;
- afficher la progression;
- se fermer avec `Escape`;
- ignorer automatiquement les etapes dont le bloc n'est pas visible;
- continuer a fonctionner apres navigation AJAX ou remplacement partiel du DOM.

### 3. Etapes utiles

Preferer des etapes orientees workflow :
1. Lire les indicateurs globaux.
2. Filtrer ou rechercher.
3. Choisir l'entite a configurer.
4. Modifier la zone principale.
5. Verifier la coherence.
6. Simuler / previsualiser.
7. Enregistrer ou appliquer en masse.

Eviter les etapes qui decrivent seulement le visuel :
- "ceci est un bouton";
- "ceci est une carte";
- "ceci est un tableau".

## UX attendue

- Le guide ne doit pas bloquer durablement le travail.
- Le texte de chaque etape doit tenir en 1-2 phrases courtes.
- Le guide doit etre relancable manuellement.
- Ne pas auto-lancer le guide sans demande utilisateur, sauf onboarding explicitement demande.
- Sur mobile, placer la carte du guide en bas de l'ecran pour eviter les chevauchements.
- Le highlight doit etre visible sans casser le layout.

## Implementation technique

Pour Blade legacy / Alpine-light :
- utiliser des `data-*` explicites (`data-page-tour-open`, `data-tour-node`, etc.);
- garder le JS local a la vue si le pattern n'est pas encore composantise;
- utiliser une liste declarative d'etapes `{ selector, title, text }`;
- filtrer les etapes sans cible visible;
- nettoyer les nodes du tour avant chaque nouvelle etape;
- retirer toutes les classes de highlight au cleanup.

Checklist technique :
- [ ] `Escape` ferme le guide.
- [ ] Clic sur `Quitter` nettoie overlay + highlight.
- [ ] Les etapes invisibles sont ignorees.
- [ ] Le tour fonctionne apres AJAX / DOM replace.
- [ ] Le guide n'ajoute pas de scroll horizontal.
- [ ] La page compile avec `php artisan view:cache`.

## Anti-patterns a bloquer

1. Ajouter un long paragraphe visible dans la page pour expliquer une interface deja dense.
2. Mettre uniquement un modal d'aide statique quand le probleme est "je ne sais pas ou regarder".
3. Creer un tour avec trop d'etapes (> 10-12) qui fatigue l'utilisateur.
4. Surligner un element non visible sans scroll automatique.
5. Laisser un overlay ou une classe highlight apres fermeture.
6. Hardcoder des positions fixes qui cassent sur mobile.
7. Bloquer la page avec un guide lance automatiquement a chaque visite.

## Exemple fondateur

Page echeanciers de paiement :
- `Guide` : tour interactif court avec highlight des diagnostics, filtres, scopes, editeur, presets, tranches, total, preview et simulation.
- `Aide` : modal de reference pour relire les concepts.

Ce pattern est a reutiliser sur toute page KLASSCI dense ou le niveau d'information devient intimidant.
