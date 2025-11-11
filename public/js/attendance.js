/**
 * Script pour gérer les présences des étudiants avec AJAX
 * 
 * Ce script permet de marquer les présences des étudiants sans recharger la page,
 * ce qui améliore l'expérience utilisateur et la rapidité de l'application.
 */

// Attendre que le document soit complètement chargé
document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner tous les boutons de présence
    const attendanceButtons = document.querySelectorAll('.attendance-btn');
    
    // Pour chaque bouton, ajouter un écouteur d'événement
    attendanceButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Empêcher le comportement par défaut du bouton (qui rechargerait la page)
            e.preventDefault();
            
            // Récupérer les données nécessaires depuis les attributs data-*
            const studentId = this.getAttribute('data-student-id');
            const date = this.getAttribute('data-date');
            const status = this.getAttribute('data-status');
            
            // Appeler la fonction pour marquer la présence
            markAttendance(studentId, date, status, this);
        });
    });
    
    // Sélectionner le bouton pour marquer toutes les présences
    const markAllButton = document.querySelector('#mark-all-present');
    if (markAllButton) {
        markAllButton.addEventListener('click', function(e) {
            e.preventDefault();
            markAllAttendance('present');
        });
    }
    
    // Sélectionner le bouton pour marquer toutes les absences
    const markAllAbsentButton = document.querySelector('#mark-all-absent');
    if (markAllAbsentButton) {
        markAllAbsentButton.addEventListener('click', function(e) {
            e.preventDefault();
            markAllAttendance('absent');
        });
    }
});

/**
 * Fonction pour marquer la présence d'un étudiant
 * 
 * @param {number} studentId - ID de l'étudiant
 * @param {string} date - Date de la présence (format YYYY-MM-DD)
 * @param {string} status - Statut de présence ('present', 'absent', 'late', 'excused')
 * @param {HTMLElement} button - Le bouton qui a été cliqué
 */
function markAttendance(studentId, date, status, button) {
    // Préparer les données à envoyer
    const data = {
        student_id: studentId,
        date: date,
        status: status
    };
    
    // URL de l'API pour marquer la présence
    const url = '/attendance/mark';
    
    // Afficher un indicateur de chargement
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    button.disabled = true;
    
    // Envoyer la requête AJAX
    ajaxPost(url, data, 
        // Fonction de succès
        function(response) {
            // Mettre à jour l'interface utilisateur
            updateAttendanceUI(studentId, status);
            
            // Afficher une notification de succès
            showNotification('Présence mise à jour avec succès', 'success');
            
            // Réactiver le bouton
            button.disabled = false;
            
            // Mettre à jour le texte du bouton en fonction du statut
            updateButtonText(button, status);
        },
        // Fonction d'erreur
        function(status, responseText) {
            // Afficher une notification d'erreur
            showNotification('Erreur lors de la mise à jour de la présence', 'danger');
            
            // Réactiver le bouton
            button.disabled = false;
            
            // Restaurer le texte original du bouton
            updateButtonText(button, button.getAttribute('data-original-status'));
            
            // Afficher l'erreur dans la console pour le débogage
            debugError('Erreur:', status, responseText);
        }
    );
}

/**
 * Fonction pour marquer la présence de tous les étudiants
 * 
 * @param {string} status - Statut de présence ('present', 'absent', 'late', 'excused')
 */
