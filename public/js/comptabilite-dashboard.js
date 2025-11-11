/**
 * Gestionnaire du Dashboard Comptabilité Avancé
 * Gestion des KPIs temps réel, graphiques Chart.js et AJAX polling
 */
class ComptabiliteManager {
    constructor() {
        this.charts = {};
        this.refreshInterval = null;
        this.isRefreshing = false;
        this.config = {
            autoRefresh: true,
            refreshInterval: 30000, // 30 secondes
            animationDuration: 1000,
        };
    }

    /**
     * Initialise le dashboard
     */
    init(options = {}) {
        this.config = { ...this.config, ...options };

        debugLog("🚀 Initialisation Dashboard Comptabilité Avancé");

        // Initialiser les graphiques
        this.initCharts(options);

        // Démarrer l'auto-refresh si activé
        if (this.config.autoRefresh) {
            this.startAutoRefresh();
        }

        // Événements
        this.bindEvents();

        debugLog("✅ Dashboard initialisé avec succès");
    }

    /**
     * Initialise tous les graphiques Chart.js
     */
    initCharts(options) {
        // Graphique d'évolution financière
        if (document.getElementById("evolutionChart")) {
            this.initEvolutionChart(
                options.evolutionData,
                options.evolutionDepensesData
            );
        }

        // Graphique de répartition par filière
        if (document.getElementById("repartitionChart")) {
            this.initRepartitionChart(options.kpisData);
        }

        // Graphique des prévisions
        if (document.getElementById("previsionsChart")) {
            this.initPrevisionsChart(options.kpisData);
        }

        // Graphique de performance mensuelle
        if (document.getElementById("performanceChart")) {
            this.initPerformanceChart(options.kpisData);
        }
    }

