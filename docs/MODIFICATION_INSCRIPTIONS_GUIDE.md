# Guide de Modification des Inscriptions - KLASSCI SAAS

## Vue d'ensemble

Le système KLASSCI permet maintenant de modifier **toutes** les informations affichées sur la page de détails d'une inscription, y compris le nouveau statut d'affectation. Cette fonctionnalité est essentielle pour maintenir la cohérence des données et permettre la mise à jour des informations selon l'évolution du statut des étudiants.

## Accès à la Modification

### Bouton "Modifier"

Le bouton **"Modifier"** est désormais accessible sur la page de détails de l'inscription (`/esbtp/inscriptions/show/{id}`) pour tous les utilisateurs ayant la permission `inscriptions.edit`, peu importe le statut de l'inscription.

**Ancienne logique** : Le bouton n'était visible que pour les inscriptions en statut `en_attente`
**Nouvelle logique** : Le bouton est visible selon les permissions utilisateur

```php
// Ancien code
@if($inscription->status === 'en_attente')
    <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn-acasi secondary me-2">
        <i class="fas fa-edit"></i>Modifier
    </a>
@endif

// Nouveau code  
@can('inscriptions.edit')
    <a href="{{ route('esbtp.inscriptions.edit', $inscription) }}" class="btn-acasi secondary me-2">
        <i class="fas fa-edit"></i>Modifier
    </a>
@endcan
```

## Champs Modifiables

### Informations Académiques

| Champ | Modifiable | Restriction |
|-------|-----------|-------------|
| **Filière** | ✅ Oui | Aucune |
| **Niveau d'études** | ✅ Oui | Aucune |
| **Classe** | ⚠️ Conditionnel | Seulement si statut = `en_attente` |
| **Statut d'affectation** | ✅ Oui | **NOUVEAU CHAMP** |

### Informations d'Inscription

| Champ | Modifiable | Restriction |
|-------|-----------|-------------|
| **Date d'inscription** | ✅ Oui | Format date valide |
| **Type d'inscription** | ✅ Oui | première_inscription, réinscription, transfert |
| **Statut** | ✅ Oui | en_attente, active, annulée, terminée |

### Informations Financières

| Champ | Modifiable | Restriction |
|-------|-----------|-------------|
| **Frais d'inscription** | ✅ Oui | Montant ≥ 0 |
| **Montant scolarité** | ✅ Oui | Montant ≥ 0 |
| **Observations** | ✅ Oui | Texte libre |

## Nouveau Champ : Statut d'Affectation

### Interface

Le nouveau champ **"Statut d'affectation"** a été ajouté au formulaire de modification avec :

```html
<div class="form-group">
    <label for="affectation_status">Statut d'affectation</label>
    <select class="form-control" id="affectation_status" name="affectation_status">
        <option value="">-- Sélectionner le statut --</option>
        <option value="affecté">Affecté</option>
        <option value="réaffecté">Réaffecté</option>
        <option value="non_affecté">Non affecté</option>
    </select>
    <div class="form-text text-muted">
        Le statut d'affectation détermine les frais de scolarité selon les subventions gouvernementales ivoiriennes.
    </div>
</div>
```

### Validation

Le champ est validé au niveau contrôleur :

```php
'affectation_status' => 'nullable|in:affecté,réaffecté,non_affecté',
```

### Impact

La modification du statut d'affectation impacte :
- Le calcul automatique des frais futurs
- Les statistiques d'établissement
- Les rapports de subventions gouvernementales

## Logique Métier

### Restrictions selon le Statut

#### Inscription en statut `en_attente`
- ✅ Tous les champs sont modifiables
- ✅ La classe peut être changée

#### Inscription en statut `active`, `annulée` ou autre
- ✅ Tous les champs sont modifiables **SAUF** la classe
- ❌ La classe est figée (affichée en lecture seule)
- ℹ️ Message d'avertissement affiché : *"La classe ne peut plus être modifiée après validation de l'inscription."*

#### Inscription en statut `terminée`
- ❌ Modification entièrement bloquée
- ℹ️ Message d'erreur : *"Les inscriptions terminées ne peuvent pas être modifiées."*

### Gestion des Permissions

La modification nécessite la permission `inscriptions.edit` qui peut être attribuée selon les rôles :

