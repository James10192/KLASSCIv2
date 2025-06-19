@extends('layouts.app')

@section('title', 'Nouvelle Inscription')

@push('styles')
<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<style>
    /* Variables CSS pour la cohérence */
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

    /* Glassmorphism pour les conteneurs principaux */
    .card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow-soft);
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    /* Styles ultra-modernes pour Choices.js */
    .choices {
        margin-bottom: 0;
        font-size: 14px;
        position: relative;
    }

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
        content: '';
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

    .choices__item--selectable {
        padding: 16px 20px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        margin: 4px 8px;
    }

    .choices__item--selectable::before {
        content: '';
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

    .choices__placeholder {
        color: #8b9dc3;
        opacity: 1;
        font-style: italic;
    }

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

    /* Bouton d'ajout ultra-moderne avec glassmorphism */
    .add-parent-container {
        display: flex;
        justify-content: center;
        margin: 3rem 0;
        padding: 2rem;
        background: var(--glass-bg);
        backdrop-filter: blur(15px);
        border-radius: 24px;
        border: 2px dashed rgba(102, 126, 234, 0.3);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .add-parent-container::before {
        content: '';
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

    .add-parent-container:hover {
        border-color: #667eea;
        transform: translateY(-5px);
        box-shadow: var(--shadow-hover);
    }

    .add-parent-container:hover::before {
        opacity: 0.1;
    }

    .btn-add-parent {
        background: var(--primary-gradient);
        border: none;
        color: white;
        padding: 16px 40px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 16px;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: var(--transition);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }

    .btn-add-parent::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transition: var(--transition);
        transform: translate(-50%, -50%);
    }

    .btn-add-parent:hover::before {
        width: 300px;
        height: 300px;
    }

    .btn-add-parent:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.6);
        color: white;
    }

    .btn-add-parent:active {
        transform: translateY(-1px) scale(1.02);
    }

    .btn-add-parent i {
        margin-right: 12px;
        font-size: 18px;
        transition: var(--transition);
        position: relative;
        z-index: 1;
    }

    .btn-add-parent:hover i {
        transform: rotate(180deg) scale(1.2);
    }

    /* Boutons de suppression stylés */
    .remove-parent {
        background: var(--danger-gradient);
        border: none;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: 500;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
        position: relative;
        overflow: hidden;
    }

    .remove-parent::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        transition: var(--transition);
        transform: translate(-50%, -50%);
    }

    .remove-parent:hover::before {
        width: 200px;
        height: 200px;
    }

    .remove-parent:hover {
        transform: translateY(-2px) scale(1.05);
        box-shadow: 0 8px 25px rgba(250, 112, 154, 0.6);
        color: white;
    }

    /* Cartes de parents avec effet glassmorphism */
    .parent-item {
        transition: var(--transition);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--shadow-soft);
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        position: relative;
    }

    .parent-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-gradient);
        transform: scaleX(0);
        transition: var(--transition);
    }

    .parent-item:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: var(--shadow-hover);
    }

    .parent-item:hover::before {
        transform: scaleX(1);
    }

    .parent-item .card-body {
        padding: 2rem;
        background: transparent;
    }

    /* Titres avec effet néon */
    .section-title {
        color: #2d3748;
        font-weight: 700;
        margin-bottom: 1.5rem;
        position: relative;
        padding-left: 20px;
        font-size: 1.5rem;
    }

    .section-title::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 30px;
        background: var(--primary-gradient);
        border-radius: 3px;
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }

    .section-title::after {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 6px;
        height: 30px;
        background: var(--primary-gradient);
        border-radius: 3px;
        filter: blur(10px);
        opacity: 0.7;
    }

    /* Checkboxes personnalisées */
    .form-check-input {
        width: 20px;
        height: 20px;
        border: 2px solid #667eea;
        border-radius: 6px;
        transition: var(--transition);
        position: relative;
    }

    .form-check-input:checked {
        background: var(--primary-gradient);
        border-color: #667eea;
        box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
    }

    .form-check-input:focus {
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }

    .form-check-label {
        font-weight: 500;
        color: #4a5568;
        margin-left: 8px;
        transition: var(--transition);
    }

    .form-check:hover .form-check-label {
        color: #667eea;
    }

    /* Inputs avec effet glassmorphism */
    .form-control {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border: 2px solid transparent;
        border-radius: 12px;
        padding: 12px 16px;
        transition: var(--transition);
        font-size: 14px;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.95);
        border-color: #667eea;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    /* Labels stylés */
    .form-label {
        font-weight: 600;
        color: #4a5568;
        margin-bottom: 8px;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* États d'erreur avec style moderne */
    .choices.is-invalid .choices__inner {
        border-color: #fa709a;
        box-shadow: 0 0 0 0.25rem rgba(250, 112, 154, 0.25);
    }

    .form-control.is-invalid {
        border-color: #fa709a;
        box-shadow: 0 0 0 0.25rem rgba(250, 112, 154, 0.25);
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

    /* Animations avancées */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }

    .parent-item {
        animation: slideInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-add-parent {
        animation: float 3s ease-in-out infinite;
    }

    /* Boutons principaux stylés */
    .btn-primary {
        background: var(--primary-gradient);
        border: none;
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        transition: var(--transition);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        position: relative;
        overflow: hidden;
    }

    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.5s;
    }

    .btn-primary:hover::before {
        left: 100%;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.6);
        color: white;
    }

    .btn-secondary {
        background: rgba(108, 117, 125, 0.1);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(108, 117, 125, 0.3);
        border-radius: 12px;
        padding: 12px 24px;
        font-weight: 600;
        transition: var(--transition);
        color: #6c757d;
    }

    .btn-secondary:hover {
        background: rgba(108, 117, 125, 0.2);
        transform: translateY(-2px);
        color: #495057;
    }

    /* Alertes modernes */
    .alert {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        border: 1px solid var(--glass-border);
        border-radius: var(--border-radius);
        border-left: 4px solid;
        box-shadow: var(--shadow-soft);
    }

    .alert-info {
        border-left-color: #4facfe;
        background: rgba(79, 172, 254, 0.1);
    }

    .alert-warning {
        border-left-color: #fee140;
        background: rgba(254, 225, 64, 0.1);
    }

    .alert-danger {
        border-left-color: #fa709a;
        background: rgba(250, 112, 154, 0.1);
    }

    .alert-success {
        border-left-color: #00f2fe;
        background: rgba(0, 242, 254, 0.1);
    }

    /* Responsive design amélioré */
    @media (max-width: 768px) {
        .add-parent-container {
            margin: 2rem 0;
            padding: 1.5rem;
        }

        .btn-add-parent {
            padding: 14px 30px;
            font-size: 14px;
        }

        .section-title {
            font-size: 1.25rem;
        }

        .parent-item .card-body {
            padding: 1.5rem;
        }
    }

    /* Micro-interactions pour les icônes */
    .fas, .far {
        transition: var(--transition);
    }

    .card-title .fas:hover {
        transform: scale(1.2) rotate(5deg);
        color: #667eea;
    }

    /* Effet de survol pour les conteneurs */
    .container-fluid {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        padding: 2rem 0;
    }

    /* Scrollbar personnalisée */
    ::-webkit-scrollbar {
        width: 8px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: var(--primary-gradient);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #667eea 100%);
    }

    /* Styles pour les tags de parents (comme destinataires d'email) */
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

    .choices__list--multiple .choices__item .parent-tag {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .choices__list--multiple .choices__item .parent-tag small {
        opacity: 0.8;
        font-size: 11px;
    }

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

    /* Styles pour les choix dans le dropdown */
    .parent-choice-item {
        padding: 8px 0;
    }

    .parent-choice-item .parent-info {
        line-height: 1.3;
    }

    .parent-choice-item strong {
        color: #2d3748;
        font-size: 14px;
    }

    .parent-choice-item small {
        color: #6c757d;
        font-size: 12px;
    }

    /* Animation pour les choix au survol */
    .choices__item--choice.parent-choice-item:hover {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    }

    /* Amélioration du conteneur principal */
    .choices__inner {
        min-height: 48px;
        padding: 8px 12px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 4px;
    }

    .choices__list--multiple {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 2px;
        margin: 0;
    }

    /* Style pour le champ de saisie */
    .choices__input--cloned {
        background: transparent;
        border: none;
        font-size: 14px;
        min-width: 150px;
        padding: 4px 0;
    }

    .choices__input--cloned:focus {
        outline: none;
    }

    /* Placeholder amélioré */
    .choices__placeholder {
        color: #8b9dc3;
        font-style: italic;
        margin: 0;
        padding: 4px 0;
    }

    /* Responsive pour mobile */
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

        .choices__input--cloned {
            min-width: 100px;
        }
    }

    /* Overlay du modal premium : fond clair, pas de noir, pas de blur */
    .modal-backdrop.show {
        background-color: rgba(255,255,255,0.18) !important; /* Overlay lumineux */
        backdrop-filter: none !important;
    }

    /* Modal premium : effet glassmorphism sur le contenu uniquement */
    .modal-content {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-radius: 22px;
        box-shadow: 0 8px 32px 0 rgba(4,83,203,0.18);
        border: 1px solid rgba(255,255,255,0.18);
    }

    @media (max-width: 768px) {
        .modal-content {
            border-radius: 14px;
            padding: 0.5rem;
        }
    }

    /* Overlay du modal : version radicale, priorité maximale */
    body .modal-backdrop.show {
        background-color: transparent !important;
        backdrop-filter: none !important;
        box-shadow: none !important;
        opacity: 1 !important;
        z-index: 1050 !important;
    }
    /* Pour test ultime, décommente si besoin :
    body .modal-backdrop.show {
        display: none !important;
    }
    */

    /* Forcer le z-index du modal Bootstrap et de l'overlay */
    .modal {
        z-index: 2000 !important;
    }
    .modal-backdrop.show {
        z-index: 1999 !important;
    }

    /* Overlay du modal custom */
    #modalCustomOverlay {
      display: none;
      position: fixed;
      z-index: 3000;
      left: 0; top: 0; right: 0; bottom: 0;
      background: rgba(4,83,203,0.13);
      backdrop-filter: blur(2px);
      align-items: center;
      justify-content: center;
      transition: opacity 0.2s;
    }
    #modalCustomOverlay.active {
      display: flex;
      opacity: 1;
    }
    #modalCustomContent {
      background: rgba(255,255,255,0.98);
      border-radius: 2.2rem;
      box-shadow: 0 8px 32px 0 rgba(4,83,203,0.18);
      max-width: 700px;
      width: 98vw;
      padding: 2.5rem 2rem 2rem 2rem;
      position: relative;
      animation: modalIn 0.25s cubic-bezier(.4,2,.6,1) both;
    }
    @keyframes modalIn {
      from { transform: translateY(40px) scale(0.98); opacity: 0; }
      to { transform: none; opacity: 1; }
    }
    #modalCustomOverlay .modal-close-btn {
      position: absolute;
      top: 1.2rem; right: 1.2rem;
      background: #0453cb;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 38px; height: 38px;
      font-size: 1.3rem;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 2px 8px rgba(4,83,203,0.10);
      cursor: pointer;
      transition: background 0.2s;
    }
    #modalCustomOverlay .modal-close-btn:hover {
      background: #1b64d4;
    }
    @media (max-width: 600px) {
      #modalCustomContent { padding: 1.2rem 0.5rem; }
    }
    body.modal-open-custom {
      overflow: hidden !important;
    }
