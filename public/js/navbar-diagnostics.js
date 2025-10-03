// ===== SCRIPT DE DIAGNOSTIC NAVBAR ET SIDEBAR =====

document.addEventListener("DOMContentLoaded", function () {
    console.log("🔍 DIAGNOSTIC NAVBAR ET SIDEBAR - DÉBUT");

    // 1. Vérifier Bootstrap
    console.log("📦 Vérification de Bootstrap...");
    if (typeof bootstrap !== "undefined") {
        console.log("✅ Bootstrap est chargé:", bootstrap);
    } else {
        console.error("❌ Bootstrap n'est pas chargé !");
    }

    // 2. Vérifier jQuery (si utilisé)
    if (typeof $ !== "undefined") {
        console.log("✅ jQuery est chargé:", $.fn.jquery);
    } else {
        console.log(
            "ℹ️ jQuery n'est pas chargé (normal si on utilise Bootstrap 5)"
        );
    }

    // 3. Diagnostic des dropdowns
    console.log("🔽 Diagnostic des dropdowns...");
    const dropdownElements = document.querySelectorAll(
        '[data-bs-toggle="dropdown"]'
    );
    console.log(`📊 Nombre de dropdowns trouvés: ${dropdownElements.length}`);

    dropdownElements.forEach((element, index) => {
        console.log(`🔽 Dropdown ${index + 1}:`, {
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
                console.log(`✅ Dropdown ${index + 1} est initialisé`);
            } else {
                console.log(
                    `⚠️ Dropdown ${
                        index + 1
                    } n'est pas initialisé, tentative d'initialisation...`
                );
                new bootstrap.Dropdown(element);
                console.log(`✅ Dropdown ${index + 1} initialisé manuellement`);
            }
        } catch (error) {
            console.error(`❌ Erreur avec dropdown ${index + 1}:`, error);
        }
    });

    // 4. Diagnostic du burger toggle
    console.log("🍔 Diagnostic du burger toggle...");
    const sidebarToggle = document.getElementById("sidebar-toggle");
    if (sidebarToggle) {
        console.log("✅ Burger toggle trouvé:", sidebarToggle);

        // Vérifier les event listeners
        sidebarToggle.addEventListener("click", function (e) {
            console.log("🍔 Burger toggle cliqué !");
            const sidebar = document.getElementById("sidebar");
            const overlay = document.getElementById("sidebar-overlay");

            if (sidebar) {
                console.log("📱 État actuel de la sidebar:", {
                    classes: sidebar.className,
                    display: window.getComputedStyle(sidebar).display,
                    left: window.getComputedStyle(sidebar).left,
                });

                // Toggle des classes
                sidebar.classList.toggle("show");
                if (overlay) {
                    overlay.classList.toggle("show");
                }

                console.log("📱 Nouvel état de la sidebar:", {
                    classes: sidebar.className,
                    display: window.getComputedStyle(sidebar).display,
                    left: window.getComputedStyle(sidebar).left,
                });
            } else {
                console.error("❌ Sidebar non trouvée !");
            }
        });
    } else {
        console.error("❌ Burger toggle non trouvé !");
    }

    // 5. Diagnostic des accordéons de la sidebar
    console.log("🎵 Diagnostic des accordéons de la sidebar...");
    const accordionButtons = document.querySelectorAll(".menu-accordion-btn");
    console.log(`📊 Nombre d'accordéons trouvés: ${accordionButtons.length}`);

    accordionButtons.forEach((button, index) => {
        console.log(`🎵 Accordéon ${index + 1}:`, {
            element: button,
            classes: button.className,
            nextSibling: button.nextElementSibling,
        });

        // Ajouter event listener pour diagnostic
        button.addEventListener("click", function (e) {
            console.log(`🎵 Accordéon ${index + 1} cliqué !`);
            const content = this.nextElementSibling;
            if (
                content &&
                content.classList.contains("menu-accordion-content")
            ) {
                content.classList.toggle("show");
                this.classList.toggle("active");
                console.log(`🎵 Accordéon ${index + 1} état:`, {
                    buttonActive: this.classList.contains("active"),
                    contentShow: content.classList.contains("show"),
                });
            } else {
                console.error(
                    `❌ Contenu d'accordéon ${index + 1} non trouvé !`
                );
            }
        });
    });

    // 6. Diagnostic de la scrollbar de la sidebar
    console.log("📜 Diagnostic de la scrollbar...");
    const sidebarMenu = document.querySelector(".sidebar-menu");
    if (sidebarMenu) {
        console.log("✅ Sidebar menu trouvé:", {
            element: sidebarMenu,
            scrollHeight: sidebarMenu.scrollHeight,
            clientHeight: sidebarMenu.clientHeight,
            offsetHeight: sidebarMenu.offsetHeight,
            maxHeight: window.getComputedStyle(sidebarMenu).maxHeight,
            overflowY: window.getComputedStyle(sidebarMenu).overflowY,
        });

        // Vérifier si le contenu dépasse
        if (sidebarMenu.scrollHeight > sidebarMenu.clientHeight) {
            console.log(
                "⚠️ Le contenu de la sidebar dépasse la hauteur disponible"
            );
        } else {
            console.log(
                "✅ Le contenu de la sidebar tient dans la hauteur disponible"
            );
        }
    } else {
        console.error("❌ Sidebar menu non trouvé !");
    }

    // 7. Diagnostic des notifications
    console.log("🔔 Diagnostic des notifications...");
    const notificationsBtn = document.getElementById("notificationsDropdown");
    if (notificationsBtn) {
        console.log("✅ Bouton notifications trouvé");
        notificationsBtn.addEventListener("click", function () {
            console.log("🔔 Bouton notifications cliqué !");
        });
    } else {
        console.error("❌ Bouton notifications non trouvé !");
    }

    // 8. Diagnostic du search input
    console.log("🔍 Diagnostic du search input...");
    const searchInput = document.getElementById("global-search");
    if (searchInput) {
        console.log("✅ Search input trouvé");
        searchInput.addEventListener("input", function () {
            console.log("🔍 Search input utilisé, valeur:", this.value);
        });
        searchInput.addEventListener("focus", function () {
            console.log("🔍 Search input focus");
        });
        searchInput.addEventListener("blur", function () {
            console.log("🔍 Search input blur");
        });
    } else {
        console.error("❌ Search input non trouvé !");
    }

    // 9. Diagnostic des actions rapides
    console.log("⚡ Diagnostic des actions rapides...");
    const quickActionsBtn = document.getElementById("quickActionsDropdown");
    if (quickActionsBtn) {
        console.log("✅ Bouton actions rapides trouvé");
        quickActionsBtn.addEventListener("click", function () {
            console.log("⚡ Bouton actions rapides cliqué !");
        });
    } else {
        console.error("❌ Bouton actions rapides non trouvé !");
    }

    // 10. Vérifier les routes (les routes sont définies dans le HTML via data-attributes)
    console.log("🛣️ Vérification des routes...");
    const notifBtn = document.getElementById('notificationsDropdown');
    const msgBtn = document.getElementById('messagesDropdown');
    const actionBtn = document.getElementById('quickActionsDropdown');

    if (notifBtn) {
        console.log('🛣️ Route notifications:', notifBtn.dataset.route || '/navbar/notifications');
    }
    if (msgBtn) {
        console.log('🛣️ Route messages:', msgBtn.dataset.route || '/navbar/messages');
    }
    if (actionBtn) {
        console.log('🛣️ Route quickActions:', actionBtn.dataset.route || '/navbar/quick-actions');
    }

    console.log("🔍 DIAGNOSTIC NAVBAR ET SIDEBAR - FIN");
});

// Fonction pour tester manuellement les dropdowns
window.testDropdowns = function () {
    console.log("🧪 Test manuel des dropdowns...");
    const dropdowns = document.querySelectorAll('[data-bs-toggle="dropdown"]');
    dropdowns.forEach((dropdown, index) => {
        try {
            const instance = new bootstrap.Dropdown(dropdown);
            console.log(`✅ Dropdown ${index + 1} réinitialisé`);
        } catch (error) {
            console.error(`❌ Erreur dropdown ${index + 1}:`, error);
        }
    });
};

// Fonction pour tester manuellement la sidebar
window.testSidebar = function () {
    console.log("🧪 Test manuel de la sidebar...");
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");

    if (sidebar) {
        sidebar.classList.toggle("show");
        if (overlay) overlay.classList.toggle("show");
        console.log("✅ Sidebar togglée");
    } else {
        console.error("❌ Sidebar non trouvée");
    }
};

// Fonction pour forcer l'initialisation de Bootstrap
window.forceBootstrapInit = function () {
    console.log("🔄 Initialisation forcée de Bootstrap...");

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

    console.log("✅ Bootstrap réinitialisé");
};
