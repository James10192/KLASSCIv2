---
description: Guide complet pour l'implémentation de Choices.js avec style moderne dans ESBTP
globs: resources/views/**/*.blade.php, public/js/**/*.js
alwaysApply: true
---

# Guide d'Implémentation Choices.js - Style Moderne ESBTP

Ce document fournit un guide complet pour implémenter Choices.js avec un style moderne et glassmorphism dans l'application ESBTP, en remplacement de Select2.

## Table des Matières

1. [Introduction et Avantages](#introduction-et-avantages)
2. [Installation et Dépendances](#installation-et-dépendances)
3. [Styles CSS Modernes](#styles-css-modernes)
4. [Configuration JavaScript](#configuration-javascript)
5. [Templates Personnalisés](#templates-personnalisés)
6. [Gestion AJAX](#gestion-ajax)
7. [Événements et Callbacks](#événements-et-callbacks)
8. [Exemples d'Implémentation](#exemples-dimplémentation)
9. [Exemples Avancés DashboardKit](#exemples-avancés-dashboardkit)
10. [Migration depuis Select2](#migration-depuis-select2)
11. [Bonnes Pratiques](#bonnes-pratiques)

## Introduction et Avantages

### Pourquoi Choices.js ?

-   **Performance supérieure** : Plus léger et plus rapide que Select2
-   **Design moderne** : Interface utilisateur plus élégante et responsive
-   **Accessibilité** : Meilleur support des standards d'accessibilité
-   **Personnalisation** : Templates et styles entièrement personnalisables
-   **Sélection multiple** : Interface intuitive style "email recipients"
-   **Recherche avancée** : Recherche en temps réel avec debouncing

### Cas d'Usage Principaux

-   Sélection de parents/tuteurs avec recherche
-   Sélection multiple d'étudiants
-   Choix de matières ou classes
-   Tout select avec recherche et/ou sélection multiple

## Installation et Dépendances

### CDN (Recommandé pour ESBTP)

```html
<!-- CSS -->
<link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"
/>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
```

### NPM (Alternative)

```bash
npm install choices.js
```

## Styles CSS Modernes

### Variables CSS de Base

```css
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    --glass-bg: rgba(255, 255, 255, 0.25);
    --glass-border: rgba(255, 255, 255, 0.18);
    --shadow-soft: 0 8px 32px rgba(31, 38, 135, 0.37);
    --shadow-hover: 0 15px 35px rgba(31, 38, 135, 0.5);
    --border-radius: 16px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
```

### Styles Choices.js Modernes

```css
/* Container principal */
.choices {
    margin-bottom: 0;
    font-size: 14px;
    position: relative;
}

/* Input container avec glassmorphism */
.choices__inner {
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border: 2px solid transparent;
    border-radius: 12px;
    font-size: 14px;
    min-height: 48px;
    padding: 12px 16px 8px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
}

.choices__inner::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--primary-gradient);
    opacity: 0;
    transition: var(--transition);
    z-index: -1;
}

.choices__inner:focus-within {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.choices__inner:focus-within::before {
    opacity: 0.1;
}

/* Dropdown avec glassmorphism */
.choices__list--dropdown {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    z-index: 1050;
    overflow: hidden;
    animation: dropdownSlideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes dropdownSlideIn {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Items dans le dropdown */
.choices__item--selectable {
    padding: 16px 20px;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    border-radius: 8px;
    margin: 4px 8px;
}

.choices__item--selectable::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: var(--primary-gradient);
    transition: var(--transition);
    z-index: -1;
}

.choices__item--selectable:hover {
    color: white;
    transform: translateX(5px);
}

.choices__item--selectable:hover::before {
    left: 0;
}

.choices__item--selectable.is-highlighted {
    background: var(--primary-gradient);
    color: white;
    transform: translateX(5px);
}

/* Tags pour sélection multiple (style email) */
.choices__list--multiple .choices__item {
    background: var(--primary-gradient);
    border: none;
    border-radius: 20px;
    color: white;
    font-size: 13px;
    font-weight: 500;
    margin: 2px 4px 2px 0;
    padding: 6px 12px;
    display: inline-flex;
    align-items: center;
    box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    transition: var(--transition);
    animation: slideInTag 0.3s ease-out;
}

@keyframes slideInTag {
    from {
        opacity: 0;
        transform: translateX(-20px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateX(0) scale(1);
    }
}

.choices__list--multiple .choices__item:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Bouton de suppression des tags */
.choices__button {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    font-size: 12px;
    height: 18px;
    width: 18px;
    margin-left: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
}

.choices__button:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.choices__button:focus {
    outline: 2px solid rgba(255, 255, 255, 0.5);
    outline-offset: 1px;
}

/* Placeholder */
.choices__placeholder {
    color: #8b9dc3;
    opacity: 1;
    font-style: italic;
}

/* Input de recherche */
.choices__input {
    background-color: transparent;
    border: 0;
    font-size: 14px;
    margin-bottom: 0;
    padding: 0;
    color: #2d3748;
}

.choices__input:focus {
    outline: 0;
}

/* États de chargement et messages */
.choices__list--dropdown .choices__item--loading,
.choices__list--dropdown .choices__item--no-results {
    padding: 20px;
    text-align: center;
    color: #8b9dc3;
    font-style: italic;
    background: rgba(139, 157, 195, 0.1);
    border-radius: 8px;
    margin: 8px;
}

/* États d'erreur */
.choices.is-invalid .choices__inner {
    border-color: #fa709a;
    box-shadow: 0 0 0 0.25rem rgba(250, 112, 154, 0.25);
}

/* Responsive */
@media (max-width: 768px) {
    .choices__list--multiple .choices__item {
        font-size: 12px;
        padding: 4px 8px;
        margin: 1px 2px 1px 0;
    }

    .choices__button {
        height: 16px;
        width: 16px;
        font-size: 10px;
    }
}
```

## Configuration JavaScript

### Configuration de Base

```javascript
// Variables globales
let choicesInstances = {};

// Configuration par défaut
const defaultChoicesConfig = {
    searchEnabled: true,
    searchChoices: true,
    searchFloor: 2,
    searchResultLimit: 10,
    shouldSort: false,
    placeholder: true,
    placeholderValue: "Rechercher...",
    noResultsText: "Aucun résultat trouvé",
    noChoicesText: "Aucun choix disponible",
    itemSelectText: "Cliquer pour sélectionner",
    loadingText: "Recherche en cours...",
    removeItemButton: true,
    duplicateItemsAllowed: false,
    maxItemCount: 5,
    searchResultLimit: 10,
    renderChoiceLimit: 10,
};
```

### Fonction d'Initialisation

```javascript
function initializeChoices(selectElement, customConfig = {}) {
    const selectId = selectElement.id;
    console.log("Initialisation de Choices.js pour:", selectId);

    // Vérifier que l'élément existe
    if (!selectElement) {
        console.error("Élément select non trouvé:", selectId);
        return null;
    }

    // Détruire l'instance existante si elle existe
    if (choicesInstances[selectId]) {
        choicesInstances[selectId].destroy();
        delete choicesInstances[selectId];
    }

    // Fusionner la configuration
    const config = { ...defaultChoicesConfig, ...customConfig };

    try {
        // Créer l'instance Choices.js
        const choices = new Choices(selectElement, config);
        choicesInstances[selectId] = choices;
        console.log("Instance Choices.js créée avec succès pour:", selectId);

        return choices;
    } catch (error) {
        console.error(
            "Erreur lors de la création de l'instance Choices.js:",
            error
        );
        return null;
    }
}
```

## Templates Personnalisés

### Template pour Sélection Multiple (Style Email)

```javascript
const emailStyleConfig = {
    callbackOnCreateTemplates: function (template) {
        return {
            item: ({ classNames }, data) => {
                return template(`
                    <div class="${classNames.item} ${
                    data.highlighted
                        ? classNames.highlightedState
                        : classNames.itemSelectable
                }" 
                         data-item data-id="${data.id}" data-value="${
                    data.value
                }" 
                         ${data.active ? 'aria-selected="true"' : ""} 
                         ${data.disabled ? 'aria-disabled="true"' : ""}>
                        <span class="parent-tag">
                            <i class="fas fa-user me-1"></i>
                            ${data.label}
                            ${
                                data.customProperties?.telephone
                                    ? `<small class="text-muted ms-1">(${data.customProperties.telephone})</small>`
                                    : ""
                            }
                        </span>
                        ${
                            !data.disabled
                                ? `<button type="button" class="${classNames.button}" aria-label="Supprimer ${data.label}" data-button><i class="fas fa-times"></i></button>`
                                : ""
                        }
                    </div>
                `);
            },
            choice: ({ classNames }, data) => {
                return template(`
                    <div class="${classNames.item} ${classNames.itemChoice} ${
                    data.disabled
                        ? classNames.itemDisabled
                        : classNames.itemSelectable
                }" 
                         data-select-text="${
                             this.config.itemSelectText
                         }" data-choice 
                         ${
                             data.disabled
                                 ? 'data-choice-disabled aria-disabled="true"'
                                 : "data-choice-selectable"
                         } 
                         data-id="${data.id}" data-value="${data.value}" 
                         ${
                             data.groupId > 0
                                 ? 'role="treeitem"'
                                 : 'role="option"'
                         }>
                        <div class="choice-item">
                            <div class="choice-info">
                                <strong>${data.customProperties?.nom || ""} ${
                    data.customProperties?.prenoms || ""
                }</strong>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-phone me-1"></i>${
                                        data.customProperties?.telephone || ""
                                    }
                                    ${
                                        data.customProperties?.email
                                            ? `<i class="fas fa-envelope ms-2 me-1"></i>${data.customProperties.email}`
                                            : ""
                                    }
                                </small>
                            </div>
                        </div>
                    </div>
                `);
            },
        };
    },
};
```

### Template Simple

```javascript
const simpleConfig = {
    callbackOnCreateTemplates: function (template) {
        return {
            item: ({ classNames }, data) => {
                return template(`
                    <div class="${classNames.item} ${
                    data.highlighted
                        ? classNames.highlightedState
                        : classNames.itemSelectable
                }" 
                         data-item data-id="${data.id}" data-value="${
                    data.value
                }">
                        ${data.label}
                        ${
                            !data.disabled
                                ? `<button type="button" class="${classNames.button}" data-button><i class="fas fa-times"></i></button>`
                                : ""
                        }
                    </div>
                `);
            },
        };
    },
};
```

## Gestion AJAX

### Recherche avec Debouncing

```javascript
function setupAjaxSearch(selectElement, choices, searchUrl) {
    let searchTimeout;

    selectElement.addEventListener("search", function (event) {
        const searchTerm = event.detail.value;
        console.log("Recherche:", searchTerm);

        if (searchTerm.length >= 2) {
            // Effacer le timeout précédent
            clearTimeout(searchTimeout);

            // Définir un nouveau timeout pour éviter trop de requêtes
            searchTimeout = setTimeout(() => {
                performAjaxSearch(searchTerm, choices, searchUrl);
            }, 300);
        }
    });
}

function performAjaxSearch(searchTerm, choicesInstance, url) {
    console.log("Recherche AJAX pour:", searchTerm);

    // Afficher l'état de chargement
    choicesInstance.setChoices(
        [
            {
                value: "",
                label: "Recherche en cours...",
                disabled: true,
            },
        ],
        "value",
        "label",
        true
    );

    const searchUrl = `${url}?q=${encodeURIComponent(searchTerm)}`;

    fetch(searchUrl, {
        method: "GET",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "application/json",
            "X-CSRF-TOKEN": document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content"),
        },
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then((data) => {
            console.log("Données reçues:", data);

            if (
                data.items &&
                Array.isArray(data.items) &&
                data.items.length > 0
            ) {
                // Transformer les données pour Choices.js
                const choices = data.items.map((item) => ({
                    value: item.id,
                    label: item.text || `${item.nom} ${item.prenoms}`,
                    customProperties: {
                        nom: item.nom,
                        prenoms: item.prenoms,
                        telephone: item.telephone,
                        email: item.email,
                    },
                }));

                choicesInstance.setChoices(choices, "value", "label", true);
            } else {
                choicesInstance.setChoices(
                    [
                        {
                            value: "",
                            label: "Aucun résultat trouvé",
                            disabled: true,
                        },
                    ],
                    "value",
                    "label",
                    true
                );
            }
        })
        .catch((error) => {
            console.error("Erreur lors de la recherche:", error);
            choicesInstance.setChoices(
                [
                    {
                        value: "",
                        label: "Erreur lors de la recherche",
                        disabled: true,
                    },
                ],
                "value",
                "label",
                true
            );
        });
}
```

## Événements et Callbacks

### Gestion des Événements

```javascript
function setupChoicesEvents(selectElement, choices) {
    // Événement d'ajout d'élément
    selectElement.addEventListener("addItem", function (event) {
        const selectedItem = event.detail;
        console.log("Élément ajouté:", selectedItem);

        // Logique personnalisée après ajout
        updateHiddenFields(selectElement);
        onItemAdded(selectedItem);
    });

    // Événement de suppression d'élément
    selectElement.addEventListener("removeItem", function (event) {
        const removedItem = event.detail;
        console.log("Élément supprimé:", removedItem);

        // Logique personnalisée après suppression
        updateHiddenFields(selectElement);
        onItemRemoved(removedItem);
    });

    // Événement de changement
    selectElement.addEventListener("change", function (event) {
        console.log("Changement détecté:", event.detail);
        onSelectionChanged(event.detail);
    });
}

// Fonctions de callback personnalisables
function onItemAdded(item) {
    // Logique personnalisée après ajout
}

function onItemRemoved(item) {
    // Logique personnalisée après suppression
}

function onSelectionChanged(detail) {
    // Logique personnalisée lors du changement
}
```

### Mise à Jour des Champs Cachés

```javascript
function updateHiddenFields(selectElement) {
    const container = selectElement.closest(".form-group, .parent-item");
    if (!container) return;

    // Supprimer les anciens champs cachés
    const existingHiddenFields = container.querySelectorAll(
        ".hidden-choice-field"
    );
    existingHiddenFields.forEach((field) => field.remove());

    // Récupérer les valeurs sélectionnées
    const choicesInstance = choicesInstances[selectElement.id];
    if (choicesInstance) {
        const selectedValues = choicesInstance.getValue();

        // Créer des champs cachés pour chaque valeur sélectionnée
        selectedValues.forEach((item, index) => {
            if (item.value) {
                const hiddenField = document.createElement("input");
                hiddenField.type = "hidden";
                hiddenField.name = `${selectElement.name}_items[${index}]`;
                hiddenField.value = item.value;
                hiddenField.className = "hidden-choice-field";
                container.appendChild(hiddenField);
            }
        });
    }
}
```

## Exemples d'Implémentation

### Exemple 1: Sélection Simple avec Recherche

```html
<div class="form-group">
    <label for="classe_select">Sélectionner une classe</label>
    <select id="classe_select" name="classe_id" class="form-control">
        <option value="">Choisir une classe...</option>
    </select>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const classeSelect = document.getElementById("classe_select");

        const choices = initializeChoices(classeSelect, {
            placeholderValue: "Rechercher une classe...",
            searchFloor: 1,
            maxItemCount: 1,
        });

        setupAjaxSearch(
            classeSelect,
            choices,
            '{{ route("esbtp.api.search-classes") }}'
        );
    });
</script>
```

### Exemple 2: Sélection Multiple Style Email

```html
<div class="form-group">
    <label for="parents_select">Sélectionner des parents</label>
    <select
        id="parents_select"
        name="parents[]"
        class="form-control"
        multiple
    ></select>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const parentsSelect = document.getElementById("parents_select");
        parentsSelect.setAttribute("multiple", "multiple");

        const choices = initializeChoices(parentsSelect, {
            ...emailStyleConfig,
            placeholderValue: "Rechercher et sélectionner des parents...",
            maxItemCount: 5,
            removeItemButton: true,
        });

        setupAjaxSearch(
            parentsSelect,
            choices,
            '{{ route("esbtp.api.search-parents") }}'
        );
        setupChoicesEvents(parentsSelect, choices);
    });
</script>
```

### Exemple 3: Select Statique avec Style

```html
<div class="form-group">
    <label for="genre_select">Genre</label>
    <select id="genre_select" name="genre" class="form-control">
        <option value="">Sélectionner...</option>
        <option value="M">Homme</option>
        <option value="F">Femme</option>
    </select>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const genreSelect = document.getElementById("genre_select");

        initializeChoices(genreSelect, {
            searchEnabled: false,
            placeholderValue: "Sélectionner le genre...",
        });
    });
</script>
```

## Exemples Avancés DashboardKit

### Configuration avec Groupes

```javascript
// Select avec groupes d'options
const groupedConfig = {
    placeholderValue: "Choisir une option...",
    searchEnabled: true,
    searchChoices: true,
    searchFloor: 1,
    searchResultLimit: 4,
    renderChoiceLimit: 4,
};

// HTML avec groupes
const groupedHTML = `
<select id="grouped-select" class="form-control">
    <optgroup label="Filières Techniques">
        <option value="genie-civil">Génie Civil</option>
        <option value="mine-geologie">Mine et Géologie</option>
        <option value="topographie">Topographie</option>
    </optgroup>
    <optgroup label="Filières Générales">
        <option value="mathematiques">Mathématiques</option>
        <option value="physique">Physique</option>
        <option value="chimie">Chimie</option>
    </optgroup>
</select>
`;

// Initialisation
const groupedChoices = initializeChoices(
    document.getElementById("grouped-select"),
    groupedConfig
);
```

### Configuration avec Icônes et Badges

```javascript
const iconConfig = {
    callbackOnCreateTemplates: function (template) {
        return {
            item: ({ classNames }, data) => {
                return template(`
                    <div class="${classNames.item} ${
                    data.highlighted
                        ? classNames.highlightedState
                        : classNames.itemSelectable
                }" 
                         data-item data-id="${data.id}" data-value="${
                    data.value
                }">
                        <span class="choice-item-content">
                            ${
                                data.customProperties?.icon
                                    ? `<i class="${data.customProperties.icon} me-2"></i>`
                                    : ""
                            }
                            ${data.label}
                            ${
                                data.customProperties?.badge
                                    ? `<span class="badge bg-${
                                          data.customProperties.badgeColor ||
                                          "primary"
                                      } ms-2">${
                                          data.customProperties.badge
                                      }</span>`
                                    : ""
                            }
                        </span>
                        ${
                            !data.disabled
                                ? `<button type="button" class="${classNames.button}" data-button><i class="fas fa-times"></i></button>`
                                : ""
                        }
                    </div>
                `);
            },
            choice: ({ classNames }, data) => {
                return template(`
                    <div class="${classNames.item} ${classNames.itemChoice} ${
                    data.disabled
                        ? classNames.itemDisabled
                        : classNames.itemSelectable
                }" 
                         data-select-text="${
                             this.config.itemSelectText
                         }" data-choice 
                         ${
                             data.disabled
                                 ? 'data-choice-disabled aria-disabled="true"'
                                 : "data-choice-selectable"
                         } 
                         data-id="${data.id}" data-value="${data.value}">
                        <div class="choice-content d-flex align-items-center">
                            ${
                                data.customProperties?.icon
                                    ? `<i class="${data.customProperties.icon} me-2"></i>`
                                    : ""
                            }
                            <span class="flex-grow-1">${data.label}</span>
                            ${
                                data.customProperties?.badge
                                    ? `<span class="badge bg-${
                                          data.customProperties.badgeColor ||
                                          "primary"
                                      }">${data.customProperties.badge}</span>`
                                    : ""
                            }
                        </div>
                    </div>
                `);
            },
        };
    },
};

// Exemple d'utilisation avec des données enrichies
const enrichedData = [
    {
        value: "admin",
        label: "Administrateur",
        customProperties: {
            icon: "fas fa-user-shield",
            badge: "Admin",
            badgeColor: "danger",
        },
    },
    {
        value: "teacher",
        label: "Enseignant",
        customProperties: {
            icon: "fas fa-chalkboard-teacher",
            badge: "Prof",
            badgeColor: "success",
        },
    },
    {
        value: "student",
        label: "Étudiant",
        customProperties: {
            icon: "fas fa-user-graduate",
            badge: "Élève",
            badgeColor: "info",
        },
    },
];
```

