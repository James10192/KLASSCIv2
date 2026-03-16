{{-- 3. Student Info Card — premium vertical --}}
<div class="sr-student-card sr-animate sr-animate-delay-2">
    <div class="sr-student-avatar">
        <div class="sr-student-avatar-inner">
            @if($etudiant->photo_url)
                <img src="{{ $etudiant->photo_url }}" alt="{{ $etudiant->nom }}">
            @else
                <i class="fas fa-user-graduate"></i>
            @endif
        </div>
    </div>

    <h3 class="sr-student-name">{{ $etudiant->nom }} {{ $etudiant->prenoms }}</h3>
    <div class="sr-student-matricule">
        <i class="fas fa-id-badge"></i>
        {{ $etudiant->matricule }}
    </div>

    <div class="sr-student-divider"></div>

    <div class="sr-student-details">
        <div class="sr-detail-item">
            <div class="sr-detail-icon sr-detail-icon--classe">
                <i class="fas fa-chalkboard"></i>
            </div>
            <div class="sr-detail-text">
                <div class="sr-detail-label">Classe</div>
                <div class="sr-detail-value">{{ isset($classe) && $classe ? $classe->name : 'Non définie' }}</div>
            </div>
        </div>
        <div class="sr-detail-item">
            <div class="sr-detail-icon sr-detail-icon--filiere">
                <i class="fas fa-sitemap"></i>
            </div>
            <div class="sr-detail-text">
                <div class="sr-detail-label">Filière</div>
                <div class="sr-detail-value">{{ isset($classe) && $classe && isset($classe->filiere) ? $classe->filiere->name : 'N/A' }}</div>
            </div>
        </div>
        <div class="sr-detail-item">
            <div class="sr-detail-icon sr-detail-icon--niveau">
                <i class="fas fa-layer-group"></i>
            </div>
            <div class="sr-detail-text">
                <div class="sr-detail-label">Niveau</div>
                <div class="sr-detail-value">{{ isset($classe) && $classe && isset($classe->niveau) ? $classe->niveau->name : 'N/A' }}</div>
            </div>
        </div>
        <div class="sr-detail-item">
            <div class="sr-detail-icon sr-detail-icon--annee">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="sr-detail-text">
                <div class="sr-detail-label">Année</div>
                <div class="sr-detail-value">
                    @if(isset($anneeUniversitaire) && $anneeUniversitaire)
                        @php
                            $anneeDisplay = $anneeUniversitaire->name;
                            if (! $anneeDisplay && $anneeUniversitaire->annee_debut && $anneeUniversitaire->annee_fin) {
                                $anneeDisplay = $anneeUniversitaire->annee_debut . '-' . $anneeUniversitaire->annee_fin;
                            }
                            if (! $anneeDisplay && $anneeUniversitaire->start_date && $anneeUniversitaire->end_date) {
                                $anneeDisplay = $anneeUniversitaire->start_date->format('Y') . '-' . $anneeUniversitaire->end_date->format('Y');
                            }
                        @endphp
                        {{ $anneeDisplay ?: 'N/A' }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
