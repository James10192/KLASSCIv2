// ===== SCRIPT DE DIAGNOSTIC NAVBAR ET SIDEBAR =====

document.addEventListener("DOMContentLoaded", function () {
    debugLog("🔍 DIAGNOSTIC NAVBAR ET SIDEBAR - DÉBUT");

    // 1. Vérifier Bootstrap
    debugLog("📦 Vérification de Bootstrap...");
    if (typeof bootstrap !== "undefined") {
        debugLog("✅ Bootstrap est chargé:", bootstrap);
    } else {
        debugError("❌ Bootstrap n'est pas chargé !");
    }

    // 2. Vérifier jQuery (si utilisé)
    if (typeof $ !== "undefined") {
        debugLog("✅ jQuery est chargé:", $.fn.jquery);
    } else {
        debugLog(
            "ℹ️ jQuery n'est pas chargé (normal si on utilise Bootstrap 5)"
        );
    }

    // 3. Diagnostic des dropdowns
    debugLog("🔽 Diagnostic des dropdowns...");
    const dropdownElements = document.querySelectorAll(
        '[data-bs-toggle="dropdown"]'
    );
    debugLog(`📊 Nombre de dropdowns trouvés: ${dropdownElements.length}`);

    dropdownElements.forEach((element, index) => {
        debugLog(`🔽 Dropdown ${index + 1}:`, {
            element: element,
            id: element.id,
            classes: element.className,
            "data-bs-toggle": element.getAttribute("data-bs-toggle"),
            "aria-expanded": element.getAttribute("aria-expanded"),
        });

        // Vérifier si Bootstrap dropdown est initialisé
        try {
            const dropdownInstance = bootstrap.Dropdown.getInstance(element);
            if (dropdownInstance) {
                debugLog(`✅ Dropdown ${index + 1} est initialisé`);
            } else {
                debugLog(
                    `⚠️ Dropdown ${
                        index + 1
                    } n'est pas initialisé, tentative d'initialisation...`
                );
                new bootstrap.Dropdown(element);
                debugLog(`✅ Dropdown ${index + 1} initialisé manuellement`);
            }
        } catch (error) {
            debugError(`❌ Erreur avec dropdown ${index + 1}:`, error);
        }
    });

    // 4. Diagnostic du burger toggle
    debugLog("🍔 Diagnostic du burger toggle...");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    if (sidebarToggle) {
        debugLog("✅ Burger toggle trouvé:", sidebarToggle);

        // Vérifier les event listeners
        sidebarToggle.addEventListener("click", function (e) {
            debugLog("🍔 Burger toggle cliqué !");
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebar-overlay");

            if (sidebar) {
                debugLog("📱 État actuel de la sidebar:", {
                    classes: sidebar.className,
                    display: window.getComputedStyle(sidebar).display,
                    left: window.getComputedStyle(sidebar).left,
                });

                // Toggle des classes
                sidebar.classList.toggle("show");
                if (overlay) {
                    overlay.classList.toggle("show");
                }

                debugLog("📱 Nouvel état de la sidebar:", {
                    classes: sidebar.className,
                    display: window.getComputedStyle(sidebar).display,
                    left: window.getComputedStyle(sidebar).left,
                });
            } else {
                debugError("❌ Sidebar non trouvée !");
            }
        });
    } else {
        debugError("❌ Burger toggle non trouvé !");
    }

    // 5. Diagnostic des accordéons de la sidebar
    debugLog("🎵 Diagnostic des accordéons de la sidebar...");
    const accordionButtons = document.querySelectorAll(".menu-accordion-btn");
    debugLog(`📊 Nombre d'accordéons trouvés: ${accordionButtons.length}`);

    accordionButtons.forEach((button, index) => {
        debugLog(`🎵 Accordéon ${index + 1}:`, {
            element: button,
            classes: button.className,
            nextSibling: button.nextElementSibling,
        });

        // Ajouter event listener pour diagnostic
        button.addEventListener("click", function (e) {
            debugLog(`🎵 Accordéon ${index + 1} cliqué !`);
            const content = this.nextElementSibling;
            if (
                content &&
                content.classList.contains("menu-accordion-content")
            ) {
                content.classList.toggle("show");
                this.classList.toggle("active");
                debugLog(`🎵 Accordéon ${index + 1} état:`, {
                    buttonActive: this.classList.contains("active"),
                    contentShow: content.classList.contains("show"),
                });
            } else {
                debugError(
                    `❌ Contenu d'accordéon ${index + 1} non trouvé !`
                );
            }
        });
    });

    // 6. Diagnostic de la scrollbar de la sidebar
    debugLog("📜 Diagnostic de la scrollbar...");
    const sidebarMenu = document.querySelector(".sidebar-menu");
    if (sidebarMenu) {
        debugLog("✅ Sidebar menu trouvé:", {
            element: sidebarMenu,
            scrollHeight: sidebarMenu.scrollHeight,
            clientHeight: sidebarMenu.clientHeight,
            offsetHeight: sidebarMenu.offsetHeight,
            maxHeight: window.getComputedStyle(sidebarMenu).maxHeight,
            overflowY: window.getComputedStyle(sidebarMenu).overflowY,
        });

        // Vérifier si le contenu dépasse
        if (sidebarMenu.scrollHeight > sidebarMenu.clientHeight) {
            debugLog(
                "⚠️ Le contenu de la sidebar dépasse la hauteur disponible"
            );
        } else {
            debugLog(
                "✅ Le contenu de la sidebar tient dans la hauteur disponible"
            );
        }
    } else {
        debugError("❌ Sidebar menu non trouvé !");
    }

    // 7. Diagnostic des notifications
    debugLog("🔔 Diagnostic des notifications...");
    const notificationsBtn = document.getElementById("notificationsDropdown");
    if (notificationsBtn) {
        debugLog("✅ Bouton notifications trouvé");
        notificationsBtn.addEventListener("click", function () {
            debugLog("🔔 Bouton notifications cliqué !");
        });
    } else {
        debugError("❌ Bouton notifications non trouvé !");
    }

    // 8. Diagnostic du search input
    debugLog("🔍 Diagnostic du search input...");
    const searchInput = document.getElementById("global-search");
    if (searchInput) {
        debugLog("✅ Search input trouvé");
        searchInput.addEventListener("input", function () {
            debugLog("🔍 Search input utilisé, valeur:", this.value);
        });
        searchInput.addEventListener("focus", function () {
            debugLog("🔍 Search input focus");
        });
        searchInput.addEventListener("blur", function () {
            debugLog("🔍 Search input blur");
        });
    } else {
        debugError("❌ Search input non trouvé !");
    }

    // 9. Diagnostic des actions rapides
    debugLog("⚡ Diagnostic des actions rapides...");
    const quickActionsBtn = document.getElementById("quickActionsDropdown");
    if (quickActionsBtn) {
        debugLog("✅ Bouton actions rapides trouvé");
        quickActionsBtn.addEventListener("click", function () {
            debugLog("⚡ Bouton actions rapides cliqué !");
        });
    } else {
        debugError("❌ Bouton actions rapides non trouvé !");
    }

    // 10. Vérifier les routes (les routes sont définies dans le HTML via data-attributes)
    debugLog("🛣️ Vérification des routes...");
    const notifBtn = document.getElementById('notificationsDropdown');
    const msgBtn = document.getElementById('messagesDropdown');
    const actionBtn = document.getElementById('quickActionsDropdown');

    if (notifBtn) {
        debugLog('🛣️ Route notifications:', notifBtn.dataset.route || '/navbar/notifications');
    }
    if (msgBtn) {
        debugLog('🛣️ Route messages:', msgBtn.dataset.route || '/navbar/messages');
    }
    if (actionBtn) {
        debugLog('🛣️ Route quickActions:', actionBtn.dataset.route || '/navbar/quick-actions');
    }

    debugLog("🔍 DIAGNOSTIC NAVBAR ET SIDEBAR - FIN");
});

