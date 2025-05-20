/**
 * KLASSCI - Système de Remplacement Intelligent d'Images
 * 
 * Ce script remplace automatiquement les images manquantes par des illustrations
 * thématiques correspondant au contexte dans lequel elles sont utilisées.
 * 
 * Fonctionnalités:
 * - Détection des images manquantes
 * - Analyse du contexte de l'image (section, texte environnant)
 * - Remplacement par une illustration thématique adaptée
 * - Support des images de fond CSS
 */

document.addEventListener('DOMContentLoaded', function() {
    // Configuration
    const config = {
        thematicImages: {
            admin: 'images/admin-image.png',
            teacher: 'images/teacher-image.png',
            devices: 'images/devices-mockup.png',
            logo: 'images/LOGO-KLASSCI-PNG.png',
            chart: 'icons/chart-icon.png',
            user: 'icons/user-icon.png',
            cog: 'icons/cog-icon.png',
            badge: 'icons/badge-klassci.png'
        },
        // Mots-clés pour identifier le contexte
        contextKeywords: {
            admin: ['admin', 'directeur', 'direction', 'gestion', 'administration'],
            teacher: ['enseignant', 'professeur', 'éducation', 'enseigner', 'cours'],
            devices: ['appareil', 'mobile', 'tablette', 'ordinateur', 'technologie'],
            chart: ['graphique', 'statistique', 'données', 'analyse'],
            user: ['utilisateur', 'étudiant', 'élève', 'personne'],
            cog: ['paramètre', 'configuration', 'système', 'réglage']
        },
        // Classes pour les effets visuels
        cssClasses: {
            fadeIn: 'img-fade-in',
            pulse: 'img-pulse',
            bounce: 'img-bounce'
        }
    };

    // Créer les styles pour les animations
    createStyles();

    // Initialiser le processus de remplacement
    initImageReplacement();

    // Ajouter un observateur pour capturer les images ajoutées dynamiquement
    observeDynamicImages();

    /**
     * Initialise le processus de remplacement d'images
     */
    function initImageReplacement() {
        // Rechercher toutes les images dans le document
        const images = document.querySelectorAll('img');
        images.forEach(processImage);

        // Rechercher les éléments avec des images d'arrière-plan
        processCssBackgroundImages();
    }

    /**
     * Traite une image pour vérifier si elle doit être remplacée
     * @param {HTMLImageElement} img - L'élément image à traiter
     */
    function processImage(img) {
        img.addEventListener('error', function() {
            replaceWithThematicImage(img);
        });

        // Pour les images déjà en erreur
        if (img.complete && (img.naturalWidth === 0 || img.naturalHeight === 0)) {
            replaceWithThematicImage(img);
        }
    }

    /**
     * Remplace une image manquante par une illustration thématique
     * @param {HTMLImageElement} img - L'image à remplacer
     */
    function replaceWithThematicImage(img) {
        // Analyser le contexte pour déterminer le type d'image
        const contextType = analyzeContext(img);
        
        // Obtenir l'image thématique appropriée
        const thematicSrc = getThematicImage(contextType);
        
        // Remplacer l'image
        if (thematicSrc) {
            // Conserver la taille originale si disponible
            const width = img.width || img.getAttribute('width');
            const height = img.height || img.getAttribute('height');
            const classNames = img.className;
            const altText = img.getAttribute('alt') || 'Image illustrative';
            
            // Créer une nouvelle image avec la source thématique
            const newImg = document.createElement('img');
            newImg.src = thematicSrc;
            newImg.alt = altText;
            newImg.className = classNames + ' ' + getRandomEffectClass();
            
            // Appliquer les dimensions d'origine si disponibles
            if (width) newImg.width = width;
            if (height) newImg.height = height;
            
            // Remplacer l'image d'origine
            img.parentNode.replaceChild(newImg, img);
            
            // Journaliser le remplacement pour le débogage
            console.info(`Image remplacée: ${img.src} → ${thematicSrc} (contexte: ${contextType})`);
        }
    }

    /**
     * Analyse le contexte d'une image pour déterminer son type
     * @param {HTMLImageElement} img - L'image à analyser
     * @returns {string} - Le type de contexte identifié
     */
    function analyzeContext(img) {
        // Obtenir le texte environnant
        let surroundingText = '';
        let element = img.parentNode;
        
        // Remonter jusqu'à 3 niveaux pour collecter le texte
        for (let i = 0; i < 3 && element; i++) {
            surroundingText += element.innerText + ' ';
            element = element.parentNode;
        }
        
        surroundingText = surroundingText.toLowerCase();
        
        // Vérifier les attributs de l'image
        const src = img.src.toLowerCase();
        const alt = (img.getAttribute('alt') || '').toLowerCase();
        const imgClass = (img.className || '').toLowerCase();
        
        // Vérifier les classes des parents
        const parentClasses = [];
        element = img.parentNode;
        while (element) {
            if (element.className) {
                parentClasses.push(element.className.toLowerCase());
            }
            element = element.parentNode;
        }
        
        // Rechercher des indices dans les URL
        if (src.includes('admin') || src.includes('direction')) return 'admin';
        if (src.includes('teach') || src.includes('prof') || src.includes('enseign')) return 'teacher';
        if (src.includes('device') || src.includes('appareil') || src.includes('responsive')) return 'devices';
        if (src.includes('logo') || src.includes('brand')) return 'logo';
        if (src.includes('chart') || src.includes('graph') || src.includes('stat')) return 'chart';
        if (src.includes('user') || src.includes('etudiant') || src.includes('profile')) return 'user';
        if (src.includes('cog') || src.includes('setting') || src.includes('config')) return 'cog';
        
        // Analyser le texte environnant avec les mots-clés
        for (const [type, keywords] of Object.entries(config.contextKeywords)) {
            for (const keyword of keywords) {
                if (surroundingText.includes(keyword) || alt.includes(keyword)) {
                    return type;
                }
            }
        }
        
        // Rechercher des indices dans les classes
        const allClasses = imgClass + ' ' + parentClasses.join(' ');
        if (allClasses.includes('admin') || allClasses.includes('dashboard')) return 'admin';
        if (allClasses.includes('teacher') || allClasses.includes('education')) return 'teacher';
        if (allClasses.includes('device') || allClasses.includes('mobile')) return 'devices';
        if (allClasses.includes('logo') || allClasses.includes('brand')) return 'logo';
        if (allClasses.includes('chart') || allClasses.includes('statistics')) return 'chart';
        if (allClasses.includes('user') || allClasses.includes('profile')) return 'user';
        if (allClasses.includes('settings') || allClasses.includes('config')) return 'cog';
        
        // Vérifier l'emplacement dans la page
        const sections = document.querySelectorAll('section, div[class*="section"], [id*="section"]');
        for (const section of sections) {
            if (section.contains(img)) {
                const sectionId = section.id.toLowerCase();
                const sectionClass = section.className.toLowerCase();
                
                if (sectionId.includes('admin') || sectionClass.includes('admin')) return 'admin';
                if (sectionId.includes('teacher') || sectionClass.includes('teacher')) return 'teacher';
                if (sectionId.includes('device') || sectionClass.includes('device')) return 'devices';
                if (sectionId.includes('feature') || sectionClass.includes('feature')) return 'cog';
                // Autres vérifications de section...
            }
        }
        
        // Type par défaut en fonction de la taille
        if (img.width > 400 && img.height > 300) {
            return 'devices'; // Grande image par défaut
        } else if (img.width < 100 && img.height < 100) {
            return 'cog'; // Petite image, probablement une icône
        }
        
        // Valeur par défaut
        return 'admin';
    }

    /**
     * Obtient l'URL de l'image thématique correspondant au type
     * @param {string} type - Le type de contexte
     * @returns {string} - L'URL de l'image thématique
     */
    function getThematicImage(type) {
        return config.thematicImages[type] || config.thematicImages.admin;
    }

    /**
     * Obtient aléatoirement une classe d'effet CSS pour l'animation
     * @returns {string} - Classe CSS pour l'animation
     */
    function getRandomEffectClass() {
        const effects = Object.values(config.cssClasses);
        return effects[Math.floor(Math.random() * effects.length)];
    }

    /**
     * Traite les images d'arrière-plan CSS
     */
    function processCssBackgroundImages() {
        // Sélectionner tous les éléments avec un style background-image
        const elements = document.querySelectorAll('[style*="background-image"]');
        
        elements.forEach(function(element) {
            const style = element.getAttribute('style');
            if (style && style.includes('url(')) {
                // Extraire l'URL de l'image
                const urlMatch = style.match(/url\(['"]?([^'"]+)['"]?\)/);
                if (urlMatch && urlMatch[1]) {
                    const imageUrl = urlMatch[1];
                    
                    // Vérifier si l'image existe
                    const tempImg = new Image();
                    tempImg.onload = function() {
                        // L'image existe, rien à faire
                    };
                    
                    tempImg.onerror = function() {
                        // L'image n'existe pas, la remplacer
                        const contextType = analyzeContext(element);
                        const thematicUrl = getThematicImage(contextType);
                        
                        // Remplacer l'URL dans le style
                        const newStyle = style.replace(
                            /url\(['"]?([^'"]+)['"]?\)/, 
                            `url('${thematicUrl}')`
                        );
                        
                        element.setAttribute('style', newStyle);
                        console.info(`Image d'arrière-plan remplacée: ${imageUrl} → ${thematicUrl}`);
                    };
                    
                    tempImg.src = imageUrl;
                }
            }
        });
    }

    /**
     * Observe les changements du DOM pour capturer les images ajoutées dynamiquement
     */
    function observeDynamicImages() {
        // Créer un observateur pour surveiller l'ajout de nouveaux éléments
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                // Vérifier les nouveaux nœuds ajoutés
                mutation.addedNodes.forEach(function(node) {
                    // Vérifier si c'est une image
                    if (node.nodeName === 'IMG') {
                        processImage(node);
                    } 
                    // Vérifier si c'est un conteneur qui pourrait contenir des images
                    else if (node.nodeType === 1) { // Element node
                        // Rechercher les images dans ce nouvel élément
                        const childImages = node.querySelectorAll('img');
                        childImages.forEach(processImage);
                        
                        // Vérifier également les images d'arrière-plan CSS
                        const elementsWithBgImage = node.querySelectorAll('[style*="background-image"]');
                        if (elementsWithBgImage.length > 0) {
                            processCssBackgroundImages();
                        }
                    }
                });
            });
        });
        
        // Configurer l'observateur pour surveiller tout le document
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Crée les styles CSS pour les animations
     */
    function createStyles() {
        const styleSheet = document.createElement('style');
        styleSheet.type = 'text/css';
        styleSheet.innerHTML = `
            .img-fade-in {
                animation: fadeIn 1s ease-in-out;
            }
            
            .img-pulse {
                animation: pulse 2s infinite;
            }
            
            .img-bounce {
                animation: bounce 1s;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
            
            @keyframes bounce {
                0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                40% { transform: translateY(-20px); }
                60% { transform: translateY(-10px); }
            }
        `;
        
        document.head.appendChild(styleSheet);
    }
}); 