    /**
     * Graphique d'évolution des recettes/dépenses
     */
    initEvolutionChart(recettesData, depensesData) {
        const ctx = document.getElementById("evolutionChart").getContext("2d");

        // Préparation des données
        const labels = this.getLast12Months();
        const recettes = this.prepareEvolutionData(recettesData, labels);
        const depenses = this.prepareEvolutionData(depensesData, labels);

        this.charts.evolution = new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Recettes",
                        data: recettes,
                        borderColor: "#28a745",
                        backgroundColor: "rgba(40, 167, 69, 0.1)",
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: "#28a745",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointRadius: 5,
                    },
                    {
                        label: "Dépenses",
                        data: depenses,
                        borderColor: "#dc3545",
                        backgroundColor: "rgba(220, 53, 69, 0.1)",
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: "#dc3545",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointRadius: 5,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: "top",
                    },
                    tooltip: {
                        mode: "index",
                        intersect: false,
                        callbacks: {
                            label: function (context) {
                                return (
                                    context.dataset.label +
                                    ": " +
                                    new Intl.NumberFormat("fr-FR").format(
                                        context.parsed.y
                                    ) +
                                    " FCFA"
                                );
                            },
                        },
                    },
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: "Mois",
                        },
                    },
                    y: {
                        display: true,
                        title: {
                            display: true,
                            text: "Montant (FCFA)",
                        },
                        ticks: {
                            callback: function (value) {
                                return new Intl.NumberFormat("fr-FR").format(
                                    value
                                );
                            },
                        },
                    },
                },
                interaction: {
                    intersect: false,
                    mode: "index",
                },
                animation: {
                    duration: this.config.animationDuration,
                },
            },
        });
    }

    /**
     * Graphique de répartition par filière (doughnut)
     */
    initRepartitionChart(kpisData) {
        const ctx = document
            .getElementById("repartitionChart")
            .getContext("2d");

        // Données fictives pour la démonstration - à remplacer par de vraies données
        const data = {
            labels: ["Informatique", "Gestion", "Marketing", "Autres"],
            datasets: [
                {
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        "#007bff",
                        "#28a745",
                        "#ffc107",
                        "#6c757d",
                    ],
                    borderWidth: 2,
                    borderColor: "#ffffff",
                },
            ],
        };

        this.charts.repartition = new Chart(ctx, {
            type: "doughnut",
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return (
                                    context.label + ": " + context.parsed + "%"
                                );
                            },
                        },
                    },
                },
                animation: {
                    duration: this.config.animationDuration,
                },
            },
        });
    }

    /**
     * Graphique des prévisions (bar)
     */
    initPrevisionsChart(kpisData) {
        const ctx = document.getElementById("previsionsChart").getContext("2d");

        const labels = this.getNext3Months();
        const previsions = kpisData?.previsions || {};

        const recettesPrevues = labels.map((month) => {
            const monthKey = month.substring(0, 7); // YYYY-MM format
            return previsions[monthKey]?.recettes_prevues || 0;
        });

        const depensesPrevues = labels.map((month) => {
            const monthKey = month.substring(0, 7);
            return previsions[monthKey]?.depenses_prevues || 0;
        });

        this.charts.previsions = new Chart(ctx, {
            type: "bar",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Recettes prévues",
                        data: recettesPrevues,
                        backgroundColor: "rgba(40, 167, 69, 0.8)",
                        borderColor: "#28a745",
                        borderWidth: 1,
                    },
                    {
                        label: "Dépenses prévues",
                        data: depensesPrevues,
                        backgroundColor: "rgba(220, 53, 69, 0.8)",
                        borderColor: "#dc3545",
                        borderWidth: 1,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return (
                                    context.dataset.label +
                                    ": " +
                                    new Intl.NumberFormat("fr-FR").format(
                                        context.parsed.y
                                    ) +
                                    " FCFA"
                                );
                            },
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return new Intl.NumberFormat("fr-FR").format(
                                    value
                                );
                            },
                        },
                    },
                },
                animation: {
                    duration: this.config.animationDuration,
                },
            },
        });
    }

    /**
     * Graphique de performance mensuelle (line)
     */
    initPerformanceChart(kpisData) {
        const ctx = document
            .getElementById("performanceChart")
            .getContext("2d");

        const labels = this.getLast6Months();
        // Données fictives pour la performance - à remplacer par de vraies données
        const performance = [82, 85, 79, 88, 91, 87];

        this.charts.performance = new Chart(ctx, {
            type: "line",
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Taux de recouvrement (%)",
                        data: performance,
                        borderColor: "#007bff",
                        backgroundColor: "rgba(0, 123, 255, 0.1)",
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: "#007bff",
                        pointBorderColor: "#ffffff",
                        pointBorderWidth: 2,
                        pointRadius: 6,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function (value) {
                                return value + "%";
                            },
                        },
                    },
                },
                animation: {
                    duration: this.config.animationDuration,
                },
            },
        });
    }

    /**
     * Démarre l'auto-refresh des données
     */
    startAutoRefresh() {
        debugLog(
            `🔄 Auto-refresh démarré (${this.config.refreshInterval / 1000}s)`
        );

        this.refreshInterval = setInterval(() => {
            this.refreshDashboard();
        }, this.config.refreshInterval);
    }

    /**
     * Arrête l'auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
            debugLog("⏹️ Auto-refresh arrêté");
        }
    }

    /**
     * Actualise les données du dashboard via AJAX
     */
    async refreshDashboard() {
        if (this.isRefreshing) return;

        this.isRefreshing = true;
        this.showLoading();

        try {
            debugLog("🔄 Actualisation des données...");

            const response = await fetch(
                "/esbtp/comptabilite/kpis-temps-reel",
                {
                    method: "GET",
                    headers: {
                        Accept: "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                        "X-CSRF-TOKEN": document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute("content"),
                    },
                }
            );

            if (!response.ok) {
                throw new Error(
                    `HTTP ${response.status}: ${response.statusText}`
                );
            }

            const data = await response.json();

            if (data.success) {
                this.updateKPIs(data.data);
                this.updateCharts(data.data);
                this.updateLastUpdateTime();
                debugLog("✅ Données actualisées avec succès");
            } else {
                debugError(
                    "❌ Erreur lors de l'actualisation:",
                    data.message
                );
            }
        } catch (error) {
            debugError("❌ Erreur AJAX:", error);
        } finally {
            this.isRefreshing = false;
            this.hideLoading();
        }
    }

    /**
     * Met à jour les valeurs des KPIs
     */
    updateKPIs(data) {
        // Mise à jour des cartes KPI
        this.updateKPIValue("#kpi-recettes", data.recettes?.total, " FCFA");
        this.updateKPIValue("#kpi-depenses", data.depenses?.total, " FCFA");
        this.updateKPIValue(
            "#kpi-resultat",
            data.performance?.resultat_net,
            " FCFA"
        );
        this.updateKPIValue(
            "#kpi-recouvrement",
            data.paiements?.taux_recouvrement,
            "%"
        );

        // Mise à jour des indicateurs détaillés
        this.updateDetailedIndicators(data);
    }

    /**
     * Met à jour une valeur KPI avec animation
     */
    updateKPIValue(selector, newValue, unit = "") {
        const element = document.querySelector(`${selector} [data-kpi-value]`);
        if (element && newValue !== undefined) {
            element.classList.add("kpi-updating");

            setTimeout(() => {
                element.textContent =
                    new Intl.NumberFormat("fr-FR").format(newValue) + unit;
                element.classList.remove("kpi-updating");
            }, 300);
        }
    }

    /**
     * Met à jour les indicateurs détaillés
     */
    updateDetailedIndicators(data) {
        const container = document.getElementById("indicateursDetailles");
        if (container && data) {
            // Ici on peut mettre à jour les badges des indicateurs détaillés
            const badges = container.querySelectorAll(".badge");
            // Logique de mise à jour des badges...
        }
    }

    /**
     * Met à jour les graphiques avec nouvelles données
     */
    updateCharts(data) {
        // Mise à jour du graphique d'évolution si nécessaire
        if (this.charts.evolution) {
            // Logique de mise à jour des données du graphique
        }

        // Mise à jour d'autres graphiques...
    }

    /**
     * Met à jour l'heure de dernière mise à jour
     */
    updateLastUpdateTime() {
        const element = document.getElementById("lastUpdate");
        if (element) {
            element.textContent = new Date().toLocaleTimeString("fr-FR");
        }
    }

    /**
     * Affiche le loader
     */
    showLoading() {
        const loader = document.getElementById("loadingOverlay");
        if (loader) {
            loader.classList.remove("d-none");
        }
    }

    /**
     * Cache le loader
     */
    hideLoading() {
        const loader = document.getElementById("loadingOverlay");
        if (loader) {
            loader.classList.add("d-none");
        }
    }

    /**
     * Gestion des événements
     */
    bindEvents() {
        // Bouton de refresh manuel
        document.addEventListener("click", (e) => {
            if (e.target.closest("[data-refresh]")) {
                e.preventDefault();
                this.refreshDashboard();
            }
        });

        // Gestion de la visibilité de la page pour arrêter/reprendre l'auto-refresh
        document.addEventListener("visibilitychange", () => {
            if (document.hidden) {
                this.stopAutoRefresh();
            } else if (this.config.autoRefresh) {
                this.startAutoRefresh();
            }
        });
    }

    /**
     * Utilitaires pour les labels de dates
     */
    getLast12Months() {
        const months = [];
        const now = new Date();

        for (let i = 11; i >= 0; i--) {
            const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
            months.push(
                date.toLocaleDateString("fr-FR", {
                    month: "short",
                    year: "numeric",
                })
            );
        }

        return months;
    }

    getLast6Months() {
        const months = [];
        const now = new Date();

        for (let i = 5; i >= 0; i--) {
            const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
            months.push(
                date.toLocaleDateString("fr-FR", {
                    month: "short",
                    year: "numeric",
                })
            );
        }

        return months;
    }

    getNext3Months() {
        const months = [];
        const now = new Date();

        for (let i = 1; i <= 3; i++) {
            const date = new Date(now.getFullYear(), now.getMonth() + i, 1);
            months.push(
                date.toLocaleDateString("fr-FR", {
                    month: "short",
                    year: "numeric",
                })
            );
        }

        return months;
    }

    /**
     * Prépare les données d'évolution pour les graphiques
     */
    prepareEvolutionData(sourceData, labels) {
        // Convertit les données du serveur en format compatible Chart.js
        if (!sourceData || !Array.isArray(sourceData)) {
            return new Array(labels.length).fill(0);
        }

        return labels.map((label) => {
            const found = sourceData.find((item) => {
                // Logique de correspondance entre les labels et les données
                return item.periode === label;
            });
            return found ? found.valeur : 0;
        });
    }
}

/**
 * Fonctions globales pour les actions des composants
 */
function refreshChart(chartId) {
    debugLog(`🔄 Actualisation du graphique: ${chartId}`);
    // Logique de refresh spécifique au graphique
}

function downloadChart(chartId) {
    debugLog(`💾 Téléchargement du graphique: ${chartId}`);
    // Logique de téléchargement du graphique
}

function handleAlerte(alerteId) {
    debugLog(`🚨 Gestion de l'alerte: ${alerteId}`);
    // Logique de gestion des alertes
}

// Export pour utilisation globale
window.ComptabiliteManager = ComptabiliteManager;