### Configuration avec Limite et Validation

```javascript
const limitedConfig = {
    maxItemCount: 3,
    placeholderValue: "Sélectionner jusqu'à 3 éléments...",
    maxItemText: (maxItemCount) => {
        return `Seulement ${maxItemCount} éléments peuvent être sélectionnés`;
    },
    callbackOnMaxItem: function () {
        // Callback quand la limite est atteinte
        console.log("Limite de sélection atteinte");

        // Afficher une notification
        showNotification(
            "Vous ne pouvez sélectionner que 3 éléments maximum",
            "warning"
        );
    },
};

function showNotification(message, type = "info") {
    // Créer une notification toast
    const toast = document.createElement("div");
    toast.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    toast.style.cssText =
        "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(toast);

    // Auto-remove après 3 secondes
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 3000);
}
```

### Configuration avec Tri Personnalisé

```javascript
const sortedConfig = {
    shouldSort: true,
    sorter: function (a, b) {
        // Tri personnalisé par priorité puis par nom
        const priorityA = a.customProperties?.priority || 0;
        const priorityB = b.customProperties?.priority || 0;

        if (priorityA !== priorityB) {
            return priorityB - priorityA; // Priorité décroissante
        }

        return a.label.localeCompare(b.label); // Puis alphabétique
    },
};

// Données avec priorités
const prioritizedData = [
    { value: "1", label: "Urgent", customProperties: { priority: 3 } },
    { value: "2", label: "Normal", customProperties: { priority: 1 } },
    { value: "3", label: "Important", customProperties: { priority: 2 } },
];
```

