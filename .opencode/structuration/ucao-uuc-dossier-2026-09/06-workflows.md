# 06 — Workflows

Tables de transitions. Variantes légitimes. Pas un BPMN studio.

## 1. TPE

### Modes (setting B.3)

| Mode | Preuve | Ne prouve pas |
|---|---|---|
| Déclaration libre (actuel) | L’étudiant **dit** avoir travaillé | Présence, durée, qualité |
| Séance encadrée sur site | Créneau + émargement + (opt) superviseur | Qualité du livrable |
| Autonome sur site | Entrée/sortie lieu + activité | Encadrement enseignant |
| Dépôt de preuve | Livrable horodaté | Durée |
| Hybride | Combinaison configurée | Rien tout seul |

Wi-Fi, QR, émargement isolé : **signaux**, jamais conversion auto vers heure enseignante payable ni crédit ECUE.

### Parcours séance encadrée (UCAO probable)

1. Programmation crée une séance `type_seance=TPE` (seulement si setting l’autorise).
2. Conflits : enseignant, salle, groupe — **et** vs CM/TD/TP du même groupe.
3. Début/fin, lieu, activité, superviseur.
4. Constat : émargement étudiants + enseignant (ou oubli de sortie → exception motivée).
5. Validation pédagogique **ou** rejet motivé (stratégie déjà : auto / enseignant).
6. Dépassement : visible, pas d’écrêtage silencieux.
7. Contestation : dossier, pas réécriture de la ligne brute.

États déclaration : `brouillon → soumise → validee | rejetee`. Correction tardive = nouvelle version, motif, qui.

Perte de connexion : brouillon local **optionnel** ; pas de « offline complet ».

## 2. Enseignant : agrément → paiement

Objets **séparés** :

```
Identité personne
  ├─ Agrément / habilitation (émetteur, périmètre matières, from/to, pièces)
  ├─ Relation contrat (salarié ADC Paie | vacataire KLASSCI | prestataire)
  ├─ Affectation enseignement (ECUE, période, volumes)
  ├─ Conditions tarifaires (taux, type CM/TD/TP/TPE, grade, période)
  └─ Autorisation de paiement (file SP)
```

### Contrôles

| Moment | Règle |
|---|---|
| Affectation | agrément applicable **ce jour** au périmètre |
| Date du cours | idem ; séance déjà faite + agrément expiré **après** ⇒ dette conservée |
| Paiement | service **certifié** + visa SP + pas de double paiement de la même séance |

Expiration après service régulier : ne pas effacer les heures. Options (setting) : payer · geler en « à arbitrer » · exiger avenant. Défaut proposé : **geler à arbitrer**, pas payer ni supprimer.

Remplacement enseignant : la séance pointe le remplaçant ; les heures vont à **lui**. L’absent n’est pas payé pour ce créneau.

Assiette : aujourd’hui durée planifiée si émargé. Cible : durée constatée (début/fin réels) si saisie, sinon planifié **libellé estimation**. Baromètre planifié ≠ base de paie (déjà le design, pas encore le code).

File SP : `fiche_execution` (fait) → `demande_prestation` → `fiche_facturation` → `visa_sp` → `prepare_paie`. Refus = retour + motif. SP absent = délégation datée (D-01).

## 3. Chaîne 6.1 — besoin → dette fournisseur

États dossier d’achat : `brouillon → qualifie → consulte → choisi → engage → commande → reception_partielle|reception → facture_controlee → comptabilise | refuse | annule`.

| Étape | Acteur type | Permission | Pièces selon type | Variantes |
|---|---|---|---|---|
| Expression de besoin | service demandeur | `achats.demander` | objet, qty, justification, budget | urgence flaggée (circuit court, **pas** un bypass permanent) |
| Qualif. + stock | magasin | `stock.voir` | dispo, réservation | **satisfaction stock** ⇒ stop achat, sortie interne |
| Contrôle budgétaire | DAF / contrôleur | `budget.controler` | disponible | dépassement = visa extra ou refus |
| Consultation | préparateur | `achats.consulter` | ≥ N devis (setting, 1 = source unique **motivée**) | récurrent / marché lots |
| Comparaison | préparateur | — | grille multi-critères | pas le min prix auto |
| Engagement | chargé dépenses + visas | `achats.engager` | circuit B.9 | changement montant/IBAN ⇒ invalidation |
| Commande / contrat | mêmes | `achats.commander` | n° unique, lignes | acompte distinct |
| Réception / service fait | magasin ou certificateur | `stock.recevoir` / `achats.certifier` | BL ≠ réception ; attestation si immatériel | partielle, échelonnée, non-conforme, retour |
| Facture | dépenses | `achats.facture` | facture ≠ proforma ; IFU si applicable | sans commande = exception motivée |
| Contrôle 3-way | dépenses | lignes qty/prix/reliquat | tolérance setting | multi-commandes / multi-factures |
| Comptabilisation dette | selon D-04 | — | — | si GL externe : export événement `invoice.approved` |