function markAllAttendance(status) {
    // Récupérer la date depuis le formulaire
    const date = document.querySelector('#attendance-date').value;
    
    // Récupérer l'ID de la classe depuis le formulaire
    const classId = document.querySelector('#class-id').value;
    
    // Récupérer l'ID de la section depuis le formulaire
    const sectionId = document.querySelector('#section-id').value;
    
    // Préparer les données à envoyer
    const data = {
        class_id: classId,
        section_id: sectionId,
        date: date,
        status: status
    };
    
    // URL de l'API pour marquer toutes les présences
    const url = '/attendance/mark-all';
    
    // Afficher un indicateur de chargement
    document.querySelector('#mark-all-container').innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div>';
    
    // Envoyer la requête AJAX
    ajaxPost(url, data, 
        // Fonction de succès
        function(response) {
            // Mettre à jour l'interface utilisateur pour tous les étudiants
            response.students.forEach(studentId => {
                updateAttendanceUI(studentId, status);
            });
            
            // Afficher une notification de succès
            showNotification(`Présence mise à jour pour ${response.students.length} étudiants`, 'success');
            
            // Restaurer les boutons
            document.querySelector('#mark-all-container').innerHTML = `
                <button id="mark-all-present" class="btn btn-success">Tous présents</button>
                <button id="mark-all-absent" class="btn btn-danger ms-2">Tous absents</button>
            `;
            
            // Réattacher les écouteurs d'événements
            document.querySelector('#mark-all-present').addEventListener('click', function(e) {
                e.preventDefault();
                markAllAttendance('present');
            });
            
            document.querySelector('#mark-all-absent').addEventListener('click', function(e) {
                e.preventDefault();
                markAllAttendance('absent');
            });
        },
        // Fonction d'erreur
        function(status, responseText) {
            // Afficher une notification d'erreur
            showNotification('Erreur lors de la mise à jour des présences', 'danger');
            
            // Restaurer les boutons
            document.querySelector('#mark-all-container').innerHTML = `
                <button id="mark-all-present" class="btn btn-success">Tous présents</button>
                <button id="mark-all-absent" class="btn btn-danger ms-2">Tous absents</button>
            `;
            
            // Réattacher les écouteurs d'événements
            document.querySelector('#mark-all-present').addEventListener('click', function(e) {
                e.preventDefault();
                markAllAttendance('present');
            });
            
            document.querySelector('#mark-all-absent').addEventListener('click', function(e) {
                e.preventDefault();
                markAllAttendance('absent');
            });
            
            // Afficher l'erreur dans la console pour le débogage
            debugError('Erreur:', status, responseText);
        }
    );
}

/**
 * Fonction pour mettre à jour l'interface utilisateur après une mise à jour de présence
 * 
 * @param {number} studentId - ID de l'étudiant
 * @param {string} status - Statut de présence
 */
function updateAttendanceUI(studentId, status) {
    // Sélectionner la ligne du tableau pour cet étudiant
    const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
    
    if (row) {
        // Mettre à jour la classe CSS de la ligne
        row.className = ''; // Supprimer toutes les classes
        
        // Ajouter une classe en fonction du statut
        if (status === 'present') {
            row.classList.add('table-success');
        } else if (status === 'absent') {
            row.classList.add('table-danger');
        } else if (status === 'late') {
            row.classList.add('table-warning');
        } else if (status === 'excused') {
            row.classList.add('table-info');
        }
        
        // Mettre à jour le texte du statut
        const statusCell = row.querySelector('.status-cell');
        if (statusCell) {
            let statusText = '';
            let badgeClass = '';
            
            if (status === 'present') {
                statusText = 'Présent';
                badgeClass = 'bg-success';
            } else if (status === 'absent') {
                statusText = 'Absent';
                badgeClass = 'bg-danger';
            } else if (status === 'late') {
                statusText = 'En retard';
                badgeClass = 'bg-warning text-dark';
            } else if (status === 'excused') {
                statusText = 'Excusé';
                badgeClass = 'bg-info text-dark';
            }
            
            statusCell.innerHTML = `<span class="badge ${badgeClass}">${statusText}</span>`;
        }
        
        // Mettre à jour les boutons
        const buttons = row.querySelectorAll('.attendance-btn');
        buttons.forEach(button => {
            // Stocker le statut original
            button.setAttribute('data-original-status', status);
            
            // Mettre à jour l'apparence du bouton
            updateButtonText(button, status);
        });
    }
}

/**
 * Fonction pour mettre à jour le texte d'un bouton en fonction du statut
 * 
 * @param {HTMLElement} button - Le bouton à mettre à jour
 * @param {string} status - Le statut actuel
 */
function updateButtonText(button, status) {
    const buttonStatus = button.getAttribute('data-status');
    
    // Réinitialiser l'apparence du bouton
    button.innerHTML = '';
    button.classList.remove('btn-outline-success', 'btn-success', 'btn-outline-danger', 'btn-danger', 'btn-outline-warning', 'btn-warning', 'btn-outline-info', 'btn-info');
    
    // Configurer le bouton en fonction de son statut et du statut actuel
    if (buttonStatus === 'present') {
        button.innerHTML = '<i class="fas fa-check"></i>';
        button.classList.add(buttonStatus === status ? 'btn-success' : 'btn-outline-success');
    } else if (buttonStatus === 'absent') {
        button.innerHTML = '<i class="fas fa-times"></i>';
        button.classList.add(buttonStatus === status ? 'btn-danger' : 'btn-outline-danger');
    } else if (buttonStatus === 'late') {
        button.innerHTML = '<i class="fas fa-clock"></i>';
        button.classList.add(buttonStatus === status ? 'btn-warning' : 'btn-outline-warning');
    } else if (buttonStatus === 'excused') {
        button.innerHTML = '<i class="fas fa-notes-medical"></i>';
        button.classList.add(buttonStatus === status ? 'btn-info' : 'btn-outline-info');
    }
} 