### Configuration avec Recherche Avancée

```javascript
const advancedSearchConfig = {
    searchEnabled: true,
    searchChoices: true,
    searchFloor: 1,
    searchResultLimit: 10,
    fuseOptions: {
        includeScore: true,
        threshold: 0.3,
        keys: [
            { name: "label", weight: 0.7 },
            { name: "customProperties.description", weight: 0.3 },
        ],
    },
    callbackOnSearch: function (results, query) {
        console.log(`Recherche pour "${query}":`, results);
        return results;
    },
};
```

### Configuration avec Actions Personnalisées

```javascript
const actionConfig = {
    callbackOnCreateTemplates: function (template) {
        return {
            item: ({ classNames }, data) => {
                return template(`
                    <div class="${classNames.item} ${
                    data.highlighted
                        ? classNames.highlightedState
                        : classNames.itemSelectable
                }" 
                         data-item data-id="${data.id}" data-value="${
                    data.value
                }">
                        <div class="d-flex align-items-center justify-content-between w-100">
                            <span class="choice-label">${data.label}</span>
                            <div class="choice-actions">
                                ${
                                    data.customProperties?.editable
                                        ? '<button type="button" class="btn btn-sm btn-outline-primary me-1" onclick="editItem(\'' +
                                          data.value +
                                          '\')"><i class="fas fa-edit"></i></button>'
                                        : ""
                                }
                                <button type="button" class="${
                                    classNames.button
                                }" data-button><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    </div>
                `);
            },
        };
    },
};

function editItem(value) {
    console.log("Éditer l'élément:", value);
    // Logique d'édition
}
```

