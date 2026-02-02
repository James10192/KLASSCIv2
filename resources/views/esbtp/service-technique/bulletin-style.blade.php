@extends('layouts.app')

@section('title', 'Style Bulletin PDF')

@push('styles')
<style>
    .bulletin-style-hero {
        background: linear-gradient(120deg, rgba(4, 83, 203, 0.12), rgba(16, 185, 129, 0.12));
        border: 1px solid rgba(148, 163, 184, 0.35);
        border-radius: 18px;
        padding: 24px;
        position: relative;
        overflow: hidden;
        margin-bottom: 20px;
    }
    .bulletin-style-hero::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -60px;
        width: 180px;
        height: 180px;
        background: radial-gradient(circle, rgba(5, 150, 105, 0.18), transparent 70%);
        border-radius: 50%;
    }
    .style-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
    }
    .style-card {
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 18px;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .style-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
    }
    .style-card.is-selected {
        border-color: #0ea5e9;
        box-shadow: 0 18px 36px rgba(14, 165, 233, 0.25);
    }
    .style-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 600;
        color: #0f766e;
        background: #ecfeff;
        border: 1px solid #99f6e4;
    }
    .style-actions {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 14px;
    }
    .style-preview {
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        padding: 14px;
        font-size: 11px;
        color: #475569;
    }
    .preview-header {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 10px;
        align-items: center;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 10px;
    }
    .preview-logo {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        background: linear-gradient(135deg, #0ea5e9, #22c55e);
    }
    .preview-title {
        font-weight: 700;
        font-size: 12px;
        text-transform: uppercase;
        color: #0f172a;
    }
    .preview-subtitle {
        font-size: 10px;
        color: #64748b;
    }
    .preview-student {
        display: grid;
        grid-template-columns: 60px 1fr;
        gap: 10px;
        align-items: center;
        padding: 10px;
        background: #ffffff;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        margin-bottom: 10px;
    }
    .preview-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e2e8f0;
    }
    .preview-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10px;
        margin-bottom: 10px;
    }
    .preview-table th,
    .preview-table td {
        border: 1px solid #e2e8f0;
        padding: 4px;
        text-align: left;
    }
    .preview-table th {
        background: #f1f5f9;
        font-weight: 600;
    }
    .preview-footer {
        display: flex;
        justify-content: space-between;
        font-size: 10px;
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bulletin-style-hero">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="main-card-title">Style Bulletin PDF</div>
                        <div class="main-card-subtitle">Configuration service technique pour choisir le format applique aux PDFs et previews.</div>
                    </div>
                    <div class="text-muted">Mise a jour instantanee</div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('esbtp.bulletin-style.update') }}">
        @csrf
        <div class="style-grid">
            <label class="w-100">
                <div class="style-card {{ $currentStyle === 'yakro' ? 'is-selected' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold">ESBTP Yakro</div>
                        <span class="style-badge">Modele actuel</span>
                    </div>
                    <div class="style-preview">
                        <div class="preview-header">
                            <div class="preview-logo"></div>
                            <div>
                                <div class="preview-title">Bulletin de notes</div>
                                <div class="preview-subtitle">Annee 2024-2025 • Semestre 1</div>
                            </div>
                        </div>
                        <div class="preview-student">
                            <div class="preview-avatar"></div>
                            <div>
                                <div><strong>Etudiant:</strong> KOUAME ARNAUD</div>
                                <div><strong>Classe:</strong> 2A BTS</div>
                                <div><strong>Matricule:</strong> MESBTP24-0012</div>
                            </div>
                        </div>
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>Matiere</th>
                                    <th>Moy</th>
                                    <th>Coef</th>
                                    <th>Obs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Maths</td>
                                    <td>12.5</td>
                                    <td>3</td>
                                    <td>Bien</td>
                                </tr>
                                <tr>
                                    <td>Topographie</td>
                                    <td>14.0</td>
                                    <td>2</td>
                                    <td>TB</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="preview-footer">
                            <div>Decision: Admis</div>
                            <div>Signature Directeur</div>
                        </div>
                    </div>
                    <div class="style-actions">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulletin_style" id="bulletin_style_yakro" value="yakro" {{ $currentStyle === 'yakro' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_style_yakro">Activer ce style</label>
                        </div>
                    </div>
                </div>
            </label>
            <label class="w-100">
                <div class="style-card {{ $currentStyle === 'abidjan' ? 'is-selected' : '' }}">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="fw-bold">ESBTP Abidjan</div>
                        <span class="style-badge">Nouveau</span>
                    </div>
                    <div class="style-preview">
                        <div class="preview-header">
                            <div class="preview-logo"></div>
                            <div>
                                <div class="preview-title">Bulletin de notes</div>
                                <div class="preview-subtitle">Ministere • Logo a gauche</div>
                            </div>
                        </div>
                        <div class="preview-student">
                            <div class="preview-avatar"></div>
                            <div>
                                <div><strong>Etudiant:</strong> DIABATE MARIE</div>
                                <div><strong>Classe:</strong> 2A BTS</div>
                                <div><strong>Matricule:</strong> FESBTP24-0048</div>
                            </div>
                        </div>
                        <table class="preview-table">
                            <thead>
                                <tr>
                                    <th>Matiere</th>
                                    <th>Moy</th>
                                    <th>Coef</th>
                                    <th>Obs</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Techniques routieres</td>
                                    <td>13.7</td>
                                    <td>4</td>
                                    <td>Bien</td>
                                </tr>
                                <tr>
                                    <td>Beton arme</td>
                                    <td>15.0</td>
                                    <td>2</td>
                                    <td>TB</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="preview-footer">
                            <div>Decision: Admis</div>
                            <div>Signature Directeur</div>
                        </div>
                    </div>
                    <div class="style-actions">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="bulletin_style" id="bulletin_style_abidjan" value="abidjan" {{ $currentStyle === 'abidjan' ? 'checked' : '' }}>
                            <label class="form-check-label" for="bulletin_style_abidjan">Activer ce style</label>
                        </div>
                    </div>
                </div>
            </label>
        </div>

        <div class="mt-4">
            <button class="btn btn-primary" type="submit">
                <i class="fas fa-save me-2"></i>Enregistrer
            </button>
        </div>
    </form>
</div>
@endsection
