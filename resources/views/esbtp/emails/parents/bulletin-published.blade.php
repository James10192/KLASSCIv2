@extends('esbtp.emails.parents.layout', [
    'emailTitle' => 'Bulletin Disponible',
    'parentName' => $parentName,
    'schoolName' => $schoolName ?? 'KLASSCI',
    'schoolAddress' => $schoolAddress ?? 'École Supérieure du Bâtiment et des Travaux Publics',
    'schoolPhone' => $schoolPhone ?? '+225 00 00 00 00',
    'schoolEmail' => $schoolEmail ?? 'contact@esbtp-yakro.com',
    'schoolLogoPath' => $schoolLogoPath ?? null
])

@section('content')
    <div class="alert alert-info">
        <strong>Bulletin disponible!</strong><br>
        Le bulletin de {{ $studentName }} pour {{ $periode }} est maintenant disponible.
    </div>

    <h3 style="color: #007bff; margin-top: 30px;">Informations du bulletin</h3>

    <table class="info-table">
        <tr>
            <th style="width: 40%;">Étudiant</th>
            <td><strong>{{ $studentName }}</strong></td>
        </tr>
        <tr>
            <th>Classe</th>
            <td>{{ $classe }}</td>
        </tr>
        <tr>
            <th>Période</th>
            <td><strong>{{ $periode }}</strong></td>
        </tr>
        <tr>
            <th>Année universitaire</th>
            <td>{{ $anneeUniversitaire }}</td>
        </tr>
    </table>

    <h3 style="color: #007bff; margin-top: 30px;">Résultats académiques</h3>

    <div class="kpi-section">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value" style="color: {{ $moyenneGenerale >= 10 ? '#28a745' : '#dc3545' }};">
                    {{ number_format($moyenneGenerale, 2) }}/20
                </div>
                <div class="kpi-label">Moyenne générale</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value">{{ $rang }}/{{ $effectifClasse }}</div>
                <div class="kpi-label">Rang de la classe</div>
            </div>
        </div>
    </div>

    <div class="kpi-section" style="margin-top: 10px;">
        <div class="kpi-row">
            <div class="kpi-card">
                <div class="kpi-value">{{ $totalAbsences }}h</div>
                <div class="kpi-label">Total absences</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-value" style="color: {{ isset($noteAssiduite) && $noteAssiduite >= 0 ? '#28a745' : '#dc3545' }};">
                    {{ isset($noteAssiduite) ? ($noteAssiduite >= 0 ? '+' : '') . number_format($noteAssiduite, 2) : 'N/A' }}
                </div>
                <div class="kpi-label">Note d'assiduité</div>
            </div>
        </div>
    </div>

    @if(isset($mention) && $mention)
    <div style="text-align: center; margin: 20px 0;">
        <span class="badge badge-{{ $mentionColor ?? 'info' }}" style="font-size: 16px; padding: 8px 16px;">
            {{ $mention }}
        </span>
    </div>
    @endif

    @if(isset($appreciationGenerale) && $appreciationGenerale)
    <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff;">
        <h4 style="margin-top: 0; color: #333;">Appréciation générale</h4>
        <p style="margin-bottom: 0; color: #6c757d;">{{ $appreciationGenerale }}</p>
    </div>
    @endif

    @if($moyenneGenerale >= 10)
    <div class="alert alert-success">
        <strong>Félicitations!</strong><br>
        {{ $studentName }} a obtenu une moyenne de <strong>{{ number_format($moyenneGenerale, 2) }}/20</strong>.
        @if(isset($decision))
            Décision: <strong>{{ $decision }}</strong>
        @endif
    </div>
    @else
    <div class="alert alert-warning">
        <strong>Résultats en dessous de la moyenne</strong><br>
        {{ $studentName }} a obtenu une moyenne de <strong>{{ number_format($moyenneGenerale, 2) }}/20</strong>.
        Nous vous encourageons à suivre de près les études de votre enfant et à le soutenir dans son travail scolaire.
    </div>
    @endif

    <h3 style="color: #007bff; margin-top: 30px;">Actions</h3>

    <div class="button-container">
        <a href="{{ $bulletinUrl }}" class="button">Télécharger le bulletin</a>
    </div>

    @if(isset($requiresSignature) && $requiresSignature)
    <div class="alert alert-info" style="margin-top: 20px;">
        <strong>Signature requise</strong><br>
        Ce bulletin nécessite votre signature. Veuillez le télécharger, le signer et le retourner à l'administration.
    </div>
    @endif

    <div class="divider"></div>

    <p style="color: #6c757d; font-size: 13px;">
        Pour plus de détails sur les notes par matière, connectez-vous à votre espace parent sur la plateforme.
    </p>
@endsection