### Configuration Responsive Avancée

```javascript
const responsiveConfig = {
    // Configuration de base
    ...defaultChoicesConfig,

    // Adaptation selon la taille d'écran
    callbackOnInit: function () {
        const updateConfig = () => {
            const isMobile = window.innerWidth <= 768;
            const isTablet = window.innerWidth <= 1024;

            if (isMobile) {
                this.config.searchResultLimit = 5;
                this.config.maxItemCount = 3;
                this.config.placeholderValue = "Rechercher...";
            } else if (isTablet) {
                this.config.searchResultLimit = 8;
                this.config.maxItemCount = 5;
            } else {
                this.config.searchResultLimit = 10;
                this.config.maxItemCount = 10;
            }
        };

        updateConfig();
        window.addEventListener("resize", updateConfig);
    },
};
```

### Configuration avec Validation en Temps Réel

```javascript
const validationConfig = {
    callbackOnAddItem: function (item) {
        // Validation lors de l'ajout
        if (!validateItem(item)) {
            this.removeActiveItemsByValue(item.value);
            showValidationError("Cet élément n'est pas valide");
            return false;
        }

        // Mise à jour de l'état de validation du formulaire
        updateFormValidation();
        return true;
    },

    callbackOnRemoveItem: function (item) {
        // Validation lors de la suppression
        updateFormValidation();
        return true;
    },
};

function validateItem(item) {
    // Logique de validation personnalisée
    if (item.value === "invalid") {
        return false;
    }

    // Autres règles de validation
    return true;
}

function updateFormValidation() {
    // Mettre à jour l'état de validation du formulaire
    const form = document.querySelector("form");
    const isValid = validateForm();

    if (isValid) {
        form.classList.remove("was-validated");
    } else {
        form.classList.add("was-validated");
    }
}
```

