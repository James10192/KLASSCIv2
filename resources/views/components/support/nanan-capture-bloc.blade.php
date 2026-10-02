{{-- Capture jointe à la demande, dans le récapitulatif de Nanan. État et méthodes : nanan-capture.blade.php. --}}
<div class="nsp-capture" x-show="captureDisponible()"
     x-bind:class="{ 'nsp-capture--survol': captureSurvol }"
     x-on:dragover.prevent="captureSurvol = true" x-on:dragleave="captureSurvol = false" x-on:drop.prevent="surDepot($event)">
    <p class="nsp-etiquette nsp-capture-titre">Une capture d'écran ? <span>Facultatif, souvent très utile</span></p>

    <div class="nsp-capture-choix" x-show="!capture">
        <button type="button" class="nsp-btn nsp-btn--secondaire" x-on:click="capturerPage()" x-bind:disabled="captureEtat === 'rendu'">
            <i class="fas" x-bind:class="captureEtat === 'rendu' ? 'fa-spinner fa-spin' : 'fa-camera'" aria-hidden="true"></i>
            <span x-text="captureEtat === 'rendu' ? 'Capture en cours…' : 'Capturer cette page'">Capturer cette page</span>
        </button>
        <label class="nsp-btn nsp-btn--secondaire nsp-capture-fichier">
            <i class="fas fa-image" aria-hidden="true"></i>Choisir une image
            <input type="file" accept="image/png,image/jpeg,image/webp" x-on:change="choisirImage($event.target)">
        </label>
    </div>
    <p class="nsp-aide nsp-capture-astuce" x-show="!capture">
        Vous avez déjà fait une capture ? <kbd>Ctrl</kbd> + <kbd>V</kbd> pour la coller ici, ou glissez-la dans ce cadre.
        Sur téléphone : capture avec les boutons de l'appareil, puis « Choisir une image ».
    </p>

    <div class="nsp-capture-jointe" x-show="capture" x-cloak>
        <img alt="Aperçu de la capture jointe" x-bind:src="capture ? capture.url : ''">
        <div class="nsp-capture-jointe-texte"><strong>Capture prête</strong><span x-text="capture ? capture.taille : ''"></span></div>
        <button type="button" class="nsp-lien" x-on:click="modifierCapture()">Modifier</button>
        <button type="button" class="nsp-lien" x-on:click="retirerCapture()">Retirer</button>
    </div>

    <p class="nsp-erreur" x-show="captureMessage" role="alert"><span x-text="captureMessage"></span></p>
</div>
