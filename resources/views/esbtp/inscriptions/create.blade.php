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
<!-- Choices.js (bibliothèque pour les selects modernes) -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let parentIndex = 1; // Le premier parent a l'index 0

    // Initialisation au chargement de la page - s'assurer que les attributs required sont corrects
    function initializeRequiredAttributes() {
        document.querySelectorAll('.parent-item, .card').forEach(parentItem => {
            const existantSection = parentItem.querySelector('.parent-existant-section');
            const nouveauSection = parentItem.querySelector('.parent-nouveau-section');
            const checkbox = parentItem.querySelector('.parent-existant-checkbox');

            if (existantSection && nouveauSection) {
                // Retirer tous les attributs required au départ
                existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
                    input.removeAttribute('required');
                });
                nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
                    input.removeAttribute('required');
                });

                // Appliquer les bons attributs selon l'état de la checkbox
                if (checkbox && checkbox.checked) {
                    // Parent existant sélectionné
                    // Réactiver les champs nécessaires dans la section parent existant
                    existantSection.querySelectorAll('select').forEach(input => {
                        input.disabled = false;
                    });
                    existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                    // Désactiver les champs de la section nouveau parent
                    nouveauSection.querySelectorAll('input, select, textarea').forEach(input => {
                        input.disabled = true;
                        // Sauvegarder le nom original et le supprimer pour éviter l'envoi au serveur
                        if (input.name && input.type !== 'hidden') {
                            input.setAttribute('data-original-name', input.name);
                            input.removeAttribute('name');
                        }
                    });
                } else {
                    // Nouveau parent par défaut
                    // Restaurer les noms des champs de la section nouveau parent
                    nouveauSection.querySelectorAll('input, select, textarea').forEach(input => {
                        if (input.hasAttribute('data-original-name')) {
                            input.name = input.getAttribute('data-original-name');
                            input.removeAttribute('data-original-name');
                        }
                    });
                    nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                    // Désactiver seulement les champs qui ne sont pas nécessaires dans la section parent existant
                    existantSection.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
                        input.disabled = true;
                    });
                }
            }
        });
    }

    // Appeler l'initialisation au chargement
    initializeRequiredAttributes();

    // Gestion des checkboxes "parent existant"
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('parent-existant-checkbox')) {
            const parentItem = e.target.closest('.parent-item, .card');
            const existantSection = parentItem.querySelector('.parent-existant-section');
            const nouveauSection = parentItem.querySelector('.parent-nouveau-section');
            const typeInput = parentItem.querySelector('input[name*="[type]"]');

            // TOUJOURS retirer 'required' de tous les champs des deux sections AVANT de faire le switch
            existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
                input.removeAttribute('required');
            });
            nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
                input.removeAttribute('required');
            });

            if (e.target.checked) {
                // Afficher section parent existant, masquer nouveau
                existantSection.style.display = 'block';
                nouveauSection.style.display = 'none';
                if (typeInput) typeInput.value = 'existant';
                // Réactiver les champs nécessaires dans la section parent existant
                existantSection.querySelectorAll('select').forEach(input => {
                    input.disabled = false;
                });
                // Ajouter 'required' uniquement aux champs visibles de la section existant
                existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
                    input.setAttribute('required', 'required');
                });
                // Désactiver les champs de la section nouveau parent pour qu'ils ne soient pas envoyés
                nouveauSection.querySelectorAll('input, select, textarea').forEach(input => {
                    input.disabled = true;
                    // Sauvegarder le nom original et le supprimer pour éviter l'envoi au serveur
                    if (input.name && input.type !== 'hidden') {
                        input.setAttribute('data-original-name', input.name);
                        input.removeAttribute('name');
                    }
                });
            } else {
                // Afficher section nouveau parent, masquer existant
                existantSection.style.display = 'none';
                nouveauSection.style.display = 'block';
                if (typeInput) typeInput.value = 'nouveau';
                // Réactiver les champs de la section nouveau parent
                nouveauSection.querySelectorAll('input, select, textarea').forEach(input => {
                    input.disabled = false;
                    // Restaurer le nom original si il a été sauvegardé
                    if (input.hasAttribute('data-original-name')) {
                        input.name = input.getAttribute('data-original-name');
                        input.removeAttribute('data-original-name');
                    }
                });
                // Désactiver seulement les champs qui ne sont pas nécessaires dans la section parent existant
                existantSection.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
                    input.disabled = true;
                });
                // Ajouter 'required' uniquement aux champs visibles de la section nouveau
                nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
                    input.setAttribute('required', 'required');
                });
            }
        }
    });

    // Bouton ajouter parent
    document.getElementById('add-parent-btn').addEventListener('click', function() {
        const template = document.getElementById('parent-template');
        const container = document.getElementById('parents-container');

        // Cloner le template
        const newParent = template.cloneNode(true);
        newParent.id = 'parent-item-' + parentIndex;
        newParent.style.display = 'block';
        newParent.classList.add('parent-item');

        // Remplacer les "template" par l'index du parent
        const templateElements = newParent.querySelectorAll('[name*="template"], [id*="template"]');
        templateElements.forEach(element => {
            if (element.name) {
                element.name = element.name.replace('template', parentIndex);
            }
            if (element.id) {
                element.id = element.id.replace('template', parentIndex);
            }
            // Mise à jour des labels for
            if (element.tagName === 'LABEL' && element.getAttribute('for')) {
                element.setAttribute('for', element.getAttribute('for').replace('template', parentIndex));
            }
        });
        
        // Ajouter les attributs data-required aux champs qui en ont besoin dans le nouveau parent
        const requiredFields = [
            'input[name*="[nom]"]',
            'input[name*="[prenoms]"]',
            'input[name*="[telephone]"]',
            'select[name*="[relation]"]'
        ];
        
        requiredFields.forEach(selector => {
            const field = newParent.querySelector(selector);
            if (field) {
                field.setAttribute('data-required', 'true');
            }
        });

        // Ajouter l'animation d'apparition
        newParent.style.opacity = '0';
        newParent.style.transform = 'translateY(20px)';

        // Ajouter au container
        container.appendChild(newParent);

        // Animation d'entrée
        setTimeout(() => {
            newParent.style.transition = 'all 0.3s ease-out';
            newParent.style.opacity = '1';
            newParent.style.transform = 'translateY(0)';
        }, 10);

        // Initialiser les select pour parents existants si nécessaire
        initializeParentSelect(newParent.querySelector('.parent-select'));
        
        // Initialiser les attributs required selon la section affichée (par défaut, nouveau parent visible)
        const existantSection = newParent.querySelector('.parent-existant-section');
        const nouveauSection = newParent.querySelector('.parent-nouveau-section');
        
        // S'assurer que tous les champs n'ont pas d'attribut required au départ
        existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
            input.removeAttribute('required');
        });
        nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
            input.removeAttribute('required');
        });
        
        // Par défaut, c'est la section "nouveau parent" qui est visible, donc ajouter required à ces champs
        nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
            input.setAttribute('required', 'required');
        });
        
        // Désactiver seulement les champs qui ne sont pas nécessaires dans la section parent existant par défaut
        existantSection.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
            input.disabled = true;
        });

        parentIndex++;

        // Limiter à 3 parents maximum
        if (parentIndex >= 3) {
            this.style.display = 'none';
        }
    });

    // Gestion des boutons de suppression de parent
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-parent') || e.target.closest('.remove-parent')) {
            const button = e.target.classList.contains('remove-parent') ? e.target : e.target.closest('.remove-parent');
            const parentItem = button.closest('.parent-item, .card');

            // Animation de sortie
            parentItem.style.transition = 'all 0.3s ease-out';
            parentItem.style.opacity = '0';
            parentItem.style.transform = 'translateY(-20px)';

            setTimeout(() => {
                parentItem.remove();
                parentIndex--;

                // Réafficher le bouton d'ajout si moins de 3 parents
                if (parentIndex < 3) {
                    document.getElementById('add-parent-btn').style.display = 'inline-block';
                }
            }, 300);
        }
    });

    // Fonction pour initialiser les selects de parents existants
    function initializeParentSelect(selectElement) {
        if (!selectElement) return;

        // Configuration pour les parents existants
        if (typeof Choices !== 'undefined') {
            new Choices(selectElement, {
                searchEnabled: true,
                placeholder: true,
                placeholderValue: 'Rechercher un parent...',
                noResultsText: 'Aucun parent trouvé',
                noChoicesText: 'Aucun choix disponible',
                loadingText: 'Chargement...',
                searchPlaceholderValue: 'Taper pour rechercher',
                removeItemButton: true
            });
        }

        // Chargement AJAX des parents existants
        fetch('{{ route("esbtp.api.parents.search") }}', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
                })
                .then(response => response.json())
        .then(data => {
            if (data.parents) {
                // Vider le select
                selectElement.innerHTML = '<option></option>';

                // Ajouter les options
                data.parents.forEach(parent => {
                    const option = document.createElement('option');
                    option.value = parent.id;
                    option.textContent = `${parent.nom} ${parent.prenoms} - ${parent.telephone}`;
                    selectElement.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement des parents:', error);
        });
    }

    // Initialiser le premier select de parent
    initializeParentSelect(document.getElementById('parent_id_0'));
    
    // Correction critique : synchronisation des attributs required juste avant la soumission
    document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
        // Créer un log persistant dans le localStorage pour déboguer
        const debugData = {
            timestamp: new Date().toISOString(),
            parentInputs: []
        };
        
        const allParentInputs = document.querySelectorAll('[name*="parents"]');
        allParentInputs.forEach(input => {
            debugData.parentInputs.push({
                name: input.name,
                value: input.value,
                disabled: input.disabled,
                type: input.type,
                required: input.hasAttribute('required')
            });
        });
        
        // Sauvegarder dans localStorage (persistant après refresh)
        localStorage.setItem('inscription_debug_data', JSON.stringify(debugData, null, 2));
        
        // Aussi afficher dans un alert (visible avant refresh)
        const summary = debugData.parentInputs
            .filter(input => input.name && !input.disabled)
            .map(input => `${input.name}: "${input.value}"`)
            .join('\n');
        
        if (summary) {
            alert('DONNÉES PARENT ENVOYÉES:\n\n' + summary);
        }
        // Synchroniser les parents visibles
        document.querySelectorAll('.parent-item, .card').forEach(parentItem => {
            const existantSection = parentItem.querySelector('.parent-existant-section');
            const nouveauSection = parentItem.querySelector('.parent-nouveau-section');
            const checkbox = parentItem.querySelector('.parent-existant-checkbox');

            if (existantSection) {
                existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
                    input.removeAttribute('required');
                });
            }
            if (nouveauSection) {
                nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
                    input.removeAttribute('required');
                });
            }

            if (checkbox && checkbox.checked) {
                if (existantSection) {
                    // Réactiver les champs nécessaires dans la section parent existant
                    existantSection.querySelectorAll('select').forEach(input => {
                        input.disabled = false;
                    });
                    existantSection.querySelectorAll('[data-required="true"]').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                    // Désactiver les champs de la section nouveau parent
                    nouveauSection.querySelectorAll('input, select, textarea').forEach(input => {
                        input.disabled = true;
                        // Sauvegarder le nom original et le supprimer pour éviter l'envoi au serveur
                        if (input.name && input.type !== 'hidden') {
                            input.setAttribute('data-original-name', input.name);
                            input.removeAttribute('name');
                        }
                    });
                }
            } else {
                if (nouveauSection) {
                    // Restaurer les noms des champs de la section nouveau parent
                    nouveauSection.querySelectorAll('input, select, textarea').forEach(input => {
                        if (input.hasAttribute('data-original-name')) {
                            input.name = input.getAttribute('data-original-name');
                            input.removeAttribute('data-original-name');
                        }
                    });
                    nouveauSection.querySelectorAll('[data-required="true"]').forEach(input => {
                        input.setAttribute('required', 'required');
                    });
                    // Désactiver seulement les champs qui ne sont pas nécessaires dans la section parent existant
                    existantSection.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], textarea').forEach(input => {
                        input.disabled = true;
                    });
                }
            }
        });

        // 3. Validation manuelle HTML5
        if (!this.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            this.reportValidity();
            return false;
        }
        // Sinon, laisser la soumission normale
        });
    });

    // Gestion des frais et variants pour les inscriptions
    let currentFraisData = [];
    let selectedVariants = {};

    // Fonction pour charger les frais d'une classe
    function loadFraisForClass(filiereId, niveauId) {
        if (!filiereId || !niveauId) {
            showFraisPlaceholder();
            return;
        }

        const container = document.getElementById('fraisContainer');
        container.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Chargement des frais...</span>
                        </div>
                        <p class="mt-2 text-muted">Chargement des frais pour cette classe...</p>
                    </div>
                </div>
            </div>
        `;

        fetch(`/esbtp/frais/class-details/${filiereId}/${niveauId}`)
            .then(response => response.json())
            .then(data => {
                if (data.categories && data.categories.length > 0) {
                    currentFraisData = data.categories;
                    renderFraisForm(data.categories);
                    updateResumeFrais();
                } else {
                    showNoFraisMessage();
                }
            })
            .catch(error => {
                console.error('Erreur lors du chargement des frais:', error);
                showFraisError();
            });
    }

    function showFraisPlaceholder() {
        const container = document.getElementById('fraisContainer');
        container.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="text-center py-4">
                        <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Veuillez d'abord sélectionner une classe pour voir les frais applicables</p>
                    </div>
                </div>
            </div>
        `;
        updateResumeFrais();
    }

    function showNoFraisMessage() {
        const container = document.getElementById('fraisContainer');
        container.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Aucun frais configuré</strong> pour cette classe.
                        Contactez l'administration pour la configuration des frais.
                    </div>
                </div>
            </div>
        `;
        updateResumeFrais();
    }

    function showFraisError() {
        const container = document.getElementById('fraisContainer');
        container.innerHTML = `
            <div class="row">
                <div class="col-md-12">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Erreur de chargement</strong> des frais. Veuillez réessayer.
                    </div>
                </div>
            </div>
        `;
    }

    function renderFraisForm(categories) {
        const container = document.getElementById('fraisContainer');
        let html = '<div class="row">';

        categories.forEach(category => {
            html += `
                <div class="col-md-6 mb-4">
                    <div class="card border-${category.is_mandatory ? 'danger' : 'info'}">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="${category.icon || 'fas fa-money-bill'} me-2"></i>
                                ${category.name}
                                <span class="badge bg-${category.is_mandatory ? 'danger' : 'info'} ms-2">
                                    ${category.is_mandatory ? 'Obligatoire' : 'Optionnel'}
                                </span>
                            </h6>
                        </div>
                        <div class="card-body">
                            ${category.description ? `<p class="text-muted small mb-3">${category.description}</p>` : ''}
                            
                            <!-- Sélection du variant -->
                            <div class="mb-3">
                                <label class="form-label">Option sélectionnée</label>
                                <select class="form-select" name="frais[${category.id}][variant_id]" 
                                        onchange="updateVariantSelection(${category.id}, this.value)"
                                        ${category.is_mandatory ? 'required' : ''}>
                                    ${!category.is_mandatory ? '<option value="">Non souscrit</option>' : ''}
                                    ${category.variants && category.variants.length > 0 ? 
                                        category.variants.map(variant => `
                                            <option value="${variant.id}" ${variant.is_default && category.is_mandatory ? 'selected' : ''}>
                                                ${variant.name} - ${variant.amount.toLocaleString()} FCFA
                                            </option>
                                        `).join('') : `
                                            <option value="default" ${category.is_mandatory ? 'selected' : ''}>
                                                Option standard - ${category.amount.toLocaleString()} FCFA
                                            </option>
                                        `
                                    }
                                </select>
                            </div>

                            <!-- Montant affiché -->
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">Montant:</span>
                                <span class="fw-bold text-primary" id="amount-${category.id}">
                                    ${category.is_mandatory ? 
                                        (category.variants && category.variants.length > 0 ? 
                                            category.variants.find(v => v.is_default)?.amount.toLocaleString() || category.amount.toLocaleString()
                                            : category.amount.toLocaleString()
                                        ) : '0'
                                    } FCFA
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        container.innerHTML = html;

        // Initialiser les sélections par défaut
        categories.forEach(category => {
            if (category.is_mandatory) {
                if (category.variants && category.variants.length > 0) {
                    const defaultVariant = category.variants.find(v => v.is_default);
                    if (defaultVariant) {
                        selectedVariants[category.id] = {
                            variant_id: defaultVariant.id,
                            amount: defaultVariant.amount
                        };
                    }
                } else {
                    selectedVariants[category.id] = {
                        variant_id: 'default',
                        amount: category.amount
                    };
                }
            }
        });

        updateResumeFrais();
    }

    function updateVariantSelection(categoryId, variantId) {
        const category = currentFraisData.find(c => c.id == categoryId);
        if (!category) return;

        const amountElement = document.getElementById(`amount-${categoryId}`);
        
        if (!variantId || variantId === '') {
            // Non souscrit
            delete selectedVariants[categoryId];
            amountElement.textContent = '0 FCFA';
        } else if (variantId === 'default') {
            // Option standard
            selectedVariants[categoryId] = {
                variant_id: 'default',
                amount: category.amount
            };
            amountElement.textContent = category.amount.toLocaleString() + ' FCFA';
        } else {
            // Variant spécifique
            const variant = category.variants.find(v => v.id == variantId);
            if (variant) {
                selectedVariants[categoryId] = {
                    variant_id: variant.id,
                    amount: variant.amount
                };
                amountElement.textContent = variant.amount.toLocaleString() + ' FCFA';
            }
        }

        updateResumeFrais();
    }

    function updateResumeFrais() {
        const resumeContainer = document.getElementById('resumeFrais');
        
        if (Object.keys(selectedVariants).length === 0) {
            resumeContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    Aucun frais sélectionné
                </div>
            `;
            return;
        }

        let totalAmount = 0;
        let html = '<div class="table-responsive"><table class="table table-sm mb-0">';
        
        Object.keys(selectedVariants).forEach(categoryId => {
            const category = currentFraisData.find(c => c.id == categoryId);
            const selection = selectedVariants[categoryId];
            
            if (category && selection) {
                const variantName = selection.variant_id === 'default' ? 'Option standard' :
                    category.variants?.find(v => v.id == selection.variant_id)?.name || 'Option inconnue';
                
                html += `
                    <tr>
                        <td><strong>${category.name}</strong></td>
                        <td>${variantName}</td>
                        <td class="text-end">${selection.amount.toLocaleString()} FCFA</td>
                    </tr>
                `;
                totalAmount += selection.amount;
            }
        });

        html += `
            <tr class="table-primary">
                <td colspan="2"><strong>Total</strong></td>
                <td class="text-end"><strong>${totalAmount.toLocaleString()} FCFA</strong></td>
            </tr>
        `;
        html += '</table></div>';

        resumeContainer.innerHTML = html;
    }

    // Écouteur pour les changements de classe
    function setupClassChangeListener() {
        // Observer les sélecteurs de filière et niveau
        const filiereSelect = document.querySelector('select[name*="filiere"]');
        const niveauSelect = document.querySelector('select[name*="niveau"]');
        
        if (filiereSelect && niveauSelect) {
            function handleClassChange() {
                const filiereId = filiereSelect.value;
                const niveauId = niveauSelect.value;
                
                if (filiereId && niveauId) {
                    loadFraisForClass(filiereId, niveauId);
                } else {
                    showFraisPlaceholder();
                }
            }

            filiereSelect.addEventListener('change', handleClassChange);
            niveauSelect.addEventListener('change', handleClassChange);
            
            // Vérifier les valeurs initiales
            handleClassChange();
        }
    }

    // Initialiser quand le DOM est prêt
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(setupClassChangeListener, 1000); // Petit délai pour s'assurer que les sélecteurs sont initialisés
        showFraisPlaceholder();
    });
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
            <form id="inscriptionForm" action="{{ route('esbtp.inscriptions.store') }}" method="POST" enctype="multipart/form-data" novalidate>
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

                <div class="row">
                    <div class="col-md-12">
                        @include('components.forms.class-selector')
                    </div>
                </div>



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
                                <!-- Champ relation pour parent existant -->
                                <div class="form-group mt-2">
                                    <label class="form-label fw-bold">Relation avec l'étudiant</label>
                                    <select class="form-control" name="parents[0][relation]" data-required="true">
                                        <option value="Père">Père</option>
                                        <option value="Mère">Mère</option>
                                        <option value="Tuteur">Tuteur</option>
                                        <option value="Autre">Autre</option>
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
                                <!-- Champ relation pour parent existant (template) -->
                                <div class="form-group mt-2">
                                    <label class="form-label fw-bold">Relation avec l'étudiant</label>
                                    <select class="form-control" name="parents[template][relation]">
                                        <option value="Père">Père</option>
                                        <option value="Mère">Mère</option>
                                        <option value="Tuteur">Tuteur</option>
                                        <option value="Autre">Autre</option>
                                </select>
                                </div>
                            </div>

                            <!-- Section pour nouveau parent -->
                            <div class="parent-nouveau-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Nom</label>
                                            <input type="text" class="form-control" name="parents[template][nom]">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Prénom(s)</label>
                                            <input type="text" class="form-control" name="parents[template][prenoms]">
                                        </div>
                                    </div>
                                    </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="form-label fw-bold">Téléphone</label>
                                            <input type="tel" class="form-control" name="parents[template][telephone]" placeholder="+225 XX XX XXX XXX">
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
                                            <select class="form-control" name="parents[template][relation]">
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

                <!-- Section des frais et variants -->
                <div class="row mt-4">
                    <div class="col-md-12 mb-4">
                        <h5 class="font-weight-bold">Frais d'inscription et options</h5>
                        <hr>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Configuration des frais :</strong> Sélectionnez les options pour chaque catégorie de frais. 
                            Les frais obligatoires sont pré-sélectionnés selon votre filière et niveau d'études.
                        </div>
                    </div>
                </div>

                <!-- Conteneur dynamique pour les frais -->
                <div id="fraisContainer">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Chargement des frais...</span>
                                </div>
                                <p class="mt-2 text-muted">Veuillez d'abord sélectionner une classe pour voir les frais applicables</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Résumé des montants -->
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h6 class="mb-0">
                                    <i class="fas fa-calculator me-2"></i>
                                    Résumé des frais
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="resumeFrais">
                                    <div class="text-center text-muted py-3">
                                        Sélectionnez une classe et configurez les frais pour voir le résumé
                                    </div>
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


@endsection