### Exemple Complet avec Toutes les Fonctionnalités

```html
<div class="form-group">
    <label for="advanced_select">Sélection Avancée</label>
    <select
        id="advanced_select"
        name="advanced[]"
        class="form-control"
        multiple
    >
        <!-- Options seront chargées dynamiquement -->
    </select>
    <div class="invalid-feedback">
        Veuillez sélectionner au moins un élément.
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const advancedSelect = document.getElementById("advanced_select");

        // Configuration complète
        const fullConfig = {
            ...defaultChoicesConfig,
            ...iconConfig,
            ...limitedConfig,
            ...validationConfig,
            maxItemCount: 5,
            placeholderValue: "Rechercher et sélectionner...",
            removeItemButton: true,
            duplicateItemsAllowed: false,

            // Messages personnalisés
            noResultsText: "Aucun résultat trouvé pour votre recherche",
            noChoicesText: "Aucune option disponible",
            itemSelectText: "Cliquer pour sélectionner",
            loadingText: "Chargement des données...",
            maxItemText: (maxItemCount) => `Maximum ${maxItemCount} éléments`,

            // Callbacks avancés
            callbackOnInit: function () {
                console.log("Choices.js initialisé");
                loadInitialData();
            },

            callbackOnCreateTemplates: function (template) {
                return {
                    item: ({ classNames }, data) => {
                        return template(`
                        <div class="${classNames.item} ${
                            data.highlighted
                                ? classNames.highlightedState
                                : classNames.itemSelectable
                        }" 
                             data-item data-id="${data.id}" data-value="${
                            data.value
                        }">
                            <div class="choice-item-wrapper">
                                ${
                                    data.customProperties?.avatar
                                        ? `<img src="${data.customProperties.avatar}" class="choice-avatar me-2" alt="">`
                                        : ""
                                }
                                ${
                                    data.customProperties?.icon
                                        ? `<i class="${data.customProperties.icon} me-2"></i>`
                                        : ""
                                }
                                <div class="choice-content">
                                    <div class="choice-title">${
                                        data.label
                                    }</div>
                                    ${
                                        data.customProperties?.subtitle
                                            ? `<small class="choice-subtitle text-muted">${data.customProperties.subtitle}</small>`
                                            : ""
                                    }
                                </div>
                                ${
                                    data.customProperties?.badge
                                        ? `<span class="badge bg-${
                                              data.customProperties
                                                  .badgeColor || "primary"
                                          } ms-auto">${
                                              data.customProperties.badge
                                          }</span>`
                                        : ""
                                }
                            </div>
                            ${
                                !data.disabled
                                    ? `<button type="button" class="${classNames.button}" data-button><i class="fas fa-times"></i></button>`
                                    : ""
                            }
                        </div>
                    `);
                    },
                    choice: ({ classNames }, data) => {
                        return template(`
                        <div class="${classNames.item} ${
                            classNames.itemChoice
                        } ${
                            data.disabled
                                ? classNames.itemDisabled
                                : classNames.itemSelectable
                        }" 
                             data-select-text="${
                                 this.config.itemSelectText
                             }" data-choice 
                             ${
                                 data.disabled
                                     ? 'data-choice-disabled aria-disabled="true"'
                                     : "data-choice-selectable"
                             } 
                             data-id="${data.id}" data-value="${data.value}">
                            <div class="choice-option-wrapper">
                                ${
                                    data.customProperties?.avatar
                                        ? `<img src="${data.customProperties.avatar}" class="choice-avatar me-2" alt="">`
                                        : ""
                                }
                                ${
                                    data.customProperties?.icon
                                        ? `<i class="${data.customProperties.icon} me-2"></i>`
                                        : ""
                                }
                                <div class="choice-content flex-grow-1">
                                    <div class="choice-title">${
                                        data.label
                                    }</div>
                                    ${
                                        data.customProperties?.subtitle
                                            ? `<small class="choice-subtitle text-muted">${data.customProperties.subtitle}</small>`
                                            : ""
                                    }
                                    ${
                                        data.customProperties?.description
                                            ? `<div class="choice-description text-muted mt-1">${data.customProperties.description}</div>`
                                            : ""
                                    }
                                </div>
                                ${
                                    data.customProperties?.badge
                                        ? `<span class="badge bg-${
                                              data.customProperties
                                                  .badgeColor || "primary"
                                          }">${
                                              data.customProperties.badge
                                          }</span>`
                                        : ""
                                }
                            </div>
                        </div>
                    `);
                    },
                };
            },
        };

        // Initialiser Choices.js
        const choices = initializeChoices(advancedSelect, fullConfig);

        // Configurer la recherche AJAX
        setupAjaxSearch(
            advancedSelect,
            choices,
            '{{ route("esbtp.api.advanced-search") }}'
        );

        // Configurer les événements
        setupChoicesEvents(advancedSelect, choices);

        function loadInitialData() {
            // Charger des données initiales si nécessaire
            fetch('{{ route("esbtp.api.initial-data") }}')
                .then((response) => response.json())
                .then((data) => {
                    if (data.items) {
                        choices.setChoices(data.items, "value", "label", false);
                    }
                })
                .catch((error) =>
                    console.error("Erreur lors du chargement initial:", error)
                );
        }
    });
</script>
```