Rapprochement commande–réception–facture **à la ligne**. Reliquat toujours visible.

Fournisseur : identité légale, IFU, contacts, RIB, conditions, pièces. Modifier le bénéficiaire après visa = nouveau contrôle SoD.

## 4. Chaîne 6.2 — dette → rapprochement

Dimensions **parallèles** (pas un statut unique) : demandé, engagé, reçu, facturé, comptabilisé, autorisé, ordonnancé, réglé, rapproché.

```
dette_validee → proposition_paiement → autorisations → ordre
    → execution → confirmation_fiable → lettrage → rapprochement → cloture
```

Paiement résultat **inconnu** : état `indetermine` ; **vérifier avant retry**. Webhook / notif prestataire : authentifiée, dédupliquée, rapprochée. Capture d’écran ≠ preuve.

Variantes : acompte, solde, retenue garantie, retenue fiscale, frais, groupé, fractionné, trop-payé, remboursement, rejet, annulation. Doublon : contrainte d’unicité `(fournisseur, piece, montant, date)` **insuffisante seule** — identifiant d’opération externe obligatoire quand il existe.

### Quatre rapprochements

| Type | Objets | Import | Proposition | Clôture |
|---|---|---|---|---|
| Caisse | recettes/décaissements vs comptage | déjà KLASSCI | écarts `DetectDiscrepancies` | lock paiements existant |
| Banque / mobile | relevé vs trésorerie | CSV/OFX **à construire** | montant+date+ref, 1-n / n-1 | pas fusion au seul montant+date |
| Tiers | factures, avoirs, paiements, avances | — | lettrage partiel | reliquat |
| Auxiliaires / GL | sous-comptes vs grand livre | si D-04 interne | — | — |

## 5. Stocks et patrimoine

Types : consommable, stock, matériel affecté, immo, loué/prêté, confié (suivi physique ≠ propriété).

Mouvements : réception (une fois — **pas** à la facture), réservation, sortie service, retour, transfert **avec** réception, inventaire contradictoire, écart motivé, perte, réforme.

Ajustement : permission `stock.ajuster` + motif + approbateur. Sortie service ≠ cession immo.

Indispo salle/équipement → événement planning (conflit **actionnable**, pas un log).

## 6. Maintenance

`incident → diagnostic → demande → OT → prestataire/pièces/temps → réception technique → remise en service`.

Préventif / contrôle réglementaire / réparation. Échéances : date, compteur, heures, événement. Tâche reportée **reste** dans l’historique. Fermeture ticket n’efface ni indispo ni coût.

## 7. Véhicules et missions

Mission RH (ADC Paie ou KLASSCI RH) **liée** à réservation logistique, objets distincts.

`demande → visa → réservation véhicule+conducteur → départ → retour → frais/avance → justification`.

Cas : véhicule immobilisé, conducteur absent, inter-campus, prestataire transport, compteur incohérent.

## 8. Maquette et notes (rappel)

Import : prévisualisation **sans écriture**, erreurs par ligne, duplication contrôlée.

Évolution maquette : nouvelle version `effective_from` + `cohortes[]`. Étudiant déjà inscrit : reste sur version d’entrée **sauf** bascule explicite.

Jury : constitution, habilitation membres, quorum (déjà), calcul explicable (à unifier B5), décision humaine, dérogation **dans les pouvoirs du jury** (pas « toute obligation »), signature, publication, rectification, réémission versionnée.

## 9. Pointage employés (ADC Paie maître)

Architecture réaliste, **pas** « IP = preuve » :

| Signal | Rôle | Limite |
|---|---|---|
| Auth nominative | facteur d’identité | — |
| Poste enregistré (certificat / MDM) | facteur appareil | coût |
| Code / QR **renouvelable** | anti-rejeu | partage possible |
| Réseau allowlist (IP) | signal de lieu approximatif | NAT, DHCP, VPN, proxy, partage |
| Validation manuelle | exceptions | obligatoire |

NIST SP 800-63B : l’IP n’est pas un facteur d’authentification. Pas de biométrie / GPS permanent par défaut.

Événements bruts immuables ; corrections à part (motif, approbateur). Brute ≠ sanction.

## 10. Transitions génériques d’approbation

| De | Vers | Garde |
|---|---|---|
| brouillon | soumis | pièces min selon type |
| soumis | approuvé / refusé / retour | acteur étape, délégation vivante |
| approuvé | invalidé | mutation montant/bénéficiaire/pièce |
| * | * | double clic idempotent |

Nouvelle version de circuit : dossiers en cours **non déplacés**.