</style>
@endpush

@push('scripts')
<!-- Choices.js -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

<script>
    // Variables globales pour les instances Choices.js
    let parentChoicesInstances = {};
    let parentCounter = 0;

    // Configuration par défaut pour Choices.js
    const defaultChoicesConfig = {
        searchEnabled: true,
        searchChoices: true,
        searchFloor: 2,
        searchResultLimit: 10,
        shouldSort: false,
        placeholder: true,
        placeholderValue: 'Rechercher un parent...',
        noResultsText: 'Aucun parent trouvé',
        noChoicesText: 'Aucun parent disponible',
        itemSelectText: 'Cliquer pour sélectionner',
        loadingText: 'Recherche en cours...',
        addItemText: (value) => `Appuyer sur Entrée pour ajouter <b>"${value}"</b>`,
        maxItemText: (maxItemCount) => `Seulement ${maxItemCount} éléments peuvent être ajoutés`,
        uniqueItemText: 'Seules des valeurs uniques peuvent être ajoutées',
        customAddItemText: 'Seules des valeurs correspondant à des conditions spécifiques peuvent être ajoutées',
        classNames: {
            containerOuter: 'choices',
            containerInner: 'choices__inner',
            input: 'choices__input',
            inputCloned: 'choices__input--cloned',
            list: 'choices__list',
            listItems: 'choices__list--multiple',
            listSingle: 'choices__list--single',
            listDropdown: 'choices__list--dropdown',
            item: 'choices__item',
            itemSelectable: 'choices__item--selectable',
            itemDisabled: 'choices__item--disabled',
            itemChoice: 'choices__item--choice',
            placeholder: 'choices__placeholder',
            group: 'choices__group',
            groupHeading: 'choices__heading',
            button: 'choices__button',
            activeState: 'is-active',
            focusState: 'is-focused',
            openState: 'is-open',
            disabledState: 'is-disabled',
            highlightedState: 'is-highlighted',
            selectedState: 'is-selected',
            flippedState: 'is-flipped',
            loadingState: 'is-loading',
            noResults: 'has-no-results',
            noChoices: 'has-no-choices'
        }
    };

    // Fonction pour initialiser Choices.js sur un élément select
    function initializeParentChoice(selectElement) {
        const selectId = selectElement.id;
        console.log('=== Initialisation de Choices.js pour:', selectId);

        // Vérifier que l'élément existe
        if (!selectElement) {
            console.error('Élément select non trouvé:', selectId);
            return null;
        }

        // Détruire l'instance existante si elle existe
        if (parentChoicesInstances[selectId]) {
            console.log('Destruction de l\'instance existante pour:', selectId);
            parentChoicesInstances[selectId].destroy();
            delete parentChoicesInstances[selectId];
        }

        // Ajouter l'attribut multiple pour permettre la sélection multiple
        selectElement.setAttribute('multiple', 'multiple');

        // Configuration pour un comportement similaire aux destinataires d'email
        const config = {
            searchEnabled: true,
            searchChoices: true,
            searchFloor: 2,
            searchResultLimit: 10,
            shouldSort: false,
            placeholder: true,
            placeholderValue: 'Rechercher et sélectionner des parents...',
            noResultsText: 'Aucun parent trouvé',
            noChoicesText: 'Aucun parent disponible',
            itemSelectText: 'Cliquer pour sélectionner',
            loadingText: 'Recherche en cours...',
            removeItemButton: true, // Permet de supprimer les éléments sélectionnés
            duplicateItemsAllowed: false, // Évite les doublons
            maxItemCount: 5, // Limite le nombre de parents sélectionnables
            searchResultLimit: 10,
            renderChoiceLimit: 10,
            callbackOnCreateTemplates: function(template) {
                return {
                    item: ({ classNames }, data) => {
                        return template(`
                            <div class="${classNames.item} ${data.highlighted ? classNames.highlightedState : classNames.itemSelectable}" data-item data-id="${data.id}" data-value="${data.value}" ${data.active ? 'aria-selected="true"' : ''} ${data.disabled ? 'aria-disabled="true"' : ''}>
                                <span class="parent-tag">
                                    <i class="fas fa-user me-1"></i>
                                    ${data.label}
                                    ${data.customProperties?.telephone ? `<small class="text-muted ms-1">(${data.customProperties.telephone})</small>` : ''}
                                </span>
                                ${!data.disabled ? `<button type="button" class="${classNames.button}" aria-label="Supprimer ${data.label}" data-button><i class="fas fa-times"></i></button>` : ''}
                            </div>
                        `);
                    },
                    choice: ({ classNames }, data) => {
                        return template(`
                            <div class="${classNames.item} ${classNames.itemChoice} ${data.disabled ? classNames.itemDisabled : classNames.itemSelectable}" data-select-text="${this.config.itemSelectText}" data-choice ${data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable'} data-id="${data.id}" data-value="${data.value}" ${data.groupId > 0 ? 'role="treeitem"' : 'role="option"'}>
                                <div class="parent-choice-item">
                                    <div class="parent-info">
                                        <strong>${data.customProperties?.nom || ''} ${data.customProperties?.prenoms || ''}</strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-phone me-1"></i>${data.customProperties?.telephone || ''}
                                            ${data.customProperties?.email ? `<i class="fas fa-envelope ms-2 me-1"></i>${data.customProperties.email}` : ''}
                                        </small>
                                    </div>
                                </div>
                            </div>
                        `);
                    }
                };
            }
        };

        console.log('Configuration Choices.js:', config);

        try {
            // Créer l'instance Choices.js
            const choices = new Choices(selectElement, config);
            parentChoicesInstances[selectId] = choices;
            console.log('Instance Choices.js créée avec succès pour:', selectId);

            // Gérer la recherche asynchrone
            let searchTimeout;
            selectElement.addEventListener('search', function(event) {
                const searchTerm = event.detail.value;
                console.log('Événement search déclenché:', { selectId, searchTerm });

                if (searchTerm.length >= 2) {
                    // Effacer le timeout précédent
                    clearTimeout(searchTimeout);

                    // Définir un nouveau timeout pour éviter trop de requêtes
                    searchTimeout = setTimeout(() => {
                        console.log('Lancement de la recherche pour:', searchTerm);
                        searchParents(searchTerm, choices);
                    }, 300);
                } else {
                    console.log('Terme de recherche trop court:', searchTerm);
                }
            });

            // Gérer la sélection
            selectElement.addEventListener('addItem', function(event) {
                const selectedParent = event.detail;
                console.log('Parent ajouté:', selectedParent);

                // Mettre à jour les champs cachés pour tous les parents sélectionnés
                const parentContainer = selectElement.closest('.parent-item');
                if (parentContainer) {
                    updateHiddenParentFields(parentContainer, selectElement);
                }
            });

            // Gérer la suppression
            selectElement.addEventListener('removeItem', function(event) {
                const removedParent = event.detail;
                console.log('Parent supprimé:', removedParent);

                // Mettre à jour les champs cachés après suppression
                const parentContainer = selectElement.closest('.parent-item');
                if (parentContainer) {
                    updateHiddenParentFields(parentContainer, selectElement);
                }
            });

            return choices;
        } catch (error) {
            console.error('Erreur lors de la création de l\'instance Choices.js:', error);
            return null;
        }
    }

    // Fonction pour rechercher les parents via AJAX
    function searchParents(searchTerm, choicesInstance) {
        console.log('=== Début de la recherche de parents ===');
        console.log('Terme de recherche:', searchTerm);
        console.log('Instance Choices.js:', choicesInstance);

        // Vérifier que l'instance Choices.js existe
        if (!choicesInstance) {
            console.error('Instance Choices.js non fournie');
            return;
        }

        // Afficher l'état de chargement
        try {
            choicesInstance.setChoices([{
                value: '',
                label: 'Recherche en cours...',
                disabled: true
            }], 'value', 'label', true);
            console.log('État de chargement affiché');
        } catch (error) {
            console.error('Erreur lors de l\'affichage de l\'état de chargement:', error);
        }

        const url = `{{ route('esbtp.api.search-parents') }}?q=${encodeURIComponent(searchTerm)}`;
        console.log('URL de la requête:', url);

        fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            console.log('Réponse reçue:', response);
            console.log('Status:', response.status);
            console.log('Headers:', response.headers);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('=== Données reçues ===');
            console.log('Data complète:', data);
            console.log('Type de data:', typeof data);
            console.log('Data.items:', data.items);

            if (data.items && Array.isArray(data.items) && data.items.length > 0) {
                console.log('Nombre de parents trouvés:', data.items.length);

                // Transformer les données pour Choices.js
                const choices = data.items.map(parent => {
                    console.log('Parent à transformer:', parent);
                    return {
                        value: parent.id,
                        label: parent.text || `${parent.nom} ${parent.prenoms} (${parent.telephone})`,
                        customProperties: {
                            details: `${parent.telephone || ''} ${parent.email ? '• ' + parent.email : ''}`.trim(),
                            nom: parent.nom,
                            prenoms: parent.prenoms,
                            telephone: parent.telephone
                        }
                    };
                });

                console.log('Choices transformés:', choices);

                // Mettre à jour les choix
                try {
                    choicesInstance.setChoices(choices, 'value', 'label', true);
                    console.log('Choix mis à jour avec succès');
                } catch (error) {
                    console.error('Erreur lors de la mise à jour des choix:', error);
                }
            } else {
                console.log('Aucun parent trouvé');
                // Aucun résultat trouvé
                try {
                    choicesInstance.setChoices([{
                        value: '',
                        label: 'Aucun parent trouvé',
                        disabled: true
                    }], 'value', 'label', true);
                } catch (error) {
                    console.error('Erreur lors de l\'affichage du message "aucun résultat":', error);
                }
            }
        })
        .catch(error => {
            console.error('=== Erreur lors de la recherche ===');
            console.error('Erreur:', error);
            console.error('Stack trace:', error.stack);

            try {
                choicesInstance.setChoices([{
                    value: '',
                    label: 'Erreur lors de la recherche',
                    disabled: true
                }], 'value', 'label', true);
            } catch (setChoicesError) {
                console.error('Erreur lors de l\'affichage du message d\'erreur:', setChoicesError);
            }
        });
    }

    // Fonction pour mettre à jour les champs cachés
    function updateParentFields(selectElement, selectedParent) {
        const parentContainer = selectElement.closest('.parent-item');
        if (parentContainer) {
            // Mettre à jour le type de parent
            const typeInput = parentContainer.querySelector('input[name*="[type]"]');
            if (typeInput) {
                typeInput.value = 'existant';
            }

            // Cacher les champs de nouveau parent
            const newParentFields = parentContainer.querySelector('.parent-nouveau-section');
            if (newParentFields) {
                newParentFields.style.display = 'none';
            }

            // Créer ou mettre à jour les champs cachés pour les parents sélectionnés
            updateHiddenParentFields(parentContainer, selectElement);
        }
    }

    // Fonction pour mettre à jour les champs cachés avec tous les parents sélectionnés
    function updateHiddenParentFields(parentContainer, selectElement) {
        // Supprimer les anciens champs cachés
        const existingHiddenFields = parentContainer.querySelectorAll('.hidden-parent-field');
        existingHiddenFields.forEach(field => field.remove());

        // Récupérer tous les parents sélectionnés
        const choicesInstance = parentChoicesInstances[selectElement.id];
        if (choicesInstance) {
            const selectedValues = choicesInstance.getValue();
            console.log('Parents sélectionnés:', selectedValues);

            // Créer des champs cachés pour chaque parent sélectionné
            selectedValues.forEach((parent, index) => {
                if (parent.value) {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = `parents[${index}][parent_id]`;
                    hiddenField.value = parent.value;
                    hiddenField.className = 'hidden-parent-field';
                    parentContainer.appendChild(hiddenField);

                    // Ajouter aussi le type
                    const typeField = document.createElement('input');
                    typeField.type = 'hidden';
                    typeField.name = `parents[${index}][type]`;
                    typeField.value = 'existant';
                    typeField.className = 'hidden-parent-field';
                    parentContainer.appendChild(typeField);

                    console.log(`Champ caché créé pour parent ${index}:`, parent.value);
                }
            });
        }
    }

    // Fonction pour ajouter un nouveau parent
    function addNewParent() {
        parentCounter++;
        const template = document.getElementById('parent-template');
        const newParent = template.cloneNode(true);

        // Mettre à jour les IDs et noms
        newParent.id = `parent-${parentCounter}`;
        newParent.style.display = 'block';

        // Mettre à jour tous les attributs name et id dans le template
        const inputs = newParent.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace('template', parentCounter);
            }
            if (input.id) {
                input.id = input.id.replace('template', parentCounter);
            }
        });

        // Ajouter le nouveau parent au conteneur
        document.getElementById('parents-container').appendChild(newParent);

        // Initialiser Choices.js pour le nouveau select
        const newSelect = newParent.querySelector('.parent-select');
        if (newSelect) {
            initializeParentChoice(newSelect);
        }

        // Ajouter l'événement de suppression
        const removeBtn = newParent.querySelector('.remove-parent');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                removeParent(newParent);
            });
        }

        // Ajouter l'événement pour la checkbox "parent existant"
        const existantCheckbox = newParent.querySelector('.parent-existant-checkbox');
        if (existantCheckbox) {
            existantCheckbox.addEventListener('change', function() {
                console.log('Checkbox parent existant changée:', this.checked);
                toggleParentType(newParent, this.checked);
            });
        }

        // Animation d'apparition
        newParent.style.opacity = '0';
        newParent.style.transform = 'translateY(30px)';
        setTimeout(() => {
            newParent.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            newParent.style.opacity = '1';
            newParent.style.transform = 'translateY(0)';
        }, 100);

        console.log('Nouveau parent ajouté avec ID:', newParent.id);
    }

    // Fonction pour supprimer un parent
    function removeParent(parentElement) {
        // Animation de sortie
        parentElement.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        parentElement.style.opacity = '0';
        parentElement.style.transform = 'translateY(-30px) scale(0.95)';

        setTimeout(() => {
            // Détruire l'instance Choices.js si elle existe
            const select = parentElement.querySelector('.parent-select');
            if (select && parentChoicesInstances[select.id]) {
                parentChoicesInstances[select.id].destroy();
                delete parentChoicesInstances[select.id];
            }

            // Supprimer l'élément du DOM
            parentElement.remove();
        }, 400);
    }

    // Fonction pour basculer entre parent existant et nouveau parent
    function toggleParentType(parentContainer, isExistant) {
        console.log('toggleParentType appelée:', { parentContainer, isExistant });

        const selectContainer = parentContainer.querySelector('.parent-existant-section');
        const newParentFields = parentContainer.querySelector('.parent-nouveau-section');
        const typeInput = parentContainer.querySelector('input[name*="[type]"]');

        console.log('Éléments trouvés:', {
            selectContainer: !!selectContainer,
            newParentFields: !!newParentFields,
            typeInput: !!typeInput
        });

        if (isExistant) {
            if (selectContainer) {
                selectContainer.style.display = 'block';
                console.log('Section parent existant affichée');
            } else {
                console.error('Section parent existant non trouvée');
            }

            if (newParentFields) {
                newParentFields.style.display = 'none';
                console.log('Section nouveau parent cachée');
            } else {
                console.error('Section nouveau parent non trouvée');
            }

            if (typeInput) {
                typeInput.value = 'existant';
                console.log('Type défini sur existant');
            }

            // Initialiser Choices.js si pas déjà fait
            const select = parentContainer.querySelector('.parent-select');
            if (select && !parentChoicesInstances[select.id]) {
                console.log('Initialisation de Choices.js pour:', select.id);
                initializeParentChoice(select);
            }
        } else {
            if (selectContainer) {
                selectContainer.style.display = 'none';
                console.log('Section parent existant cachée');
            }

            if (newParentFields) {
                newParentFields.style.display = 'block';
                console.log('Section nouveau parent affichée');
            }

            if (typeInput) {
                typeInput.value = 'nouveau';
                console.log('Type défini sur nouveau');
            }
        }
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM chargé - Initialisation du formulaire d\'inscription');

        // Initialiser Choices.js pour tous les selects de parents existants
        document.querySelectorAll('.parent-select').forEach(select => {
            initializeParentChoice(select);
        });

        // Événement pour le bouton d'ajout de parent
        const addParentBtn = document.getElementById('add-parent-btn');
        if (addParentBtn) {
            addParentBtn.addEventListener('click', addNewParent);
        }

        // Événements pour les checkboxes "parent existant"
        document.querySelectorAll('.parent-existant-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                console.log('Checkbox parent existant changée:', this.checked);
                const parentContainer = this.closest('.parent-item');
                if (parentContainer) {
                    toggleParentType(parentContainer, this.checked);
                } else {
                    console.error('Parent container non trouvé pour la checkbox:', this);
                }
            });
        });

        // Événements pour les boutons de suppression existants
        document.querySelectorAll('.remove-parent').forEach(btn => {
            btn.addEventListener('click', function() {
                const parentElement = this.closest('.parent-item');
                removeParent(parentElement);
            });
        });

        // Validation du formulaire
        const form = document.getElementById('inscription-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // Validation personnalisée si nécessaire
                console.log('Soumission du formulaire d\'inscription');
            });
        }

        console.log('Initialisation terminée');
    });

    // Fonction utilitaire pour déboguer
    function debugChoicesInstances() {
        console.log('Instances Choices.js actives:', parentChoicesInstances);
    }

    // Exposer certaines fonctions globalement pour le débogage
    window.debugChoicesInstances = debugChoicesInstances;
    window.addNewParent = addNewParent;