### Styles CSS Supplémentaires pour les Exemples Avancés

```css
/* Styles pour les avatars et contenus enrichis */
.choice-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    object-fit: cover;
}

.choice-item-wrapper,
.choice-option-wrapper {
    display: flex;
    align-items: center;
    width: 100%;
    gap: 8px;
}

.choice-content {
    flex-grow: 1;
    min-width: 0; /* Pour permettre l'ellipsis */
}

.choice-title {
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.choice-subtitle {
    font-size: 0.75rem;
    line-height: 1.2;
}

.choice-description {
    font-size: 0.75rem;
    line-height: 1.3;
    max-height: 2.6em;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

/* Animations pour les éléments enrichis */
.choices__item--selectable:hover .choice-avatar {
    transform: scale(1.1);
    transition: transform 0.2s ease;
}

.choices__item--selectable:hover .badge {
    transform: scale(1.05);
    transition: transform 0.2s ease;
}

/* Responsive pour les contenus enrichis */
@media (max-width: 768px) {
    .choice-description {
        display: none;
    }

    .choice-subtitle {
        font-size: 0.7rem;
    }

    .choice-avatar {
        width: 20px;
        height: 20px;
    }
}
```

## Migration depuis Select2

### Étapes de Migration

1. **Remplacer les CDN**

    ```html
    <!-- Supprimer Select2 -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" /> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> -->

    <!-- Ajouter Choices.js -->
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
    ```

