/**
 * Script de diagnostic pour les problèmes de modals Bootstrap
 * À exécuter dans la console DevTools du navigateur
 */

// Fonction de diagnostic complète
function diagnosticModal() {
    debugLog('=== DIAGNOSTIC MODAL KLASSCI ===');
    
    // 1. Vérifier si les fichiers CSS sont chargés
    const cssFiles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'));
    debugLog('📁 Fichiers CSS chargés:');
    cssFiles.forEach(link => {
        debugLog(`  - ${link.href}`);
    });
    
    // 2. Vérifier les z-index en temps réel
    debugLog('\n🎯 Z-index des éléments clés:');
    
    const navbar = document.querySelector('.nextadmin-navbar, .navbar');
    if (navbar) {
        debugLog(`  - Navbar: ${window.getComputedStyle(navbar).zIndex}`);
    }
    
    const sidebar = document.querySelector('#sidebar, .nextadmin-sidebar');
    if (sidebar) {
        debugLog(`  - Sidebar: ${window.getComputedStyle(sidebar).zIndex}`);
    }
    
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach((dropdown, index) => {
        debugLog(`  - Dropdown ${index}: ${window.getComputedStyle(dropdown).zIndex}`);
    });
    
    const modal = document.querySelector('#modalNouveauFournisseur');
    if (modal) {
        debugLog(`  - Modal: ${window.getComputedStyle(modal).zIndex}`);
    }
    
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) {
        debugLog(`  - Modal backdrop: ${window.getComputedStyle(backdrop).zIndex}`);
    }
    
    // 3. Tester l'ouverture du modal
    debugLog('\n🚀 Test d\'ouverture du modal:');
    const modalElement = document.querySelector('#modalNouveauFournisseur');
    const triggerButton = document.querySelector('[data-bs-target="#modalNouveauFournisseur"]');
    
    if (modalElement && triggerButton) {
        debugLog('  ✅ Modal et bouton trouvés');
        
        // Simuler l'ouverture
        triggerButton.click();
        
        setTimeout(() => {
            const isVisible = modalElement.classList.contains('show');
            debugLog(`  - Modal visible: ${isVisible}`);
            
            if (isVisible) {
                const modalRect = modalElement.getBoundingClientRect();
                debugLog(`  - Position modal:`, modalRect);
                
                // Vérifier si des éléments passent au-dessus
                const elementsAtCenter = document.elementsFromPoint(
                    window.innerWidth / 2, 
                    window.innerHeight / 2
                );
                debugLog('  - Éléments au centre de l\'écran:', elementsAtCenter);
            }
        }, 500);
    } else {
        debugLog('  ❌ Modal ou bouton introuvable');
    }
    
    // 4. Vérifier les conflits CSS
    debugLog('\n⚠️ Conflits potentiels:');
    const allElements = document.querySelectorAll('*');
    const highZIndex = [];
    
    allElements.forEach(el => {
        const zIndex = parseInt(window.getComputedStyle(el).zIndex);
        if (zIndex > 1060) {
            highZIndex.push({
                element: el.tagName + (el.id ? '#' + el.id : '') + (el.className ? '.' + el.className.split(' ').join('.') : ''),
                zIndex: zIndex
            });
        }
    });
    
    if (highZIndex.length > 0) {
        debugLog('  🔴 Éléments avec z-index > 1060:');
        highZIndex.forEach(item => {
            debugLog(`    - ${item.element}: ${item.zIndex}`);
        });
    } else {
        debugLog('  ✅ Aucun conflit z-index détecté');
    }
    
    debugLog('\n=== FIN DIAGNOSTIC ===');
}

// Fonction pour forcer l'affichage du modal
function forceModal() {
    debugLog('🔧 FORCE MODAL - Mode manuel');
    
    const modal = document.querySelector('#modalNouveauFournisseur');
    if (modal) {
        // Appliquer les styles de force
        modal.style.cssText = `
            z-index: 9999 !important;
            position: fixed !important;
            top: 50% !important;
            left: 50% !important;
            transform: translate(-50%, -50%) !important;
            display: block !important;
            background: white !important;
            border: 3px solid red !important;
            box-shadow: 0 0 30px rgba(0,0,0,0.7) !important;
        `;
        
        modal.classList.add('show');
        
        // Créer un backdrop manuel
        let backdrop = document.querySelector('.modal-backdrop');
        if (!backdrop) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.style.cssText = `
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background-color: rgba(0,0,0,0.5) !important;
                z-index: 9998 !important;
            `;
            document.body.appendChild(backdrop);
        }
        
        debugLog('✅ Modal forcé en position');
    } else {
        debugLog('❌ Modal introuvable');
    }
}

// Lancer le diagnostic au chargement de la page
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', diagnosticModal);
} else {
    diagnosticModal();
}

debugLog('🔍 Script de diagnostic chargé. Utilisez diagnosticModal() ou forceModal() dans la console.');
