/**
 * Système de Lazy Loading et Optimisation des Performances Frontend
 * Pour KLASSCI - Module Comptabilité
 * Implémentation Task #9: Optimisation des performances et mise en cache
 */

class LazyLoadingManager {
    constructor() {
        this.observers = new Map();
        this.loadedModules = new Set();
        this.performanceMetrics = {
            loadTimes: [],
            cacheHits: 0,
            cacheMisses: 0,
        };
        this.initializeIntersectionObserver();
        this.setupPerformanceMonitoring();
    }

    /**
     * Initialise l'Intersection Observer pour le lazy loading
     */
    initializeIntersectionObserver() {
        if ("IntersectionObserver" in window) {
            // Observer pour les images
            this.imageObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.loadImage(entry.target);
                            this.imageObserver.unobserve(entry.target);
                        }
                    });
                },
                {
                    rootMargin: "50px 0px",
                    threshold: 0.1,
                }
            );

            // Observer pour les modules JavaScript
            this.moduleObserver = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.loadModule(entry.target);
                            this.moduleObserver.unobserve(entry.target);
                        }
                    });
                },
                {
                    rootMargin: "100px 0px",
                    threshold: 0.1,
                }
            );

            this.setupLazyLoading();
        } else {
            // Fallback pour les navigateurs non supportés
            this.loadAllAssets();
        }
    }

    /**
     * Configure le lazy loading pour tous les éléments
     */
    setupLazyLoading() {
        // Images lazy loading
        document.querySelectorAll("img[data-src]").forEach((img) => {
            this.imageObserver.observe(img);
        });

        // Modules JavaScript lazy loading
        document.querySelectorAll("[data-lazy-module]").forEach((element) => {
            this.moduleObserver.observe(element);
        });

        // Tableaux avec pagination
        this.setupTableLazyLoading();

        // Graphiques/Charts
        this.setupChartLazyLoading();
    }

    /**
     * Charge une image de manière optimisée
     */
    loadImage(img) {
        const startTime = performance.now();

        // Vérifier si l'image est en cache
        const cacheKey = `img_${this.hashString(img.dataset.src)}`;
        const cached = this.getFromCache(cacheKey);

        if (cached) {
            img.src = cached;
            this.performanceMetrics.cacheHits++;
            this.logPerformance(
                "image_cache_hit",
                performance.now() - startTime
            );
            return;
        }

        // Précharger avec des dimensions optimisées
        const preloader = new Image();

        preloader.onload = () => {
            img.src = preloader.src;
            img.classList.remove("lazy-loading");
            img.classList.add("lazy-loaded");

            // Mise en cache
            this.setCache(cacheKey, preloader.src);
            this.performanceMetrics.cacheMisses++;
            this.logPerformance("image_loaded", performance.now() - startTime);
        };

        preloader.onerror = () => {
            img.src = "/images/placeholder-error.png";
            img.classList.add("lazy-error");
            this.logPerformance("image_error", performance.now() - startTime);
        };

        // Optimisation responsive
        const devicePixelRatio = window.devicePixelRatio || 1;
        const width = img.offsetWidth * devicePixelRatio;
        const src = this.getOptimizedImageSrc(img.dataset.src, width);

        img.classList.add("lazy-loading");
        preloader.src = src;
    }

    /**
     * Charge un module JavaScript de manière asynchrone
     */
    async loadModule(element) {
        const moduleName = element.dataset.lazyModule;

        if (this.loadedModules.has(moduleName)) {
            return; // Module déjà chargé
        }

        const startTime = performance.now();

        try {
            // Vérifier le cache des modules
            const cacheKey = `module_${moduleName}`;
            let moduleCode = this.getFromCache(cacheKey);

            if (!moduleCode) {
                // Charger le module
                const response = await fetch(`/js/modules/${moduleName}.js`);
                moduleCode = await response.text();

                // Mettre en cache
                this.setCache(cacheKey, moduleCode, 3600000); // 1 heure
                this.performanceMetrics.cacheMisses++;
            } else {
                this.performanceMetrics.cacheHits++;
            }

            // Exécuter le module
            this.executeModule(moduleCode, moduleName);
            this.loadedModules.add(moduleName);

            this.logPerformance("module_loaded", performance.now() - startTime);
        } catch (error) {
            debugError(`Erreur chargement module ${moduleName}:`, error);
            this.logPerformance("module_error", performance.now() - startTime);
        }
    }

    /**
     * Configure le lazy loading pour les tableaux
     */
    setupTableLazyLoading() {
        document.querySelectorAll(".data-table-lazy").forEach((table) => {
            const tbody = table.querySelector("tbody");
            if (!tbody) return;

            // Charger seulement les 20 premières lignes
            const rows = tbody.querySelectorAll("tr");
            const visibleRows = 20;

            // Masquer les lignes supplémentaires
            for (let i = visibleRows; i < rows.length; i++) {
                rows[i].style.display = "none";
                rows[i].classList.add("lazy-row");
            }

            // Ajouter bouton "Charger plus"
            if (rows.length > visibleRows) {
                this.addLoadMoreButton(table, rows, visibleRows);
            }
        });
    }

    /**
     * Configure le lazy loading pour les graphiques
     */
    setupChartLazyLoading() {
        document.querySelectorAll("[data-chart-type]").forEach((canvas) => {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.loadChart(entry.target);
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.1 }
            );

            observer.observe(canvas);
        });
    }

    /**
     * Charge un graphique de manière optimisée
     */
    async loadChart(canvas) {
        const chartType = canvas.dataset.chartType;
        const dataUrl = canvas.dataset.dataUrl;

        const startTime = performance.now();

        try {
            // Vérifier le cache des données
            const cacheKey = `chart_${this.hashString(dataUrl)}`;
            let data = this.getFromCache(cacheKey);

            if (!data) {
                const response = await fetch(dataUrl);
                data = await response.json();
                this.setCache(cacheKey, data, 900000); // 15 minutes
                this.performanceMetrics.cacheMisses++;
            } else {
                this.performanceMetrics.cacheHits++;
            }

            // Charger Chart.js si nécessaire
            if (typeof Chart === "undefined") {
                await this.loadScript("/js/vendor/chart.min.js");
            }

            // Créer le graphique avec configuration optimisée
            new Chart(canvas, this.getOptimizedChartConfig(chartType, data));

            this.logPerformance("chart_loaded", performance.now() - startTime);
        } catch (error) {
            debugError("Erreur chargement graphique:", error);
            this.showChartError(canvas);
        }
    }

    /**
     * Ajoute un bouton "Charger plus" pour les tableaux
     */
    addLoadMoreButton(table, rows, currentVisible) {
        const loadMoreBtn = document.createElement("button");
        loadMoreBtn.className = "btn btn-outline-primary btn-sm load-more-btn";
        loadMoreBtn.innerHTML = '<i class="fas fa-plus"></i> Charger plus';

        let visibleCount = currentVisible;
        const batchSize = 20;

        loadMoreBtn.addEventListener("click", () => {
            const endIndex = Math.min(visibleCount + batchSize, rows.length);

            for (let i = visibleCount; i < endIndex; i++) {
                rows[i].style.display = "";
                rows[i].classList.remove("lazy-row");
            }

            visibleCount = endIndex;

            if (visibleCount >= rows.length) {
                loadMoreBtn.remove();
            } else {
                loadMoreBtn.innerHTML = `<i class="fas fa-plus"></i> Charger plus (${
                    rows.length - visibleCount
                } restantes)`;
            }
        });

        table.parentNode.insertBefore(loadMoreBtn, table.nextSibling);
    }

    /**
     * Exécute un module JavaScript
     */
    executeModule(code, moduleName) {
        try {
            // Créer un contexte sécurisé pour le module
            const moduleFunction = new Function("module", "exports", code);
            const moduleExports = {};
            const moduleObject = { exports: moduleExports };

            moduleFunction(moduleObject, moduleExports);

            // Stocker les exports si nécessaire
            if (Object.keys(moduleExports).length > 0) {
                window.LazyModules = window.LazyModules || {};
                window.LazyModules[moduleName] = moduleExports;
            }
        } catch (error) {
            debugError(`Erreur exécution module ${moduleName}:`, error);
        }
    }

    /**
     * Charge un script externe
     */
    loadScript(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = src;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Cache management
     */
    setCache(key, value, ttl = 1800000) {
        // 30 minutes par défaut
        const item = {
            value: value,
            expiry: Date.now() + ttl,
        };

        try {
            localStorage.setItem(`lazy_cache_${key}`, JSON.stringify(item));
        } catch (e) {
            // Storage plein, nettoyer le cache
            this.cleanupCache();
        }
    }

    getFromCache(key) {
        try {
            const item = localStorage.getItem(`lazy_cache_${key}`);
            if (!item) return null;

            const parsed = JSON.parse(item);
            if (Date.now() > parsed.expiry) {
                localStorage.removeItem(`lazy_cache_${key}`);
                return null;
            }

            return parsed.value;
        } catch (e) {
            return null;
        }
    }

    /**
     * Nettoie le cache expiré
     */
    cleanupCache() {
        const keys = Object.keys(localStorage);
        const lazyKeys = keys.filter((key) => key.startsWith("lazy_cache_"));

        lazyKeys.forEach((key) => {
            try {
                const item = JSON.parse(localStorage.getItem(key));
                if (Date.now() > item.expiry) {
                    localStorage.removeItem(key);
                }
            } catch (e) {
                localStorage.removeItem(key);
            }
        });
    }

    /**
     * Surveillance des performances
     */
    setupPerformanceMonitoring() {
        // Observer la navigation
        if ("PerformanceObserver" in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === "navigation") {
                        this.logNavigationMetrics(entry);
                    }
                });
            });

            observer.observe({ entryTypes: ["navigation"] });
        }

        // Monitorer la mémoire
        this.monitorMemoryUsage();

        // Rapporter les métriques périodiquement
        setInterval(() => {
            this.reportMetrics();
        }, 60000); // Chaque minute
    }

    /**
     * Log des métriques de performance
     */
    logPerformance(action, duration) {
        this.performanceMetrics.loadTimes.push({
            action: action,
            duration: duration,
            timestamp: Date.now(),
        });

        // Garder seulement les 100 dernières métriques
        if (this.performanceMetrics.loadTimes.length > 100) {
            this.performanceMetrics.loadTimes =
                this.performanceMetrics.loadTimes.slice(-100);
        }
    }

    /**
     * Monitore l'utilisation mémoire
     */
    monitorMemoryUsage() {
        if ("memory" in performance) {
            const memory = performance.memory;

            if (memory.usedJSHeapSize > memory.jsHeapSizeLimit * 0.8) {
                debugWarn(
                    "Utilisation mémoire élevée, nettoyage du cache..."
                );
                this.cleanupCache();

                // Forcer le garbage collection si possible
                if (window.gc) {
                    window.gc();
                }
            }
        }
    }

    /**
     * Rapporte les métriques au serveur
     */
    reportMetrics() {
        const metrics = {
            cache_hits: this.performanceMetrics.cacheHits,
            cache_misses: this.performanceMetrics.cacheMisses,
            average_load_time: this.calculateAverageLoadTime(),
            loaded_modules: Array.from(this.loadedModules),
            timestamp: Date.now(),
        };

        // Envoyer au serveur de manière asynchrone
        if (navigator.sendBeacon) {
            navigator.sendBeacon(
                "/api/performance-metrics",
                JSON.stringify(metrics)
            );
        } else {
            fetch("/api/performance-metrics", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(metrics),
            }).catch(() => {}); // Ignorer les erreurs
        }
    }

    /**
     * Utilitaires
     */
    hashString(str) {
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = (hash << 5) - hash + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return Math.abs(hash);
    }

    getOptimizedImageSrc(src, width) {
        // Ajouter des paramètres de redimensionnement si supporté
        const url = new URL(src, window.location.origin);
        url.searchParams.set("w", Math.ceil(width));
        url.searchParams.set("q", "85"); // Qualité
        return url.toString();
    }

    getOptimizedChartConfig(type, data) {
        return {
            type: type,
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 0, // Désactiver animations pour performance
                },
                plugins: {
                    legend: {
                        display: true,
                    },
                },
            },
        };
    }

    calculateAverageLoadTime() {
        if (this.performanceMetrics.loadTimes.length === 0) return 0;

        const total = this.performanceMetrics.loadTimes.reduce(
            (sum, metric) => sum + metric.duration,
            0
        );
        return total / this.performanceMetrics.loadTimes.length;
    }

    showChartError(canvas) {
        const container = canvas.parentNode;
        container.innerHTML = `
            <div class="alert alert-warning text-center">
                <i class="fas fa-chart-line"></i>
                <p>Impossible de charger le graphique</p>
                <button class="btn btn-sm btn-outline-primary" onclick="window.lazyLoader.loadChart(this.closest('.alert').previousElementSibling)">
                    Réessayer
                </button>
            </div>
        `;
    }

    loadAllAssets() {
        // Fallback: charger tous les assets immédiatement
        document.querySelectorAll("img[data-src]").forEach((img) => {
            img.src = img.dataset.src;
        });

        document.querySelectorAll("[data-lazy-module]").forEach((element) => {
            this.loadModule(element);
        });
    }
}

// Initialisation automatique
document.addEventListener("DOMContentLoaded", () => {
    window.lazyLoader = new LazyLoadingManager();
    debugInfo("✅ Lazy Loading Manager initialisé");
});

// Export pour utilisation externe
if (typeof module !== "undefined" && module.exports) {
    module.exports = LazyLoadingManager;
}