</script>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Nouvelle Inscription</h1>
        <a href="{{ route('esbtp.inscriptions.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Retour à la liste
        </a>
    </div>

    <!-- Formulaire d'inscription -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Formulaire d'inscription</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('esbtp.inscriptions.store') }}" enctype="multipart/form-data">
                @csrf

                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Message pour les champs obligatoires -->
                <div class="alert alert-warning mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Les champs marqués d'un astérisque (<span class="text-danger">*</span>) sont obligatoires.
                    <br>
                    <small>Assurez-vous de remplir tous les champs obligatoires avant de soumettre le formulaire.</small>
                </div>

                <!-- Informations générales -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <h5 class="font-weight-bold">Informations générales</h5>
                        <hr>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> Un compte étudiant sera automatiquement créé lors de l'inscription. Le nom d'utilisateur et le mot de passe seront affichés après la création.
                    </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="card-title">Informations de classe</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i> La classe sélectionnée détermine automatiquement la filière, le niveau d'études, la formation et l'année universitaire.
                    </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label for="classe_display">Classe <span class="text-danger">*</span></label>
                            <div style="display: flex; gap: 10px;">
                                <input type="hidden" id="classe_id" name="classe_id" value="{{ old('classe_id') }}">
                                <input type="text" id="classe_display" class="form-control @error('classe_id') is-invalid @enderror" value="{{ old('classe_display') }}" readonly>
                                <button class="btn btn-primary" type="button" onclick="ouvrirSelecteurClasse()" style="min-width: 120px;">
                                    <i class="fas fa-search"></i> Sélectionner
                                </button>
                            </div>
                            <small class="text-muted mt-1 d-block">Cliquez sur le bouton pour ouvrir le sélecteur de classe</small>
                        @error('classe_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                        </div>
                    </div>
                </div>

                <!-- Frais obligatoires (lecture seule) -->
                @if(isset($mandatoryFeeCategories) && $mandatoryFeeCategories->count())
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="card-title">Frais obligatoires</h5>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle mr-1"></i> Le paiement du montant total des frais obligatoires est requis pour valider l'inscription.
                        </div>
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Libellé</th>
                                    <th>Montant (FCFA)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $mandatoryTotal = 0; @endphp
                                @foreach($mandatoryFeeCategories as $category)
                                    <tr>
                                        <td>{{ $category->name }}</td>
                                        <td>{{ number_format($category->amount, 0, ',', ' ') }}</td>
                                    </tr>
                                    @php $mandatoryTotal += $category->amount; @endphp
                                @endforeach
                                <tr class="font-weight-bold">
                                    <td>Total</td>
                                    <td>{{ number_format($mandatoryTotal, 0, ',', ' ') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                <!-- Champ de paiement initial obligatoire -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="paiement_montant">Montant du paiement initial <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control @error('paiement_montant') is-invalid @enderror"
                                   id="paiement_montant" name="paiement_montant"
                                   value="{{ old('paiement_montant', isset($mandatoryTotal) ? $mandatoryTotal : '') }}" required>
                            @error('paiement_montant')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Le montant minimum doit couvrir les frais obligatoires</small>
                        </div>
                    </div>
                </div>

                <!-- Services optionnels (frais) -->
                @if(isset($optionalFeeCategories) && $optionalFeeCategories->count())
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5 class="card-title">Services optionnels</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i> Sélectionnez les services optionnels auxquels l'étudiant souhaite souscrire (cantine, transport, internat, etc.).
                        </div>
                        <div class="form-group">
                            @foreach($optionalFeeCategories as $category)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="fee_optionals[]" id="fee_optionals_{{ $category->id }}" value="{{ $category->id }}"
                                        {{ is_array(old('fee_optionals')) && in_array($category->id, old('fee_optionals', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="fee_optionals_{{ $category->id }}">
                                        {{ $category->name }}
                                        @if($category->default_amount)
                                            <span class="badge bg-primary ms-2">{{ number_format($category->default_amount, 0, ',', ' ') }} FCFA</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Informations de l'étudiant -->
                <div class="row mt-4">
                    <div class="col-md-12 mb-4">
                        <h5 class="font-weight-bold">Informations de l'étudiant</h5>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom">Nom <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nom') is-invalid @enderror"
                               id="nom" name="nom" value="{{ old('nom') }}" required>
                        @error('nom')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="prenoms">Prénom(s) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('prenoms') is-invalid @enderror"
                               id="prenoms" name="prenoms" value="{{ old('prenoms') }}" required>
                        @error('prenoms')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="matricule">Matricule <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('matricule') is-invalid @enderror"
                               id="matricule" name="matricule" value="{{ old('matricule') }}" required>
                        <small class="text-muted">Entrez manuellement le matricule de l'étudiant</small>
                        @error('matricule')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="date_naissance">Date de naissance <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_naissance') is-invalid @enderror"
                               id="date_naissance" name="date_naissance" value="{{ old('date_naissance') }}" required>
                        @error('date_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="lieu_naissance">Lieu de naissance</label>
                        <input type="text" class="form-control @error('lieu_naissance') is-invalid @enderror"
                               id="lieu_naissance" name="lieu_naissance" value="{{ old('lieu_naissance') }}">
                        @error('lieu_naissance')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="sexe">Genre <span class="text-danger">*</span></label>
                        <select class="form-control @error('sexe') is-invalid @enderror" id="sexe" name="sexe" required>
                            <option value="">Sélectionner...</option>
                            <option value="M" {{ old('sexe') == 'M' ? 'selected' : '' }}>Homme</option>
                            <option value="F" {{ old('sexe') == 'F' ? 'selected' : '' }}>Femme</option>
                        </select>
                        @error('sexe')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="email_personnel">Email</label>
                        <input type="email" class="form-control @error('email_personnel') is-invalid @enderror"
                               id="email_personnel" name="email_personnel" value="{{ old('email_personnel') }}">
                        @error('email_personnel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="telephone">Téléphone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('telephone') is-invalid @enderror"
                               id="telephone" name="telephone" value="{{ old('telephone') }}"
                               placeholder="+225 XX XX XXX XXX" required>
                        @error('telephone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="ville">Ville de résidence</label>
                        <input type="text" class="form-control @error('ville') is-invalid @enderror"
                               id="ville" name="ville" value="{{ old('ville') }}">
                        @error('ville')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="commune">Commune de résidence</label>
                        <input type="text" class="form-control @error('commune') is-invalid @enderror"
                               id="commune" name="commune" value="{{ old('commune') }}">
                        @error('commune')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-8 mb-3">
                        <label for="photo">Photo de profil</label>
                        <input type="file" class="form-control-file @error('photo') is-invalid @enderror"
                               id="photo" name="photo">
                        <small class="form-text text-muted">Formats acceptés: jpeg, png, jpg. Taille max: 2Mo</small>
                        @error('photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Informations du parent -->
                <div class="row mt-5">
                    <div class="col-md-12 mb-4">
                        <h5 class="section-title">Informations du/des parent(s)/tuteur(s)</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Ajoutez les informations des parents ou tuteurs de l'étudiant. Vous pouvez rechercher des parents existants ou en créer de nouveaux.
                    </div>
                    </div>
                </div>

                <!-- Container pour les parents -->
                <div id="parents-container">
                    <!-- Premier parent (toujours présent) -->
                    <div class="parent-item card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-title mb-0 text-primary">
                                    <i class="fas fa-user-tie me-2"></i>Parent/Tuteur Principal
                                </h6>
                            </div>

                            <input type="hidden" name="parents[0][type]" value="nouveau">

                            <div class="form-check mb-3">
                                <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_0">
                                <label class="form-check-label" for="parent_existant_0">
                                    <i class="fas fa-search me-1"></i>Sélectionner un parent existant
                                </label>
                            </div>

                            <!-- Section pour parent existant -->
                            <div class="parent-existant-section" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-search me-1"></i>Rechercher un parent
                                    </label>
                                    <select class="form-control parent-select" id="parent_id_0" name="parents[0][parent_id]">
                                        <option></option>
                                </select>
                                </div>
                            </div>

                            <!-- Section pour nouveau parent -->
                            <div class="parent-nouveau-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Nom</label>
                                            <input type="text" class="form-control" name="parents[0][nom]" data-required="true">
                                    </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Prénom(s)</label>
                                            <input type="text" class="form-control" name="parents[0][prenoms]" data-required="true">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Téléphone</label>
                                            <input type="tel" class="form-control" name="parents[0][telephone]" data-required="true" placeholder="+225 XX XX XXX XXX">
                                    </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Email</label>
                                            <input type="email" class="form-control" name="parents[0][email]">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Profession</label>
                                            <input type="text" class="form-control" name="parents[0][profession]">
                                    </div>
                                </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Relation</label>
                                            <select class="form-control" name="parents[0][relation]" data-required="true">
                                                <option value="Père">Père</option>
                                                <option value="Mère">Mère</option>
                                                <option value="Tuteur">Tuteur</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mb-3">
                                    <label class="form-label fw-bold">Adresse</label>
                                    <textarea class="form-control" name="parents[0][adresse]" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bouton pour ajouter un parent supplémentaire -->
                <div class="add-parent-container">
                    <button type="button" id="add-parent-btn" class="btn btn-add-parent">
                        <i class="fas fa-plus"></i>
                        Ajouter un parent/tuteur
                    </button>
                </div>

                <!-- Template pour un nouveau parent (caché par défaut) -->
                <div id="parent-template" style="display: none;">
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="card-title mb-0 text-primary">
                                    <i class="fas fa-user-friends me-2"></i>Parent/Tuteur
                                </h6>
                                <button type="button" class="btn btn-sm remove-parent">
                                    <i class="fas fa-times me-1"></i> Supprimer
                                </button>
                            </div>

                            <input type="hidden" name="parents[template][type]" value="nouveau">

                            <div class="form-check mb-3">
                                <input class="form-check-input parent-existant-checkbox" type="checkbox" id="parent_existant_template">
                                <label class="form-check-label" for="parent_existant_template">
                                    <i class="fas fa-search me-1"></i>Sélectionner un parent existant
                                </label>
                            </div>

                            <!-- Section pour parent existant -->
                            <div class="parent-existant-section" style="display: none;">
                                <div class="form-group">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-search me-1"></i>Rechercher un parent
                                    </label>
                                    <select class="form-control parent-select" id="parent_id_template" name="parents[template][parent_id]">
                                        <option></option>
                                </select>
                                </div>
                            </div>

                            <!-- Section pour nouveau parent -->
                            <div class="parent-nouveau-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Nom</label>
                                            <input type="text" class="form-control" name="parents[template][nom]" data-required="true">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Prénom(s)</label>
                                            <input type="text" class="form-control" name="parents[template][prenoms]" data-required="true">
                                        </div>
                                    </div>
                                    </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Téléphone</label>
                                            <input type="tel" class="form-control" name="parents[template][telephone]" data-required="true" placeholder="+225 XX XX XXX XXX">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Email</label>
                                            <input type="email" class="form-control" name="parents[template][email]">
                                        </div>
                                    </div>
                                    </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Profession</label>
                                            <input type="text" class="form-control" name="parents[template][profession]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Relation</label>
                                            <select class="form-control" name="parents[template][relation]" data-required="true">
                                            <option value="Père">Père</option>
                                            <option value="Mère">Mère</option>
                                            <option value="Tuteur">Tuteur</option>
                                            <option value="Autre">Autre</option>
                                        </select>
                                    </div>
                                </div>
                                    </div>

                                <div class="form-group mb-3">
                                    <label class="form-label fw-bold">Adresse</label>
                                    <textarea class="form-control" name="parents[template][adresse]" rows="2"></textarea>
                                    </div>
                                </div>
                                    </div>
                                </div>
                            </div>

                <!-- Boutons de soumission -->
                <div class="row mt-4">
                    <div class="col-md-12 text-center">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Enregistrer l'inscription
                        </button>
                        <a href="{{ route('esbtp.inscriptions.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL CUSTOM SÉLECTEUR DE CLASSE (remplace Bootstrap) -->
<style>
/* Overlay du modal custom */
#modalCustomOverlay {
  display: none;
  position: fixed;
  z-index: 3000;
  left: 0; top: 0; right: 0; bottom: 0;
  background: rgba(4,83,203,0.13);
  backdrop-filter: blur(2px);
  align-items: center;
  justify-content: center;
  transition: opacity 0.2s;
}
#modalCustomOverlay.active {
  display: flex;
  opacity: 1;
}
#modalCustomContent {
  background: rgba(255,255,255,0.98);
  border-radius: 2.2rem;
  box-shadow: 0 8px 32px 0 rgba(4,83,203,0.18);
  max-width: 700px;
  width: 98vw;
  padding: 2.5rem 2rem 2rem 2rem;
  position: relative;
  animation: modalIn 0.25s cubic-bezier(.4,2,.6,1) both;
}
@keyframes modalIn {
  from { transform: translateY(40px) scale(0.98); opacity: 0; }
  to { transform: none; opacity: 1; }
}
#modalCustomOverlay .modal-close-btn {
  position: absolute;
  top: 1.2rem; right: 1.2rem;
  background: #0453cb;
  color: #fff;
  border: none;
  border-radius: 50%;
  width: 38px; height: 38px;
  font-size: 1.3rem;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 2px 8px rgba(4,83,203,0.10);
  cursor: pointer;
  transition: background 0.2s;
}
#modalCustomOverlay .modal-close-btn:hover {
  background: #1b64d4;
}
@media (max-width: 600px) {
  #modalCustomContent { padding: 1.2rem 0.5rem; }
}
body.modal-open-custom {
  overflow: hidden !important;
}
</style>

<!-- Bouton Sélectionner modifié pour ouvrir le modal custom -->
<script>
function ouvrirSelecteurClasse() {
  document.getElementById('modalCustomOverlay').classList.add('active');
  document.body.classList.add('modal-open-custom');
  // Focus sur le premier input du modal si besoin
  setTimeout(function(){
    var firstInput = document.querySelector('#modalCustomOverlay input, #modalCustomOverlay select');
    if(firstInput) firstInput.focus();
  }, 200);
}
function fermerSelecteurClasse() {
  document.getElementById('modalCustomOverlay').classList.remove('active');
  document.body.classList.remove('modal-open-custom');
}
// Fermer le modal si on clique sur l'overlay
window.addEventListener('click', function(e){
  var overlay = document.getElementById('modalCustomOverlay');
  if(e.target === overlay) fermerSelecteurClasse();
});
// Fermer avec ESC
window.addEventListener('keydown', function(e){
  if(e.key === 'Escape') fermerSelecteurClasse();
});
</script>

<!-- MODAL CUSTOM OVERLAY -->
<div id="modalCustomOverlay" tabindex="-1" aria-modal="true" role="dialog">
  <div id="modalCustomContent">
    <button type="button" class="modal-close-btn" onclick="fermerSelecteurClasse()" aria-label="Fermer">&times;</button>
    <h4 class="mb-3" style="color:#0453cb;font-weight:600;">Sélectionner une classe</h4>
    <!-- Copie du contenu du modal Bootstrap/table de sélection -->
    <div class="row g-2 mb-3">
      <div class="col-md-4">
        <select id="filtre-filiere" class="form-select" onchange="filtrerClassesCustom()">
          <option value="">Filière...</option>
          <!-- Options dynamiques -->
        </select>
      </div>
      <div class="col-md-4">
        <select id="filtre-niveau" class="form-select" onchange="filtrerClassesCustom()">
          <option value="">Niveau...</option>
          <!-- Options dynamiques -->
        </select>
      </div>
      <div class="col-md-4">
        <select id="filtre-annee" class="form-select" onchange="filtrerClassesCustom()">
          <option value="">Année...</option>
          <!-- Options dynamiques -->
        </select>
      </div>
    </div>
    <div class="table-responsive mb-2">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>NOM</th><th>CODE</th><th>FILIÈRE</th><th>NIVEAU</th><th>ANNÉE</th><th>ACTION</th>
          </tr>
        </thead>
        <tbody id="table-classes-custom">
          <!-- Lignes dynamiques JS -->
        </tbody>
      </table>
    </div>
    <div class="d-flex justify-content-end mt-3">
      <button type="button" class="btn btn-outline-primary" onclick="fermerSelecteurClasse()">Fermer</button>
    </div>
  </div>
</div>

<script>
// Exemple de données (à remplacer par AJAX ou variables Laravel)
var classesData = [
  {id:1, nom:'1ère année BTS Génie Civil Option Bâtiment', code:'1BTS-GC-BAT', filiere:'BTS1 BATIMENT', niveau:'Première année BTS', annee:'2025-2026'},
  // ... autres classes dynamiques ...
];
function renderClassesCustom() {
  var tbody = document.getElementById('table-classes-custom');
  tbody.innerHTML = '';
  classesData.forEach(function(classe) {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td>'+classe.nom+'</td><td>'+classe.code+'</td><td>'+classe.filiere+'</td><td>'+classe.niveau+'</td><td>'+classe.annee+'</td>'+
      '<td><button type="button" class="btn btn-sm btn-primary" onclick="selectionnerClasseCustom('+classe.id+', \' '+classe.nom.replace(/'/g, "&#39;")+'\')">Sélectionner</button></td>';
    tbody.appendChild(tr);
  });
}
function selectionnerClasseCustom(id, nom) {
  // Remplir le champ classe dans le formulaire principal
  document.getElementById('champ-classe').value = nom.trim();
  fermerSelecteurClasse();
}
// Appel initial
renderClassesCustom();
</script>
<!-- FIN MODAL CUSTOM -->
@endsection

