/**
 * Gestionnaire d'événements temps réel pour le module Comptabilité ESBTP
 * Utilise Laravel Echo pour recevoir les événements broadcastés
 */

class ComptabiliteEventsManager {
    constructor() {
        this.notifications = [];
        this.init();
    }

    /**
     * Initialiser le gestionnaire d'événements
     */
    init() {
        this.setupEventListeners();
        this.setupPolling(); // Fallback si broadcasting non disponible
    }

    /**
     * Configuration des écouteurs d'événements Laravel Echo
     */
    setupEventListeners() {
        // Vérifier si Laravel Echo est disponible
        if (typeof Echo !== "undefined") {
            console.log(
                "Laravel Echo détecté - Configuration des écouteurs d'événements"
            );

            // Écouter le canal privé comptabilite
            Echo.private("comptabilite")
                .listen(".paiement.recu", (e) => {
                    this.handlePaiementRecu(e);
                })
                .listen(".bon.approuve", (e) => {
                    this.handleBonApprouve(e);
                })
                .listen(".seuil.atteint", (e) => {
                    this.handleSeuilAtteint(e);
                })
                .listen(".relance.envoyee", (e) => {
                    this.handleRelanceEnvoyee(e);
                })
                .listen(".kpis.calcules", (e) => {
                    this.handleKPIsCalcules(e);
                });
        } else {
            console.warn(
                "Laravel Echo non disponible - Utilisation du polling comme fallback"
            );
        }
    }

    /**
     * Configuration du polling comme fallback
     */
    setupPolling() {
        // Polling pour les notifications toutes les 30 secondes
        setInterval(() => {
            this.pollNotifications();
        }, 30000);
    }

    /**
     * Gestionnaire pour l'événement PaiementRecu
     */
    handlePaiementRecu(event) {
        console.log("Paiement reçu:", event);

        // Mettre à jour l'interface utilisateur
        this.showNotification(
            "success",
            "Nouveau paiement",
            `Paiement de ${this.formatMontant(event.montant)} FCFA reçu de ${
                event.etudiant
            }`
        );

        // Mettre à jour les KPIs si on est sur le dashboard
        if (window.location.pathname.includes("dashboard-avance")) {
            this.updateKPICards();
        }

        // Mettre à jour le compteur de notifications
        this.updateNotificationCount();
    }

    /**
     * Gestionnaire pour l'événement BonApprouve
     */
    handleBonApprouve(event) {
        console.log("Bon approuvé:", event);

        this.showNotification(
            "success",
            "Bon approuvé",
            `Le bon n° ${event.numero_bon} de ${this.formatMontant(
                event.montant_total
            )} FCFA a été approuvé`
        );

        // Mettre à jour la liste des bons si on est sur la page correspondante
        if (window.location.pathname.includes("bons-sortie")) {
            this.refreshBonsList();
        }
    }

    /**
     * Gestionnaire pour l'événement SeuilAtteint
     */
    handleSeuilAtteint(event) {
        console.log("Seuil atteint:", event);

        let alertType =
            event.niveau === "critique"
                ? "error"
                : event.niveau === "warning"
                ? "warning"
                : "info";

        this.showNotification(
            alertType,
            `Alerte ${event.niveau}`,
            event.message
        );

        // Afficher une alerte persistante pour les seuils critiques
        if (event.niveau === "critique") {
            this.showCriticalAlert(event);
        }
    }

    /**
     * Gestionnaire pour l'événement RelanceEnvoyee
     */
    handleRelanceEnvoyee(event) {
        console.log("Relance envoyée:", event);

        this.showNotification(
            "info",
            "Relance envoyée",
            `Relance niveau ${event.niveau} envoyée à ${event.etudiant_nom}`
        );

        // Mettre à jour la liste des relances si on est sur la page correspondante
        if (window.location.pathname.includes("relances")) {
            this.refreshRelancesList();
        }
    }

    /**
     * Gestionnaire pour l'événement KPIsCalcules
     */
    handleKPIsCalcules(event) {
        console.log("KPIs calculés:", event);

        this.showNotification(
            "info",
            "KPIs mis à jour",
            `KPIs recalculés pour la période ${event.periode}`
        );

        // Mettre à jour le dashboard en temps réel
        if (window.location.pathname.includes("dashboard")) {
            this.updateDashboardKPIs(event);
        }
    }

