/**
 * Script pour remplacer les images manquantes par des placeholders
 */
document.addEventListener('DOMContentLoaded', function() {
    // Liste des images manquantes
    const missingImages = [        'admin-image.png',
        'teacher-image.png',
        'devices-mockup.png',
    ];
    
    // Fonction pour remplacer une image
    function replaceMissingImage(img) {
        const src = img.getAttribute('src');
        
        // Vérifier si cette image est dans notre liste d'images manquantes
        const filename = src.substring(src.lastIndexOf('/') + 1);
        if (missingImages.includes(filename)) {
            // Créer un iframe pour afficher le placeholder
            const iframe = document.createElement('iframe');
            const placeholderPath = '/placeholders/' + filename.replace(/\.[^/.]+$/, "") + '.html';
            
            iframe.src = placeholderPath;
            iframe.style.border = 'none';
            iframe.style.width = img.width + 'px';
            iframe.style.height = img.height + 'px';
            iframe.style.display = 'inline-block';
            iframe.style.borderRadius = '10px';
            iframe.style.overflow = 'hidden';
            
            // Remplacer l'image par l'iframe
            img.parentNode.replaceChild(iframe, img);
        }
    }
    
    // Parcourir toutes les images de la page
    document.querySelectorAll('img').forEach(replaceMissingImage);
    
    // Observer les nouvelles images qui pourraient être ajoutées dynamiquement
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.tagName === 'IMG') {
                    replaceMissingImage(node);
                } else if (node.querySelectorAll) {
                    node.querySelectorAll('img').forEach(replaceMissingImage);
                }
            });
        });
    });
    
    // Observer tout le document pour les changements
    observer.observe(document.body, { childList: true, subtree: true });
});