/**
 * KLASSCI Debug Helper
 *
 * Fournit des fonctions de debug conditionnelles basées sur APP_DEBUG
 * Usage: debugLog(), debugError(), debugWarn(), debugAlert()
 *
 * Les logs sont UNIQUEMENT affichés quand APP_DEBUG=true
 */

(function() {
    'use strict';

    // Variable globale définie par le backend (layouts/app.blade.php)
    window.DEBUG_MODE = window.DEBUG_MODE || false;

    // Console.log conditionnel
    window.debugLog = function(...args) {
        if (window.DEBUG_MODE) {
            console.log(...args);
        }
    };

    // Console.error conditionnel
    window.debugError = function(...args) {
        if (window.DEBUG_MODE) {
            console.error(...args);
        }
    };

    // Console.warn conditionnel
    window.debugWarn = function(...args) {
        if (window.DEBUG_MODE) {
            console.warn(...args);
        }
    };

    // Console.info conditionnel
    window.debugInfo = function(...args) {
        if (window.DEBUG_MODE) {
            console.info(...args);
        }
    };

    // Alert() conditionnel
    window.debugAlert = function(message) {
        if (window.DEBUG_MODE) {
            alert(message);
        }
    };

    // Confirm() conditionnel (retourne false en production)
    window.debugConfirm = function(message) {
        if (window.DEBUG_MODE) {
            return confirm(message);
        }
        return false;
    };

    // Afficher l'état du debug mode au chargement
    if (window.DEBUG_MODE) {
        console.log('%c🔧 DEBUG MODE ACTIVÉ', 'color: orange; font-weight: bold; font-size: 14px;');
        console.log('Les logs de debug sont visibles. Pour masquer: APP_DEBUG=false dans .env');
    }
})();
