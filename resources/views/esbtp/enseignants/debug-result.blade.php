@extends('layouts.app')

@section('title', 'Résultat Debug - ESBTP-yAKRO')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<style>
    .debug-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: var(--space-xl);
    }
    
    .debug-header {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        padding: var(--space-xl);
        border-radius: var(--radius-large);
        margin-bottom: var(--space-xl);
        text-align: center;
    }
    
    .debug-content {
        background: var(--surface);
        border-radius: var(--radius-large);
        padding: var(--space-xl);
        box-shadow: var(--shadow-card);
        margin-bottom: var(--space-lg);
    }
    
    .debug-message {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: var(--radius-medium);
        padding: var(--space-lg);
        font-family: 'Courier New', monospace;
        font-size: 0.9rem;
        white-space: pre-wrap;
        line-height: 1.6;
        max-height: 500px;
        overflow-y: auto;
        margin-bottom: var(--space-lg);
        user-select: all;
        cursor: text;
    }
    
    .copy-btn {
        background: var(--primary);
        color: white;
        border: none;
        padding: var(--space-md) var(--space-lg);
        border-radius: var(--radius-medium);
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-right: var(--space-md);
    }
    
    .copy-btn:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }
    
    .copy-btn.success {
        background: var(--success);
    }
    
    .action-buttons {
        display: flex;
        gap: var(--space-md);
        flex-wrap: wrap;
        margin-top: var(--space-lg);
    }
    
    .btn-secondary {
        background: var(--secondary);
        color: white;
        text-decoration: none;
        padding: var(--space-md) var(--space-lg);
        border-radius: var(--radius-medium);
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
        background: var(--secondary-dark);
        color: white;
        text-decoration: none;
        transform: translateY(-2px);
    }
    
    .highlight {
        background: #fff3cd;
        padding: var(--space-sm);
        border-radius: var(--radius-small);
        border-left: 4px solid #ffc107;
        margin-bottom: var(--space-md);
    }
</style>
@endsection

@section('content')
<div class="debug-container">
    <div class="debug-header">
        <h1>🔧 Résultat de la Modification</h1>
        <p>Détails complets de la mise à jour de l'enseignant</p>
    </div>
    
    <div class="debug-content">
        <div class="highlight">
            <strong>💡 Information :</strong> Vous pouvez sélectionner et copier tout le texte ci-dessous pour l'analyser ou le partager.
        </div>
        
        <div class="debug-message" id="debugMessage">{{ $debugMessage }}</div>
        
        <div class="action-buttons">
            <button class="copy-btn" onclick="copyDebugMessage()" id="copyBtn">
                📋 Copier le message
            </button>
            
            <a href="{{ route('esbtp.enseignants.show', $enseignant->id) }}" class="btn-secondary">
                👁️ Voir la page SHOW
            </a>
            
            <a href="{{ route('esbtp.enseignants.edit', $enseignant->id) }}" class="btn-secondary">
                ✏️ Modifier à nouveau
            </a>
            
            <a href="{{ route('esbtp.personnel.unified.index') }}" class="btn-secondary">
                📋 Retour à la liste
            </a>
        </div>
    </div>
    
    <div class="debug-content">
        <h3>📊 Actions suivantes recommandées :</h3>
        <ul>
            <li><strong>Vérifier la page SHOW</strong> : Consultez l'affichage des disponibilités</li>
            <li><strong>Comparer avec la page EDIT</strong> : Vérifiez la cohérence</li>
            <li><strong>Tester d'autres modifications</strong> : Si nécessaire</li>
            <li><strong>Copier ce message</strong> : Pour analyse ou rapport de bug</li>
        </ul>
    </div>
</div>

<script>
function copyDebugMessage() {
    const messageElement = document.getElementById('debugMessage');
    const copyBtn = document.getElementById('copyBtn');
    
    // Sélectionner le texte
    const range = document.createRange();
    range.selectNode(messageElement);
    window.getSelection().removeAllRanges();
    window.getSelection().addRange(range);
    
    try {
        // Copier dans le presse-papiers
        const successful = document.execCommand('copy');
        if (successful) {
            copyBtn.textContent = '✅ Copié !';
            copyBtn.classList.add('success');
            
            // Remettre le texte original après 3 secondes
            setTimeout(() => {
                copyBtn.textContent = '📋 Copier le message';
                copyBtn.classList.remove('success');
            }, 3000);
        } else {
            throw new Error('Command not supported');
        }
    } catch (err) {
        // Fallback moderne avec l'API Clipboard
        navigator.clipboard.writeText(messageElement.textContent).then(() => {
            copyBtn.textContent = '✅ Copié !';
            copyBtn.classList.add('success');
            
            setTimeout(() => {
                copyBtn.textContent = '📋 Copier le message';
                copyBtn.classList.remove('success');
            }, 3000);
        }).catch(() => {
            alert('Erreur de copie. Sélectionnez manuellement le texte et utilisez Ctrl+C');
        });
    }
    
    // Désélectionner après un moment
    setTimeout(() => {
        window.getSelection().removeAllRanges();
    }, 1000);
}

// Auto-sélection du message au chargement pour faciliter la copie manuelle
document.addEventListener('DOMContentLoaded', function() {
    const messageElement = document.getElementById('debugMessage');
    
    // Afficher un message informatif
    setTimeout(() => {
        console.log('🔧 Page de debug chargée. Le message est prêt à être copié.');
    }, 500);
});
</script>
@endsection