    /**
     * Afficher une notification toast
     */
    showNotification(type, title, message) {
        // Utiliser Bootstrap Toast ou une autre librairie de notifications
        const toastHtml = `
            <div class="toast align-items-center text-white bg-${this.getBootstrapClass(
                type
            )} border-0" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <strong>${title}</strong><br>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;

        // Ajouter le toast au conteneur
        const toastContainer =
            document.getElementById("toast-container") ||
            this.createToastContainer();
        toastContainer.insertAdjacentHTML("beforeend", toastHtml);

        // Initialiser et afficher le toast
        const toastElement = toastContainer.lastElementChild;
        const toast = new bootstrap.Toast(toastElement, { delay: 5000 });
        toast.show();

        // Supprimer l'élément après fermeture
        toastElement.addEventListener("hidden.bs.toast", () => {
            toastElement.remove();
        });
    }

    /**
     * Créer le conteneur de toasts s'il n'existe pas
     */
    createToastContainer() {
        const container = document.createElement("div");
        container.id = "toast-container";
        container.className = "toast-container position-fixed top-0 end-0 p-3";
        container.style.zIndex = "9999";
        document.body.appendChild(container);
        return container;
    }

    /**
     * Convertir le type en classe Bootstrap
     */
    getBootstrapClass(type) {
        const mapping = {
            success: "success",
            error: "danger",
            warning: "warning",
            info: "info",
        };
        return mapping[type] || "info";
    }

    /**
     * Afficher une alerte critique persistante
     */
    showCriticalAlert(event) {
        const alertHtml = `
            <div class="alert alert-danger alert-dismissible fade show critical-alert" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Alerte Critique!</strong> ${event.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

        // Insérer en haut de la page
        const mainContent = document.querySelector("main") || document.body;
        mainContent.insertAdjacentHTML("afterbegin", alertHtml);
    }

    /**
     * Mettre à jour les cartes KPI
     */
    updateKPICards() {
        // Faire un appel AJAX pour récupérer les KPIs mis à jour
        fetch("/esbtp/comptabilite/kpis-temps-reel")
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    this.updateKPIElements(data.kpis);
                }
            })
            .catch((error) =>
                console.error("Erreur lors de la mise à jour des KPIs:", error)
            );
    }

    /**
     * Mettre à jour les éléments KPI dans le DOM
     */
    updateKPIElements(kpis) {
        Object.keys(kpis).forEach((key) => {
            const element = document.getElementById(`kpi-${key}`);
            if (element) {
                element.textContent = this.formatKPI(key, kpis[key]);
                // Animer la mise à jour
                element.classList.add("updated");
                setTimeout(() => element.classList.remove("updated"), 1000);
            }
        });
    }

    /**
     * Formater les montants
     */
    formatMontant(montant) {
        return new Intl.NumberFormat("fr-FR").format(montant);
    }

    /**
     * Formater les KPIs selon leur type
     */
    formatKPI(type, value) {
        if (
            type.includes("montant") ||
            type.includes("recettes") ||
            type.includes("depenses")
        ) {
            return this.formatMontant(value);
        } else if (type.includes("taux")) {
            return `${value}%`;
        }
        return value;
    }

    /**
     * Polling pour les notifications (fallback)
     */
    pollNotifications() {
        fetch("/notifications/unread-count")
            .then((response) => response.json())
            .then((data) => {
                if (data.count !== this.lastNotificationCount) {
                    this.updateNotificationCount(data.count);
                    this.lastNotificationCount = data.count;
                }
            })
            .catch((error) =>
                console.debug("Erreur polling notifications:", error)
            );
    }

    /**
     * Mettre à jour le compteur de notifications
     */
    updateNotificationCount(count = null) {
        if (count === null) {
            // Récupérer le nombre actuel
            fetch("/notifications/unread-count")
                .then((response) => response.json())
                .then((data) => this.updateNotificationBadge(data.count))
                .catch((error) =>
                    console.debug("Erreur compteur notifications:", error)
                );
        } else {
            this.updateNotificationBadge(count);
        }
    }

    /**
     * Mettre à jour le badge de notifications
     */
    updateNotificationBadge(count) {
        const badge = document.getElementById("unreadNotificationCount");
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? "inline" : "none";
        }
    }

    /**
     * Rafraîchir la liste des bons de sortie
     */
    refreshBonsList() {
        if (typeof window.datatableBons !== "undefined") {
            window.datatableBons.ajax.reload();
        }
    }

    /**
     * Rafraîchir la liste des relances
     */
    refreshRelancesList() {
        if (typeof window.datatableRelances !== "undefined") {
            window.datatableRelances.ajax.reload();
        }
    }

    /**
     * Mettre à jour le dashboard avec les nouveaux KPIs
     */
    updateDashboardKPIs(event) {
        // Mettre à jour les graphiques si Chart.js est disponible
        if (typeof window.comptabiliteCharts !== "undefined") {
            window.comptabiliteCharts.updateCharts(event.kpis);
        }

        // Mettre à jour les cartes KPI
        this.updateKPIElements(event.kpis);
    }
}

// Initialiser le gestionnaire d'événements quand le DOM est prêt
document.addEventListener("DOMContentLoaded", function () {
    window.comptabiliteEvents = new ComptabiliteEventsManager();
});

// CSS pour les animations
const style = document.createElement("style");
style.textContent = `
    .updated {
        animation: highlight 1s ease-in-out;
    }

    @keyframes highlight {
        0% { background-color: inherit; }
        50% { background-color: #ffeeba; }
        100% { background-color: inherit; }
    }

    .critical-alert {
        position: sticky;
        top: 0;
        z-index: 1050;
    }
`;
document.head.appendChild(style);