2. **Remplacer l'initialisation**

    ```javascript
    // Ancien code Select2
    $("#my-select").select2({
        placeholder: "Rechercher...",
        ajax: {
            url: "/api/search",
            dataType: "json",
        },
    });

    // Nouveau code Choices.js
    const mySelect = document.getElementById("my-select");
    const choices = initializeChoices(mySelect, {
        placeholderValue: "Rechercher...",
    });
    setupAjaxSearch(mySelect, choices, "/api/search");
    ```

3. **Adapter les événements**

    ```javascript
    // Select2
    $("#my-select").on("select2:select", function (e) {
        // Logique
    });

    // Choices.js
    document
        .getElementById("my-select")
        .addEventListener("addItem", function (e) {
            // Logique
        });
    ```

### Correspondances des Options

| Select2                  | Choices.js                 | Description                   |
| ------------------------ | -------------------------- | ----------------------------- |
| `placeholder`            | `placeholderValue`         | Texte du placeholder          |
| `allowClear`             | `removeItemButton`         | Bouton de suppression         |
| `multiple`               | Attribut HTML `multiple`   | Sélection multiple            |
| `minimumInputLength`     | `searchFloor`              | Caractères min pour recherche |
| `maximumSelectionLength` | `maxItemCount`             | Nombre max d'éléments         |
| `ajax.url`               | Fonction `setupAjaxSearch` | Recherche AJAX                |

