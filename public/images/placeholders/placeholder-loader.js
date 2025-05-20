/**
 * Script pour remplacer les images manquantes par des SVG
 */
document.addEventListener('DOMContentLoaded', function() {
    // Fonction pour remplacer les images manquantes
    function replaceMissingImage(img) {
        img.onerror = null; // Éviter les boucles infinies
        
        // Obtenir le nom de fichier de l'image
        const src = img.getAttribute('src');
        const filename = src.substring(src.lastIndexOf('/') + 1);
        const basename = filename.substring(0, filename.lastIndexOf('.'));
        
        // Déterminer le type d'image
        let placeholderType = 'generic';
        if (filename.includes('admin')) {
            placeholderType = 'admin';
        } else if (filename.includes('teacher')) {
            placeholderType = 'teacher';
        } else if (filename.includes('student')) {
            placeholderType = 'student';
        }
        
        // Déterminer si l'image est circulaire
        const isCircular = filename.includes('avatar') || 
                          filename.includes('testimonial') || 
                          filename.includes('profile');
        
        // Créer un texte par défaut
        let placeholderText = basename.charAt(0).toUpperCase() + basename.slice(1);
        if (filename.includes('admin')) {
            placeholderText = 'Admin';
        } else if (filename.includes('teacher')) {
            placeholderText = 'Teacher';
        } else if (filename.includes('student')) {
            placeholderText = 'Student';
        } else if (filename.includes('default')) {
            placeholderText = 'User';
        }
        
        // Remplacer l'image par une image SVG
        img.src = '/images/placeholders/' + basename + '.svg';
        
        // Alternative: utiliser des classes CSS
        img.classList.add('placeholder-image');
        img.classList.add('placeholder-' + placeholderType);
        if (isCircular) {
            img.classList.add('placeholder-circular');
        }
        img.setAttribute('data-text', placeholderText);
    }
    
    // Ajouter un gestionnaire d'événements pour toutes les images
    document.querySelectorAll('img').forEach(function(img) {
        if (!img.complete || img.naturalWidth === 0) {
            img.addEventListener('error', function() {
                replaceMissingImage(img);
            });
        }
    });
});