// Fonction pour tester manuellement les dropdowns
window.testDropdowns = function () {
    debugLog("🧪 Test manuel des dropdowns...");
    const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    dropdowns.forEach((dropdown, index) => {
        try {
            const instance = new bootstrap.Dropdown(dropdown);
            debugLog(`✅ Dropdown ${index + 1} réinitialisé`);
        } catch (error) {
            debugError(`❌ Erreur dropdown ${index + 1}:`, error);
        }
    });
};

// Fonction pour tester manuellement la sidebar
window.testSidebar = function () {
    debugLog("🧪 Test manuel de la sidebar...");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");

    if (sidebar) {
        sidebar.classList.toggle("show");
        if (overlay) overlay.classList.toggle("show");
        debugLog("✅ Sidebar togglée");
    } else {
        debugError("❌ Sidebar non trouvée");
    }
};

// Fonction pour forcer l'initialisation de Bootstrap
window.forceBootstrapInit = function () {
    debugLog("🔄 Initialisation forcée de Bootstrap...");

    // Initialiser tous les dropdowns
    document
        .querySelectorAll('[data-bs-toggle="dropdown"]')
        .forEach((element) => {
            new bootstrap.Dropdown(element);
        });

    // Initialiser tous les tooltips
    document
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach((element) => {
            new bootstrap.Tooltip(element);
        });

    // Initialiser tous les popovers
    document
        .querySelectorAll('[data-bs-toggle="popover"]')
        .forEach((element) => {
            new bootstrap.Popover(element);
        });

    debugLog("✅ Bootstrap réinitialisé");
};
