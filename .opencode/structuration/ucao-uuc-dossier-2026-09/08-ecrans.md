# 08 — Écrans

Design system existant : `dashboard-acasi`, `main-card`, `stat-card`, `btn-acasi`, `--primary #0453cb`. Français métier. Pas de codes permission, JSON, UUID, stack traces.

Chaque espace : file **À traiter**, **En attente de…**, motif, historique, filtre, lien pièce.

États transverses : vide, chargement, accès refusé (FR : « Vous n’avez pas le droit de… » + A6), action interdite par l’état, donnée manquante, visa périmé, pièce absente, réseau, reprise. Masse : prévisualiser, résultat par ligne, pas d’écrasement silencieux.

## Espaces de travail

| Espace | Rôles types |
|---|---|
| Pilotage instance | admin instance |
| Scolarité | scolarité, dir. études |
| Enseignants | scolarité, SP |
| Caisse | caissier |
| Achats / stock | préparateur, magasin, dépenses |
| Trésorerie | trésorerie, DAF |
| Moyens généraux | patrimoine, flotte |
| Passerelle Paie | RH lecture, admin |

## Écrans critiques

### E1 — Réglages, provenance des règles

- **Objectif** : voir la valeur **effective** et d’où elle vient.
- **Champs** : groupe (téléphone, TPE, LMD, paie, achats). Chaque ligne : valeur, niveau (instance/programme), héritée ?, modifiable par moi ?
- **Actions** : modifier (si droit), aperçu d’impact, historique.
- **Erreur** : 229 sans préfixe BJ — déjà. Profil paie `non_valide` — bandeau « calculs indicatifs ».

### E2 — Droits et diagnostic

- Recherche personne. « Pourquoi Mme X ne voit pas les notes ESMEA ? »
- Réponse structurée : permission manquante **ou** hors composante **ou** fenêtre fermée **ou** config absente.
- Actions : proposer d’accorder **si** A4 le permet ; sinon « demandez à … ».
- Pas de bouton « voir comme ».

### E3 — Audit filtrable

- Filtres : période, auteur, objet, action. Export contrôlé.
- Masquage : salaires, pièces d’identité. « Révéler » = motif + journal.

### E4 — Maquettes

- Arbre domaine/mention/parcours/UE/ECUE. Crédits semestre (lecture 30).
- Import : fichier → **prévisualisation** → erreurs par ligne → confirmer.
- Dupliquer vers un parcours : avertir règles différentes.
- Version : `effective_from`, cohortes concernées.

### E5 — Planning

- Grille semaine. Conflits enseignant **et** salle (quand salle entité).
- Dupliquer la semaine (existant, le nommer clairement — ce n’est pas un solveur).
- TPE : créneau possible seulement si setting.
- Indispo salle (maintenance) = pastille + lien OT.

### E6 — Dossier enseignant / agrément

- Identité, régimes, agréments (période, périmètre), affectations, taux.
- Alerte : expire dans N jours (setting), pièce manquante, déclencheur réexamen.
- Service passé reste visible si agrément échu.

### E7 — Certification et file SP

- File « À viser » : dossiers complets / incomplets.
- Détail : séances, durées planifiées vs constatées, pièces.
- Actions : viser, refuser (motif obligatoire), renvoyer.
- Suivant : préparation paie (existant) **sans** barème BJ silencieux.

### E8 — Suivi TPE

- Calendrier séances TPE + journal déclarations.
- Validation / rejet. Doublon et chevauchement signalés.
- Jamais de bouton « convertir en heures payées ».

### E9–E14 — Achats

| Écran | Objectif | Actions |
|---|---|---|
| Besoin | exprimer | soumettre, voir stock |
| Comparaison offres | choisir | grille, motif source unique |
| Commande | engager | lignes, visas, PDF |
| Réception | constater | partielle, écart, pas de 2e stock à la facture |
| Facture | 3-way | reliquat, exception motivée |
| Dossier paiement | payer | dimensions, indéterminé, pas retry aveugle |

Libellés : « Reliquat 12 unités », « Proforma — n’est pas une facture », « En attente du visa DAF ».

### E15 — Trésorerie / rapprochement

- Onglets : Caisse (existant) · Banque · Tiers.
- Import relevé : dédup, propositions, inconnus.
- « Même montant et même date ne suffisent pas » — UI exige la référence.

### E16 — Mouvements / inventaire

- Entrée, sortie, transfert, inventaire. Écart = motif + approbateur.
- Stock physique vs théorique vs valeur (si valorisé).

### E17 — Patrimoine / incidents / maintenance

- Arbre campus/bâtiment/local/équipement.
- Incident → OT. Coût cumulé. Lien planning.

### E18 — Mission / véhicule

- Disponibilité. Compteur. Lien mission RH (id externe) sans fusionner.

### E19 — Passerelle ADC Paie

- Dernière sync, file d’erreurs, relancer **une** clé.
- « Rejeté : matricule inconnu » — corrigeable. Timeout ≠ OK.

## Parcours quotidien (mode simple)

Caissier : encaisser → reçu. Magasin : recevoir commande du jour. SP : viser 3 dossiers. Scolarité : saisir notes dans la fenêtre. Admin : diagnostiquer un accès.

Les circuits à 4 visas n’apparaissent que si le dossier **dépasse** le seuil.