- **Administrateur** : Accès complet
- **Gestionnaire des inscriptions** : Accès selon configuration
- **Secrétaire** : Accès limité selon configuration

## Processus de Modification

### Étapes de Modification

1. **Accès** : Clic sur le bouton "Modifier" depuis la page de détails
2. **Formulaire** : Pré-remplissage avec les données actuelles
3. **Modification** : Changement des valeurs souhaitées
4. **Validation** : Validation côté client et serveur
5. **Sauvegarde** : Mise à jour en base de données
6. **Confirmation** : Redirection avec message de succès

### Traçabilité

Chaque modification est tracée :

```php
// Logging automatique
Log::info('Inscription modifiée', [
    'inscription_id' => $inscription->id,
    'user_id' => Auth::id(),
    'changes' => $inscription->getChanges()
]);
```

### Gestion des Erreurs

En cas d'erreur :
- Validation échouée → Retour au formulaire avec messages d'erreur
- Permissions insuffisantes → Page d'erreur 403
- Inscription inexistante → Page d'erreur 404

## Cas d'Usage Fréquents

### Changement de Statut d'Affectation

**Scénario** : Un étudiant initialement "non affecté" obtient une affectation via le MESRS

1. Accéder à la page de détails de l'inscription
2. Cliquer sur "Modifier" 
3. Changer le statut d'affectation de "Non affecté" à "Affecté"
4. Sauvegarder

**Résultat** : Les frais futurs se calculeront avec les tarifs subventionnés

### Correction d'Informations

**Scénario** : Erreur dans la filière ou le niveau d'études

1. Modifier les champs "Filière" et/ou "Niveau d'études"
2. Vérifier que la classe correspond toujours (si modifiable)
3. Ajuster le montant de scolarité si nécessaire
4. Sauvegarder

### Changement de Classe (Si Possible)

**Scénario** : Réaffectation d'un étudiant en attente vers une autre classe

1. Vérifier que l'inscription est en statut "en_attente"
2. Modifier le champ "Classe"
3. Les frais se recalculent automatiquement
4. Sauvegarder

## Bonnes Pratiques

### Pour les Administrateurs

1. **Vérifier la cohérence** : S'assurer que filière + niveau + classe correspondent
2. **Documenter les changements** : Utiliser le champ "Observations" pour justifier les modifications importantes
3. **Informer l'étudiant** : Communiquer les changements qui impactent les frais
4. **Contrôler les accès** : Attribuer la permission `inscriptions.edit` avec parcimonie

### Pour les Utilisateurs

1. **Double vérification** : Contrôler les données avant sauvegarde
2. **Statut d'affectation** : Bien comprendre l'impact sur les frais
3. **Restrictions** : Respecter les limitations selon le statut d'inscription
4. **Support** : Contacter l'administrateur en cas de doute

## Interface et Navigation

### Flux Utilisateur

```
Page Inscriptions
    ↓
Liste des inscriptions → Clic sur une inscription
    ↓  
Page de détails → Bouton "Modifier"
    ↓
Formulaire de modification → Modifications + "Enregistrer"
    ↓
Retour à la page de détails + Message de confirmation
```

### Messages d'Interface

- **Succès** : *"Les informations de l'inscription ont été mises à jour avec succès."*
- **Erreur de validation** : Messages spécifiques par champ
- **Restriction** : *"La classe ne peut plus être modifiée après validation de l'inscription."*
- **Interdiction** : *"Les inscriptions terminées ne peuvent pas être modifiées."*

## Support Multi-tenant

Le système de modification fonctionne parfaitement en mode SAAS :
- Isolation des données par établissement
- Permissions configurables par tenant  
- Logs séparés par organisation
- Personnalisation des statuts d'affectation selon le contexte local

## Conclusion

La fonctionnalité de modification des inscriptions offre désormais une flexibilité complète pour maintenir les données à jour, tout en respectant les contraintes métier et les permissions utilisateur. L'ajout du statut d'affectation s'intègre parfaitement au workflow existant et respecte le contexte ivoirien du système éducatif.

---

*Documentation mise à jour le 13 septembre 2025*  
*Version : 2.0*  
*Auteur : Système KLASSCI SAAS*