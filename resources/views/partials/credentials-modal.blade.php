{{-- Modal d'affichage des credentials --}}
@if(session('credentials'))
<div id="credentialsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;">
    <div class="card-moderne" style="max-width: 500px; width: 90%; padding: var(--space-xl); margin: auto; background-color: var(--surface); border-radius: var(--radius-medium); box-shadow: var(--shadow-elevated);">
        <div style="text-align: center; margin-bottom: var(--space-lg);">
            <div style="width: 80px; height: 80px; background-color: var(--success); border-radius: var(--radius-circle); margin: 0 auto var(--space-md); display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user-check" style="font-size: 28px; color: white;"></i>
            </div>
            <h3 style="color: var(--text-primary); margin-bottom: var(--space-sm); font-size: var(--title-main);">Compte créé avec succès!</h3>
            <p style="color: var(--text-secondary); margin: 0;">Voici les informations de connexion pour le nouvel utilisateur :</p>
        </div>

        <div style="background-color: #f8fafc; border-radius: var(--radius-small); padding: var(--space-lg); margin-bottom: var(--space-lg); border-left: 4px solid var(--success);">
            <div style="margin-bottom: var(--space-md);">
                <label style="font-weight: 600; color: var(--text-primary); display: block; margin-bottom: var(--space-xs);">Nom d'utilisateur :</label>
                <div style="background-color: white; padding: var(--space-sm); border-radius: var(--radius-small); font-family: monospace; font-size: 16px; border: 1px solid #e5e7eb; position: relative;">
                    <span id="usernameText">{{ session('credentials')['username'] }}</span>
                    <button type="button" onclick="copyToClipboard('usernameText')" style="position: absolute; right: var(--space-sm); top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary); cursor: pointer;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>

            <div>
                <label style="font-weight: 600; color: var(--text-primary); display: block; margin-bottom: var(--space-xs);">Mot de passe temporaire :</label>
                <div style="background-color: white; padding: var(--space-sm); border-radius: var(--radius-small); font-family: monospace; font-size: 16px; border: 1px solid #e5e7eb; position: relative;">
                    <span id="passwordText">{{ session('credentials')['password'] }}</span>
                    <button type="button" onclick="copyToClipboard('passwordText')" style="position: absolute; right: var(--space-sm); top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--primary); cursor: pointer;">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
            </div>
        </div>

        <div style="background-color: rgba(245, 158, 11, 0.1); border-radius: var(--radius-small); padding: var(--space-md); margin-bottom: var(--space-lg); border-left: 4px solid var(--warning);">
            <div style="display: flex; align-items: flex-start; gap: var(--space-sm);">
                <i class="fas fa-exclamation-triangle" style="color: var(--warning); margin-top: 2px;"></i>
                <div>
                    <p style="margin: 0; font-size: var(--text-small); color: var(--text-primary); font-weight: 600;">Important :</p>
                    <p style="margin: var(--space-xs) 0 0 0; font-size: var(--text-small); color: var(--text-secondary);">
                        L'utilisateur devra changer son mot de passe lors de sa première connexion pour des raisons de sécurité.
                    </p>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: var(--space-sm); justify-content: center;">
            <button type="button" onclick="printCredentials()" class="btn-acasi secondary" style="flex: 1;">
                <i class="fas fa-print" style="margin-right: var(--space-xs);"></i>
                Imprimer
            </button>
            <button type="button" onclick="closeCredentialsModal()" class="btn-acasi primary" style="flex: 1;">
                <i class="fas fa-check" style="margin-right: var(--space-xs);"></i>
                Compris
            </button>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const text = document.getElementById(elementId).textContent;
    const button = event.target.closest('button');
    const icon = button.querySelector('i');
    
    navigator.clipboard.writeText(text).then(function() {
        // Animation de succès plus visible
        const originalClass = icon.className;
        const originalButtonStyle = button.style.cssText;
        
        // Changer l'icône et le style du bouton
        icon.className = 'fas fa-check';
        icon.style.color = 'white';
        button.style.backgroundColor = 'var(--success)';
        button.style.transform = 'scale(1.1)';
        button.style.transition = 'all 0.3s ease';
        button.style.borderRadius = 'var(--radius-small)';
        button.style.padding = '4px 8px';
        
        // Ajouter un effet de pulsation
        button.style.animation = 'copySuccess 0.6s ease-in-out';
        
        // Créer une notification toast temporaire
        showCopyToast('Copié dans le presse-papiers !');
        
        setTimeout(() => {
            icon.className = originalClass;
            icon.style.color = '';
            button.style.cssText = originalButtonStyle;
            button.style.animation = '';
        }, 2000);
    }).catch(function() {
        // En cas d'erreur
        showCopyToast('Erreur lors de la copie', 'error');
    });
}

function showCopyToast(message, type = 'success') {
    // Créer le toast
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background-color: ${type === 'success' ? 'var(--success)' : 'var(--danger)'};
        color: white;
        padding: 12px 20px;
        border-radius: var(--radius-medium);
        box-shadow: var(--shadow-elevated);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    const icon = document.createElement('i');
    icon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle';
    
    toast.appendChild(icon);
    toast.appendChild(document.createTextNode(message));
    document.body.appendChild(toast);
    
    // Animation d'entrée
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Animation de sortie et suppression
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                document.body.removeChild(toast);
            }
        }, 300);
    }, 2500);
}

// Ajouter les styles CSS pour l'animation
const style = document.createElement('style');
style.textContent = `
    @keyframes copySuccess {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1.1); }
    }
`;
document.head.appendChild(style);

function printCredentials() {
    const credentials = {
        username: document.getElementById('usernameText').textContent,
        password: document.getElementById('passwordText').textContent,
        date: new Date().toLocaleDateString('fr-FR')
    };
    
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Informations de connexion ESBTP</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .header { text-align: center; margin-bottom: 30px; }
                .credentials { background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .field { margin: 15px 0; }
                .label { font-weight: bold; color: #333; }
                .value { font-family: monospace; font-size: 14px; margin-top: 5px; padding: 8px; background: white; border: 1px solid #ddd; border-radius: 4px; }
                .warning { background: #fff3cd; padding: 15px; border-radius: 8px; border-left: 4px solid #ffc107; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h2>ESBTP-yAKRO - Informations de connexion</h2>
                <p>Date : ${credentials.date}</p>
            </div>
            <div class="credentials">
                <div class="field">
                    <div class="label">Nom d'utilisateur :</div>
                    <div class="value">${credentials.username}</div>
                </div>
                <div class="field">
                    <div class="label">Mot de passe temporaire :</div>
                    <div class="value">${credentials.password}</div>
                </div>
            </div>
            <div class="warning">
                <strong>Important :</strong> L'utilisateur devra changer son mot de passe lors de sa première connexion pour des raisons de sécurité.
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function closeCredentialsModal() {
    document.getElementById('credentialsModal').style.display = 'none';
}

// Fermer avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCredentialsModal();
    }
});
</script>
@endif