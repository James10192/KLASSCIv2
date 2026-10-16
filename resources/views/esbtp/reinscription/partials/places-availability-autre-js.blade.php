    function urlPlacesPourAnnee(classeId) {
        const anneeId = document.getElementById('annee_universitaire_id')?.value;
        return anneeId
            ? `/esbtp/classes/${encodeURIComponent(classeId)}/available-places?annee_universitaire_id=${encodeURIComponent(anneeId)}`
            : null;
    }

    // L'API peut se rabattre sur l'année courante si l'année cible n'existe plus.
    // Refuser alors toute information de capacité pour une autre année.
    function reponsePlacesPourAnneeValide(data) {
        const anneeId = document.getElementById('annee_universitaire_id')?.value;
        return Boolean(anneeId) && String(data.annee_universitaire_id ?? '') === String(anneeId);
    }

    // Affichage du quota sans mélanger la récupération AJAX et les états du bouton.
    function afficherAutrePlacesDisponibles(placesInfo, data) {
        const available = Number(data.available_places);
        if (!Number.isFinite(available)) throw new Error('Nombre de places invalide.');
        const capacity = data.capacity !== undefined ? Number(data.capacity) : null;

        const capacityText = capacity !== null && !isNaN(capacity) ? ` / ${capacity}` : '';
        let message = `Places disponibles: <strong>${Math.max(available, 0)}</strong>${capacityText}`;
        let alertClass = 'alert-success';
        let buttonMessage = '';

        if (available <= 5) {
            alertClass = 'alert-warning';
            buttonMessage = available > 0
                ? `Il reste ${available} place(s).`
                : buttonMessage;
            if (available > 0) {
                message += `<br><small class="text-warning">Il ne reste que ${available} place(s) disponibles.</small>`;
            }
        }

        if (available <= 0) {
            alertClass = 'alert-danger';
            message = capacityText
                ? `<strong>Aucune place disponible !</strong> (0${capacityText})`
                : '<strong>Aucune place disponible !</strong>';
            message += '<br><small class="text-danger">Veuillez sélectionner une autre classe avant de poursuivre.</small>';
            if (typeof window.setReinscriptionButtonState === 'function') {
                window.setReinscriptionButtonState(false, 'Classe complète. Choisissez une autre classe pour finaliser la réinscription.');
            }
        } else {
            // Places disponibles - activer le bouton
            if (buttonMessage) {
                if (typeof window.setReinscriptionButtonState === 'function') {
                    window.setReinscriptionButtonState(true, buttonMessage);
                }
            } else {
                if (typeof window.setReinscriptionButtonState === 'function') {
                    window.setReinscriptionButtonState(true);
                }
            }
        }

        placesInfo.innerHTML = `<div class="alert ${alertClass} p-2 mt-2">${message}</div>`;
    }

    let autrePlacesRequestId = 0;

    // ========================================
    // VÉRIFICATION PLACES DISPONIBLES (pour "autre filière")
    // ========================================
    window.checkAutrePlacesDisponibles = function(classeId) {
        const requestId = ++autrePlacesRequestId;
        const placesInfo = document.getElementById('autreClassePlacesInfo');
        if (!placesInfo) {
            return;
        }

        // Si aucune classe sélectionnée (option vide), effacer l'info des places
        if (!classeId) {
            placesInfo.innerHTML = '';
            return;
        }

        const placesUrl = urlPlacesPourAnnee(classeId);
        if (!placesUrl) {
            placesInfo.textContent = 'Sélectionnez une année universitaire pour consulter les places.';
            if (typeof window.setReinscriptionButtonState === 'function') {
                window.setReinscriptionButtonState(false, 'Sélectionnez une année universitaire.');
            }
            return;
        }

        placesInfo.innerHTML = `
            <div class="d-flex align-items-center text-muted small mt-2">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Vérification des places...
            </div>
        `;
        if (typeof window.setReinscriptionButtonState === 'function') {
            window.setReinscriptionButtonState(false, 'Vérification des places disponibles en cours...');
        }

        fetch(placesUrl)
            .then(response => response.json())
            .then(data => {
                if (requestId !== autrePlacesRequestId) return;
                if (!reponsePlacesPourAnneeValide(data)) {
                    throw new Error('Réponse des places pour une autre année universitaire.');
                }
                afficherAutrePlacesDisponibles(placesInfo, data);

                // Charger les frais pour cette classe
                loadAutreFrais(classeId);
            })
            .catch(error => {
                if (requestId !== autrePlacesRequestId) return;
                console.error('Erreur vérification places:', error);
                placesInfo.innerHTML = '<div class="alert alert-danger p-2 mt-2">Erreur lors de la récupération des places.</div>';
                if (typeof window.setReinscriptionButtonState === 'function') {
                    window.setReinscriptionButtonState(false, 'Erreur lors de la récupération des places. Réessayez ou sélectionnez une autre classe.');
                }
            });
    }

