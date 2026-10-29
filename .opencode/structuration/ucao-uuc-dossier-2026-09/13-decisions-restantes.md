# 13 — Décisions restantes

Uniquement ce qu’un **paramètre ne peut pas** trancher. Un sigle, un taux, un gabarit : hypothèse + propriétaire, le dossier avance.

| ID | Pourquoi ça compte | Propriétaire pressenti | Alternatives | Hypothèse provisoire | Bloque précisément |
|---|---|---|---|---|---|
| D-01 | Intitulé et pouvoirs du **SP** | UCAO (direction) | service pédagogique / scolarité / autre | Permission `enseignants.prestation.validate`, label « SP » configurable | Lot 4 file de visa — **pas** le reste de la scolarité |
| D-02 | TPE : séance encadrée vs hybride | Scolarité UCAO | voir B.3 | `seance_encadree` | Uniquement le défaut UCAO du setting |
| D-03 | ADC Paie : API réelle, hébergement, responsable d’identité | ADC produit Paie | CSV only / API / pas de pont | CSV versionné en attendant | Lot 9 implémentation, pas le contrat papier |
| D-04 | Qui tient les **livres officiels** | DAF UCAO + expert-comptable | SYSCOHADA dans KLASSCI / SYCEBNL / logiciel dédié / cabinet | **KLASSCI n’est pas le livre** ; export | Lot 12 et états de clôture |
| D-05 | Effets d’un **impayé** sur scolarité | Direction UCAO + juriste | aucun / documents / examens | **Aucun blocage notes/examens/diplômes** inventé | `DocumentPrintGuard` étendu seulement |
| D-06 | Homologation / CAMES par parcours | UCAO + MESRS | pièces à fournir | Colonnes vides jusqu’aux arrêtés | Impression d’accréditation, pas l’enseignement |
| D-07 | Émetteur du diplôme / relevé | Rectorat vs UUC vs MESRS | gabarit mesrs déjà dans le code | Relevé MESRS ; diplôme rectorat **à confirmer** | Lot 10 PDF diplôme |
| D-08 | Droits des **parents d’étudiants majeurs** | UCAO + APDP | compte étudiant partagé (actuel) / parent borné / aucun | Rester sur compte étudiant **en l’état** ; ne pas inventer un portail parent | Pas l’ouverture |
| D-09 | Assujettissement e-MECeF | DAF / DGI | oui / non / partiel | Non implémenté tant que non établi | Facturation normalisée |
| D-10 | Formalités APDP + transfert CI | ADC juridique | | Minimisation dès lot 1 ; formalités hors code | Hébergement / sous-traitance IA |
| D-11 | User MySQL dédié par tenant | ADC infra | garder partagé (refusé ici) | Dédié pour les **nouveaux** ; plan de rotation anciens | Isolation H1 — peut précéder UCAO |
| D-12 | Devenir de `superAdmin` Gate::before | ADC produit | retirer / réserver ADC / flag instance | Flag instance : UCAO sans Gate::before ; CI inchangé jusqu’à migration comptes | Lot 1 |

**Ne bloque pas le dossier** : taux ITS BJ, libellé exact « attestation de travail précédent » (les deux capacités prévues), format européen du supplément, nombre de devis, seuil en XOF des visas président.
