// Script de test pour vérifier la correction du bug AJAX fournisseurs
// À exécuter dans la console du navigateur sur la page /esbtp/comptabilite/depenses/create

console.log('🔧 Test de l\'endpoint AJAX fournisseurs...');

// Vérifier que le meta tag CSRF est présent
const csrfToken = document.querySelector('meta[name="csrf-token"]');
if (!csrfToken) {
    console.error('❌ Meta tag CSRF introuvable');
} else {
    console.log('✅ Meta tag CSRF trouvé:', csrfToken.getAttribute('content').substring(0, 10) + '...');
}

// Test de l'endpoint avec les bonnes données
async function testFournisseurEndpoint() {
    try {
        const formData = new FormData();
        formData.append('nom', 'Test Fournisseur ' + Date.now());
        formData.append('email', 'test@example.com');
        formData.append('telephone', '0123456789');
        formData.append('_token', csrfToken.getAttribute('content'));
        
        console.log('📡 Envoi de la requête AJAX...');
        
        const response = await fetch('/esbtp/comptabilite/fournisseurs/ajax', {
            method: 'POST',
            body: formData
        });
        
        console.log('📊 Status HTTP:', response.status);
        
        if (response.status === 200) {
            const data = await response.json();
            console.log('✅ Succès! Réponse JSON:', data);
            return data;
        } else if (response.status === 419) {
            console.error('❌ Erreur 419 - Token CSRF invalide');
            const text = await response.text();
            console.log('🔍 Réponse HTML:', text.substring(0, 200) + '...');
        } else if (response.status === 500) {
            console.error('❌ Erreur 500 - Erreur serveur');
            const text = await response.text();
            console.log('🔍 Réponse HTML:', text.substring(0, 200) + '...');
        } else {
            console.error('❌ Erreur HTTP', response.status);
            const text = await response.text();
            console.log('🔍 Réponse:', text.substring(0, 200) + '...');
        }
    } catch (error) {
        console.error('❌ Erreur lors de la requête:', error);
    }
}

// Vérifier aussi que le modal existe
const modal = document.getElementById('modalNouveauFournisseur');
if (modal) {
    console.log('✅ Modal fournisseur trouvé');
} else {
    console.log('❌ Modal fournisseur introuvable');
}

// Lancer le test
console.log('🚀 Lancement du test...');
testFournisseurEndpoint();
