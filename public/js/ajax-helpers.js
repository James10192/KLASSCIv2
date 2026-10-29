/**
 * Fichier de fonctions utilitaires pour les requêtes AJAX
 * 
 * Ce fichier contient des fonctions qui facilitent l'utilisation d'AJAX
 * dans notre application Smart School.
 */

// Fonction qui récupère le token CSRF depuis la balise meta
function getCsrfToken() {
    // On cherche la balise meta qui contient le token CSRF
    // C'est comme chercher une clé spéciale dans une boîte
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

/**
 * Fonction pour effectuer une requête AJAX GET
 * 
 * @param {string} url - L'URL à laquelle envoyer la requête (comme l'adresse d'un ami)
 * @param {function} successCallback - Fonction à exécuter en cas de succès (ce qu'on fait quand on reçoit une bonne réponse)
 * @param {function} errorCallback - Fonction à exécuter en cas d'erreur (ce qu'on fait si quelque chose ne va pas)
 */
function ajaxGet(url, successCallback, errorCallback = null) {
    // Création d'une nouvelle requête AJAX (comme préparer une lettre)
    const xhr = new XMLHttpRequest();
    
    // Ouverture de la connexion (comme ouvrir une enveloppe)
    xhr.open('GET', url, true);
    
    // Configuration de la requête (comme mettre un timbre sur l'enveloppe)
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    
    // Définition de ce qui se passe quand la requête change d'état
    xhr.onreadystatechange = function() {
        // Si la requête est terminée (comme quand la lettre arrive à destination)
        if (xhr.readyState === 4) {
            // Si le statut est OK (comme quand le destinataire a bien reçu la lettre)
            if (xhr.status === 200) {
                // On traite la réponse (comme lire le contenu de la lettre)
                const response = JSON.parse(xhr.responseText);
                // On appelle la fonction de succès avec la réponse
                successCallback(response);
            } else {
                // Si une fonction d'erreur est fournie, on l'appelle
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        }
    };
    
    // Envoi de la requête (comme poster la lettre)
    xhr.send();
}

/**
 * Fonction pour effectuer une requête AJAX POST
 * 
 * @param {string} url - L'URL à laquelle envoyer la requête
 * @param {object} data - Les données à envoyer (comme le contenu de la lettre)
 * @param {function} successCallback - Fonction à exécuter en cas de succès
 * @param {function} errorCallback - Fonction à exécuter en cas d'erreur
 */
function ajaxPost(url, data, successCallback, errorCallback = null) {
    // Création d'une nouvelle requête AJAX
    const xhr = new XMLHttpRequest();
    
    // Ouverture de la connexion
    xhr.open('POST', url, true);
    
    // Configuration de la requête
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
    
    // Définition de ce qui se passe quand la requête change d'état
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200 || xhr.status === 201) {
                const response = JSON.parse(xhr.responseText);
                successCallback(response);
            } else {
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        }
    };
    
    // Envoi de la requête avec les données converties en JSON
    xhr.send(JSON.stringify(data));
}

/**
 * Fonction pour effectuer une requête AJAX PUT (mise à jour)
 * 
 * @param {string} url - L'URL à laquelle envoyer la requête
 * @param {object} data - Les données à envoyer
 * @param {function} successCallback - Fonction à exécuter en cas de succès
 * @param {function} errorCallback - Fonction à exécuter en cas d'erreur
 */
function ajaxPut(url, data, successCallback, errorCallback = null) {
    // Création d'une nouvelle requête AJAX
    const xhr = new XMLHttpRequest();
    
    // Ouverture de la connexion
    xhr.open('PUT', url, true);
    
    // Configuration de la requête
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
    
    // Définition de ce qui se passe quand la requête change d'état
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                successCallback(response);
            } else {
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        }
    };
    
    // Envoi de la requête avec les données converties en JSON
    xhr.send(JSON.stringify(data));
}

/**
 * Fonction pour effectuer une requête AJAX DELETE
 * 
 * @param {string} url - L'URL à laquelle envoyer la requête
 * @param {function} successCallback - Fonction à exécuter en cas de succès
 * @param {function} errorCallback - Fonction à exécuter en cas d'erreur
 */
function ajaxDelete(url, successCallback, errorCallback = null) {
    // Création d'une nouvelle requête AJAX
    const xhr = new XMLHttpRequest();
    
    // Ouverture de la connexion
    xhr.open('DELETE', url, true);
    
    // Configuration de la requête
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-CSRF-TOKEN', getCsrfToken());
    
    // Définition de ce qui se passe quand la requête change d'état
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200 || xhr.status === 204) {
                successCallback();
            } else {
                if (errorCallback) {
                    errorCallback(xhr.status, xhr.responseText);
                }
            }
        }
    };
    
    // Envoi de la requête
    xhr.send();
}

// Fonction pour afficher un message de notification
function showNotification(message, type = 'success') {
    // Création d'un élément div pour la notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast`;
    notification.innerHTML = message;
    
    // Ajout de styles pour la notification
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '250px';
    notification.style.padding = '15px';
    notification.style.borderRadius = '5px';
    notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
    
    // Ajout de la notification au corps du document
    document.body.appendChild(notification);
    
    // Suppression de la notification après 3 secondes
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s ease';
        
        // Suppression complète après la transition
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 500);
    }, 3000);
} 