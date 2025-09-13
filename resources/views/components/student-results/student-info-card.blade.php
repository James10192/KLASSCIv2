{{-- Composant pour les informations de l'étudiant --}}
<div class="main-card">
    <div class="main-card-header">
        <div class="main-card-title">
            <i class="fas fa-user-graduate"></i>
            Informations de l'étudiant
        </div>
        <div class="main-card-subtitle">Identité et inscription</div>
    </div>
    <div class="main-card-body">
        <div class="student-profile">
            <div class="student-avatar">
                <i class="fas fa-user-circle"></i>
            </div>
            <div class="student-details">
                <h3 class="student-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h3>
                <p class="student-matricule">{{ $etudiant->matricule }}</p>
                
                <div class="student-info-grid">
                    <div class="info-item">
                        <label>Classe</label>
                        <span>{{ isset($classe) && $classe ? $classe->name : 'Non définie' }}</span>
                    </div>
                    <div class="info-item">
                        <label>Filière</label>
                        <span>{{ isset($classe) && $classe && isset($classe->filiere) ? $classe->filiere->name : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <label>Niveau</label>
                        <span>{{ isset($classe) && $classe && isset($classe->niveau) ? $classe->niveau->name : 'N/A' }}</span>
                    </div>
                    <div class="info-item">
                        <label>Année</label>
                        <span>
                            @if(isset($anneeUniversitaire))
                                {{ $anneeUniversitaire->annee_debut }}-{{ $anneeUniversitaire->annee_fin }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.student-profile {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
}

.student-avatar {
    flex-shrink: 0;
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    color: white;
    font-size: 2.5rem;
}

.student-details {
    flex: 1;
}

.student-name {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.student-matricule {
    color: var(--text-secondary);
    font-weight: 500;
    margin-bottom: 1.5rem;
}

.student-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-item label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.info-item span {
    font-weight: 600;
    color: var(--text-primary);
}
</style>