## Bonnes Pratiques

### Performance

1. **Réutiliser les instances**

    ```javascript
    // Stocker les instances pour éviter les fuites mémoire
    let choicesInstances = {};

    function destroyChoices(selectId) {
        if (choicesInstances[selectId]) {
            choicesInstances[selectId].destroy();
            delete choicesInstances[selectId];
        }
    }
    ```

2. **Debouncing pour AJAX**
    ```javascript
    // Toujours utiliser un timeout pour éviter trop de requêtes
    let searchTimeout;
    searchTimeout = setTimeout(() => {
        performAjaxSearch(searchTerm, choices, url);
    }, 300);
    ```

### Accessibilité

1. **Labels appropriés**

    ```html
    <label for="my-select">Sélection obligatoire</label>
    <select id="my-select" aria-required="true"></select>
    ```

2. **Messages d'erreur**
    ```javascript
    const choices = initializeChoices(selectElement, {
        noResultsText: "Aucun résultat trouvé",
        noChoicesText: "Aucun choix disponible",
        loadingText: "Chargement en cours...",
    });
    ```

### Validation

1. **Validation côté client**

    ```javascript
    function validateChoices(selectElement) {
        const choices = choicesInstances[selectElement.id];
        const selectedValues = choices.getValue();

        if (
            selectElement.hasAttribute("required") &&
            selectedValues.length === 0
        ) {
            selectElement.classList.add("is-invalid");
            return false;
        }

        selectElement.classList.remove("is-invalid");
        return true;
    }
    ```

2. **Intégration avec les formulaires**

    ```javascript
    document.querySelector("form").addEventListener("submit", function (e) {
        let isValid = true;

        document.querySelectorAll("select[data-choices]").forEach((select) => {
            if (!validateChoices(select)) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
    ```

### Responsive Design

1. **Adaptation mobile**

    ```css
    @media (max-width: 768px) {
        .choices__inner {
            min-height: 44px; /* Taille tactile minimum */
            font-size: 16px; /* Éviter le zoom sur iOS */
        }
    }
    ```

2. **Gestion des petits écrans**
    ```javascript
    const isMobile = window.innerWidth <= 768;
    const config = {
        ...defaultChoicesConfig,
        searchResultLimit: isMobile ? 5 : 10,
        maxItemCount: isMobile ? 3 : 5,
    };
    ```

## Dépannage

### Problèmes Courants

1. **Instance non détruite**

    ```javascript
    // Toujours détruire avant de recréer
    if (choicesInstances[selectId]) {
        choicesInstances[selectId].destroy();
    }
    ```

2. **AJAX ne fonctionne pas**

    ```javascript
    // Vérifier le token CSRF
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
    ```

3. **Styles non appliqués**
    ```html
    <!-- S'assurer que le CSS est chargé après Choices.js -->
    <link rel="stylesheet" href="choices.min.css" />
    <link rel="stylesheet" href="custom-choices.css" />
    ```

### Debug

```javascript
// Fonction de debug
function debugChoices() {
    console.log("Instances actives:", choicesInstances);
    Object.keys(choicesInstances).forEach((id) => {
        const instance = choicesInstances[id];
        console.log(`${id}:`, {
            config: instance.config,
            currentState: instance.currentState,
            isActive: instance.isActive,
        });
    });
}

// Exposer globalement pour debug
window.debugChoices = debugChoices;
```

---

Cette documentation fournit tout ce qui est nécessaire pour implémenter Choices.js avec un style moderne dans l'application ESBTP. Référez-vous aux exemples spécifiques selon vos besoins et n'hésitez pas à adapter les styles et configurations selon le contexte d'utilisation.
