@extends('layouts.app')

@section('title', 'Bulletins LMD — KLASSCI')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}?v={{ @filemtime(public_path('css/dashboard-moderne.css')) ?: '1' }}">
<style>
    .lb-page{max-width:1440px;margin:0 auto;padding:0 1rem 2rem}.lb-hero{position:relative;background:linear-gradient(135deg,#0a3d8f 0%,#0453cb 40%,#3b7ddb 100%);border-radius:18px;padding:2rem 2.5rem 1.5rem;color:#fff;margin-bottom:1.5rem;overflow:hidden}.lb-hero-top,.lb-hero-actions,.lb-hero-kpis,.lb-filters{display:flex;gap:.75rem;flex-wrap:wrap}.lb-hero-top{justify-content:space-between;align-items:flex-start}.lb-hero-left{display:flex;align-items:center;gap:1rem}.lb-hero-icon{width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,.12);display:flex;align-items:center;justify-content:center;font-size:1.35rem;border:1px solid rgba(255,255,255,.15)}.lb-hero-info h1{font-size:1.45rem;font-weight:700;margin:0 0 .2rem;color:#fff}.lb-hero-info p{margin:0;opacity:.8;font-size:.88rem}.lb-hero-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.1rem;border-radius:10px;font-size:.84rem;font-weight:600;border:1.5px solid rgba(255,255,255,.3);color:#fff;background:rgba(255,255,255,.08);text-decoration:none}.lb-hero-btn:hover{background:rgba(255,255,255,.18);color:#fff;text-decoration:none}.lb-hero-btn--solid{background:#fff;color:#0453cb;border-color:#fff}.lb-hero-btn--solid:hover{background:#edf2fc;color:#0453cb}.lb-hero-kpis{margin-top:1.5rem}.lb-kpi{flex:1;min-width:150px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:12px;padding:.9rem 1rem;display:flex;align-items:center;gap:.75rem}.lb-kpi-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.15)}.lb-kpi-value{font-size:1.35rem;font-weight:700;color:#fff;line-height:1}.lb-kpi-label{font-size:.75rem;color:rgba(255,255,255,.65);margin-top:.15rem}
    .lb-config{margin-bottom:1.5rem;border:1px solid #dbe5f2;border-radius:16px;background:#fff;box-shadow:0 6px 24px rgba(15,23,42,.06);overflow:hidden}.lb-config>summary{list-style:none;cursor:pointer;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;font-weight:800;color:#1e293b;background:linear-gradient(180deg,#fff,#f8fbff)}.lb-config>summary::-webkit-details-marker{display:none}.lb-config>summary span{display:flex;align-items:center;gap:.6rem}.lb-config>summary i{color:#0453cb}.lb-config-body{padding:1.25rem;border-top:1px solid #eef2f7}.lb-config-intro{display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;padding:.85rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;margin-bottom:1.25rem}.lb-config-intro p{margin:0;color:#64748b;font-size:.84rem}.lb-swatches{display:flex;align-items:center;gap:.6rem;flex-wrap:wrap}.lb-swatch{display:inline-flex;align-items:center;gap:.35rem;font-size:.72rem;color:#64748b}.lb-swatch b{width:22px;height:22px;border-radius:6px;border:1px solid rgba(15,23,42,.12);display:inline-block}.lb-config-link{font-size:.8rem;font-weight:700;color:#0453cb;text-decoration:none}.lb-config-section{margin-top:1.25rem}.lb-config-section h3{font-size:.95rem;font-weight:800;color:#1e293b;margin:0 0 .75rem}.lb-config-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.9rem}.lb-config-grid--fonts{grid-template-columns:repeat(4,minmax(0,1fr))}.lb-config-field{display:flex;flex-direction:column;gap:.3rem}.lb-config-field--wide{grid-column:1/-1}.lb-config-field label{font-size:.72rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#64748b}.lb-config-field input,.lb-config-field select,.lb-config-field textarea{width:100%;border:1.5px solid #e2e8f0;border-radius:9px;background:#f8fafc;color:#1e293b;padding:.55rem .7rem;font-size:.84rem}.lb-config-field input:focus,.lb-config-field select:focus,.lb-config-field textarea:focus{outline:none;border-color:#0453cb;background:#fff;box-shadow:0 0 0 3px rgba(4,83,203,.08)}.lb-font-hint{font-size:.68rem;color:#94a3b8}.lb-config-actions{display:flex;justify-content:flex-end;margin-top:1.25rem}.lb-config-save{border:0;border-radius:10px;background:#0453cb;color:#fff;padding:.65rem 1.2rem;font-weight:800;display:inline-flex;align-items:center;gap:.45rem;cursor:pointer}
    .lb-filters{background:#fff;border-radius:14px;padding:1rem 1.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,.04);border:1px solid #e8ecf1;align-items:flex-end}.lb-filter-group{display:flex;flex-direction:column;gap:.3rem;flex:1;min-width:145px}.lb-filter-label{font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em}.lb-filter-input{padding:.5rem .75rem;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.86rem;color:#1e293b;background:#f8fafc;width:100%}.lb-filter-actions{display:flex;gap:.4rem}.lb-filter-btn{padding:.5rem .9rem;border-radius:9px;font-size:.84rem;font-weight:600;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:.3rem}.lb-filter-btn--primary{background:#0453cb;color:#fff}.lb-filter-btn--reset{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0}
    .lb-table-card{background:#fff;border-radius:14px;border:1px solid #e8ecf1;box-shadow:0 1px 3px rgba(0,0,0,.04);overflow:hidden}.lb-table-header{padding:1.15rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;gap:.5rem;flex-wrap:wrap}.lb-table-title{font-size:1rem;font-weight:700;color:#1e293b;display:flex;align-items:center;gap:.5rem}.lb-table-title i{color:#0453cb}.lb-table-count{font-size:.8rem;color:#94a3b8}.lb-table-wrapper{overflow-x:auto}.lb-table{width:100%;border-collapse:collapse}.lb-table thead th{padding:.75rem 1rem;font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;background:#fafbfc;border-bottom:1px solid #f1f5f9;white-space:nowrap}.lb-table tbody tr{border-bottom:1px solid #f8fafc}.lb-table tbody tr:hover{background:#f8fbff}.lb-table tbody td{padding:.8rem 1rem;font-size:.87rem;color:#475569;vertical-align:middle}.lb-matricule{font-family:Consolas,monospace;font-size:.8rem;color:#64748b}.lb-student-name{font-weight:600;color:#1e293b}.lb-semestre-tag{display:inline-flex;padding:.2rem .6rem;border-radius:6px;font-size:.78rem;font-weight:700;background:#eef2ff;color:#4f46e5}.lb-moyenne,.lb-credits,.lb-rang{display:flex;flex-direction:column;align-items:center;gap:.15rem}.lb-moyenne-value{font-size:1rem;font-weight:800}.lb-moyenne-mention{font-size:.65rem;font-weight:600;text-transform:uppercase}.lb-moy--excellent{color:#059669}.lb-moy--good{color:#0453cb}.lb-moy--fail{color:#dc2626}.lb-credits-text{font-size:.85rem;font-weight:700;color:#1e293b}.lb-credits-text span{font-weight:400;color:#94a3b8}.lb-credits-bar{width:64px;height:4px;border-radius:2px;background:#f1f5f9;overflow:hidden}.lb-credits-fill{height:100%;border-radius:2px}.lb-credits-fill--full{background:#10b981}.lb-credits-fill--mid{background:#0453cb}.lb-credits-fill--low{background:#dc2626}.lb-rang-value{font-size:.95rem;font-weight:700;color:#1e293b}.lb-rang-total{font-size:.68rem;color:#94a3b8}.lb-badge{display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .65rem;border-radius:20px;font-size:.74rem;font-weight:600}.lb-badge-dot{width:6px;height:6px;border-radius:50%}.lb-badge--published{background:#ecfdf5;color:#059669}.lb-badge--published .lb-badge-dot{background:#10b981}.lb-badge--draft{background:#fef9ee;color:#b45309}.lb-badge--draft .lb-badge-dot{background:#f59e0b}.lb-actions{display:flex;gap:.3rem;justify-content:center}.lb-act{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;border:1px solid #e8ecf1;background:#fff;color:#64748b;font-size:.8rem;cursor:pointer;text-decoration:none}.lb-act--view:hover{background:#0453cb;border-color:#0453cb;color:#fff}.lb-act--pdf:hover{background:#059669;border-color:#059669;color:#fff}.lb-act--publish:hover{background:#d97706;border-color:#d97706;color:#fff}.lb-act--delete:hover{background:#dc2626;border-color:#dc2626;color:#fff}.lb-empty{padding:4rem 2rem;text-align:center}.lb-empty-title{font-size:1.05rem;font-weight:700;color:#334155}.lb-empty-text{font-size:.88rem;color:#94a3b8;margin:.4rem 0 1.25rem}.lb-empty-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.6rem 1.2rem;background:#0453cb;color:#fff;border-radius:10px;font-size:.85rem;font-weight:600;text-decoration:none}
    @media(max-width:992px){.lb-config-grid,.lb-config-grid--fonts{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:768px){.lb-hero{padding:1.5rem}.lb-hero-top,.lb-hero-kpis,.lb-filters{flex-direction:column;align-items:stretch}.lb-filter-group{min-width:100%}.lb-config-grid,.lb-config-grid--fonts{grid-template-columns:1fr}.lb-config-field--wide{grid-column:auto}}
</style>
<style>
    .lb-export-panel{border:1px solid rgba(4,83,203,.2);border-radius:14px;background:linear-gradient(135deg,#f8fbff,#eef5ff);padding:1rem 1.2rem;margin-bottom:1.2rem;box-shadow:0 2px 10px rgba(4,83,203,.04)}
    .lb-export-heading{display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap}
    .lb-export-title{font-size:.95rem;font-weight:800;color:#1e293b;display:flex;gap:.55rem;align-items:center}
    .lb-export-title i{color:#0453cb}.lb-export-help{font-size:.78rem;color:#64748b;margin:.25rem 0 .8rem}
    .lb-export-controls{display:flex;align-items:center;gap:.65rem;flex-wrap:wrap}
    .lb-export-scope{display:inline-flex;gap:.25rem;background:#fff;border:1px solid #dbe5f2;padding:.25rem;border-radius:10px;flex-wrap:wrap}
    .lb-export-scope button{border:0;border-radius:7px;background:transparent;color:#475569;padding:.47rem .75rem;font-size:.78rem;font-weight:700;cursor:pointer}
    .lb-export-scope button[aria-pressed="true"]{background:#0453cb;color:#fff}
    .lb-export-button{border:1px solid #b6cff7;color:#0453cb;background:#fff;padding:.55rem .8rem;display:inline-flex;align-items:center;gap:.4rem;border-radius:9px;font-size:.8rem;font-weight:700;cursor:pointer}
    .lb-export-button--primary{border-color:#0453cb;background:#0453cb;color:#fff}.lb-export-button:disabled{opacity:.5;cursor:not-allowed}
    .lb-export-progress{margin-top:1rem;border-top:1px solid #dbe5f2;padding-top:.85rem}
    .lb-export-progress-head{display:flex;justify-content:space-between;gap:.75rem;font-weight:700;color:#1e293b;font-size:.84rem}
    .lb-export-rail{margin:.55rem 0;height:7px;background:#dbe5f2;border-radius:99px;overflow:hidden}
    .lb-export-rail span{display:block;height:100%;background:#0453cb;transition:width .25s}
    .lb-export-status{font-size:.78rem;color:#475569;margin:.4rem 0}
    .lb-export-status--error{color:#b91c1c}.lb-export-cell{text-align:center;width:42px}
    .lb-export-checkbox,.lb-export-select-visible{width:17px;height:17px;accent-color:#0453cb;cursor:pointer}
    @media(max-width:768px){.lb-export-panel{padding:.85rem}.lb-export-controls,.lb-export-scope{width:100%}.lb-export-scope button{flex:1}.lb-export-button{flex:1;justify-content:center}.lb-export-heading{align-items:flex-start}}
    @media(prefers-reduced-motion:reduce){.lb-export-rail span{transition:none}}
</style>
@endpush

@section('content')
@php
    $totalBulletins = $bulletins->total();
    $publies = $kpis['publies'];
    $nonPublies = $totalBulletins - $publies;
    $avgMoyenne = $kpis['moyenne'];
    $lmdGet = static fn (string $key, $default = '') => \App\Helpers\SettingsHelper::get($key, $default);
    $pdfColors = \App\Helpers\SettingsHelper::getPdfSettings();
    $fontFields = \App\Support\LMDBulletinPrintSettings::fontFields();
    $layoutFields = \App\Support\LMDBulletinPrintSettings::layoutFields();
@endphp
<div class="lb-page">
    <div class="main-content">
        <div class="lb-hero">
            <div class="lb-hero-top">
                <div class="lb-hero-left">
                    <div class="lb-hero-icon"><i class="fas fa-graduation-cap"></i></div>
                    <div class="lb-hero-info"><h1>Bulletins LMD</h1><p>Snapshots semestriels officiels — distincts des résultats live</p></div>
                </div>
                <div class="lb-hero-actions">
                    @can('system.manage')
                    <a href="#lmd-configuration" class="lb-hero-btn"><i class="fas fa-sliders-h"></i>Configuration</a>
                    @endcan
                    <a href="{{ route('esbtp.lmd.resultats.index') }}" class="lb-hero-btn"><i class="fas fa-chart-bar"></i>Résultats live</a>
                    <a href="{{ route('esbtp.lmd.bulletins.select') }}" class="lb-hero-btn lb-hero-btn--solid"><i class="fas fa-plus"></i>Générer</a>
                </div>
            </div>
            <div class="lb-hero-kpis">
                @foreach([
                    ['fa-file-alt',$totalBulletins,'Total bulletins'],
                    ['fa-check-circle',$publies,'Publiés / figés'],
                    ['fa-clock',$nonPublies,'Brouillons'],
                    ['fa-calculator',$avgMoyenne ? number_format($avgMoyenne,2) : '—','Moyenne gén.'],
                ] as [$icon,$value,$label])
                <div class="lb-kpi"><div class="lb-kpi-icon"><i class="fas {{ $icon }}"></i></div><div><div class="lb-kpi-value">{{ $value }}</div><div class="lb-kpi-label">{{ $label }}</div></div></div>
                @endforeach
            </div>
        </div>

        @foreach(['success'=>'check-circle','error'=>'exclamation-circle'] as $type=>$icon)
            @if(session($type))
            <div class="alert alert-{{ $type === 'error' ? 'danger' : $type }} alert-dismissible fade show" role="alert" style="border-radius:10px;">
                <i class="fas fa-{{ $icon }} me-2"></i>{{ session($type) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif
        @endforeach

        @can('system.manage')
        <details class="lb-config" id="lmd-configuration" {{ request()->boolean('configuration') ? 'open' : '' }}>
            <summary><span><i class="fas fa-sliders-h"></i>Configuration du bulletin LMD</span><small style="color:#94a3b8;font-weight:600;">Identité · affichage · typographie</small></summary>
            <div class="lb-config-body">
                <div class="lb-config-intro">
                    <div>
                        <p><strong>Couleurs héritées des paramètres établissement.</strong> Le texte des en-têtes s'adapte automatiquement au contraste de la couleur primaire.</p>
                        <div class="lb-swatches" style="margin-top:.5rem;">
                            <span class="lb-swatch"><b style="background:{{ $pdfColors['header_bg_color'] ?? '#0453cb' }}"></b>Bandeau</span>
                            <span class="lb-swatch"><b style="background:{{ $pdfColors['primary_color'] ?? '#0453cb' }}"></b>Tableaux</span>
                            <span class="lb-swatch"><b style="background:{{ $pdfColors['text_color'] ?? '#1f2937' }}"></b>Texte</span>
                        </div>
                    </div>
                    <a class="lb-config-link" href="{{ route('esbtp.settings.index') }}"><i class="fas fa-palette"></i> Modifier les couleurs globales</a>
                </div>

                <form method="POST" action="{{ route('esbtp.bulletins.save-configuration') }}" id="lmd-bulletin-config-form">
                    @csrf
                    <input type="hidden" name="configuration_source" value="lmd-bulletins-index">
                    <div class="lb-config-section">
                        <h3>En-tête officiel et établissement</h3>
                        <div class="lb-config-grid">
                            <div class="lb-config-field"><label>République</label><select name="lmd_bulletin_show_republic_info"><option value="1" {{ $lmdGet('lmd_bulletin_show_republic_info',1) ? 'selected' : '' }}>Afficher</option><option value="0" {{ !$lmdGet('lmd_bulletin_show_republic_info',1) ? 'selected' : '' }}>Masquer</option></select></div>
                            <div class="lb-config-field"><label>Ministère</label><select name="lmd_bulletin_show_ministry_info"><option value="1" {{ $lmdGet('lmd_bulletin_show_ministry_info',1) ? 'selected' : '' }}>Afficher</option><option value="0" {{ !$lmdGet('lmd_bulletin_show_ministry_info',1) ? 'selected' : '' }}>Masquer</option></select></div>
                            <div class="lb-config-field"><label>Encadré établissement</label><select name="lmd_bulletin_show_etablissement_box"><option value="1" {{ $lmdGet('lmd_bulletin_show_etablissement_box',1) ? 'selected' : '' }}>Afficher</option><option value="0" {{ !$lmdGet('lmd_bulletin_show_etablissement_box',1) ? 'selected' : '' }}>Masquer</option></select></div>
                            <div class="lb-config-field"><label>Fond République / Ministère</label><input type="color" name="lmd_bulletin_official_header_bg" value="{{ $lmdGet('lmd_bulletin_official_header_bg','#ffffff') }}"></div>
                            <div class="lb-config-field"><label>Couleur République / Ministère</label><input type="color" name="lmd_bulletin_header_label_color" value="{{ $lmdGet('lmd_bulletin_header_label_color','#1f2937') }}"></div>
                            <div class="lb-config-field"><label>Couleur des valeurs (année / niveau / semestre)</label><input type="color" name="lmd_bulletin_meta_value_color" value="{{ $lmdGet('lmd_bulletin_meta_value_color','#006c28') }}"></div>
                            <div class="lb-config-field"><label>Couleur des libellés du bandeau</label><input type="color" name="lmd_bulletin_meta_label_color" value="{{ $lmdGet('lmd_bulletin_meta_label_color','#1f2937') }}"></div>
                            @foreach(['lmd_bulletin_header_label_bold'=>'République en gras','lmd_bulletin_ministry_bold'=>'Ministère en gras','lmd_bulletin_meta_label_bold'=>'Libellés année / édition / niveau / semestre en gras','lmd_bulletin_meta_value_bold'=>'Valeurs année / édition / niveau / semestre en gras'] as $key=>$label)
                            <div class="lb-config-field"><label>{{ $label }}</label><select name="{{ $key }}"><option value="1" {{ $lmdGet($key,'0') == '1' ? 'selected' : '' }}>Oui</option><option value="0" {{ $lmdGet($key,'0') != '1' ? 'selected' : '' }}>Non</option></select></div>
                            @endforeach
                            <div class="lb-config-field lb-config-field--wide"><label>Texte République</label><input name="lmd_bulletin_republic_text" value="{{ $lmdGet('lmd_bulletin_republic_text', "REPUBLIQUE DE COTE D'IVOIRE") }}"></div>
                            <div class="lb-config-field"><label>Devise nationale</label><input name="lmd_bulletin_union_text" value="{{ $lmdGet('lmd_bulletin_union_text','Union - Discipline - Travail') }}"></div>
                            <div class="lb-config-field" style="grid-column:span 2"><label>Texte Ministère</label><input name="lmd_bulletin_ministry_text" value="{{ $lmdGet('lmd_bulletin_ministry_text',"MINISTERE DE L'ENSEIGNEMENT SUPERIEUR ET DE LA RECHERCHE SCIENTIFIQUE") }}"></div>
                            <div class="lb-config-field"><label>Code établissement</label><input name="lmd_bulletin_code_etablissement" value="{{ $lmdGet('lmd_bulletin_code_etablissement','') }}"></div>
                            <div class="lb-config-field"><label>Afficher le statut établissement</label><select name="lmd_bulletin_show_establishment_status"><option value="1" {{ $lmdGet('lmd_bulletin_show_establishment_status','1') == '1' ? 'selected' : '' }}>Afficher</option><option value="0" {{ $lmdGet('lmd_bulletin_show_establishment_status','1') == '0' ? 'selected' : '' }}>Masquer</option></select></div>
                            <div class="lb-config-field"><label>Statut</label><select name="lmd_bulletin_statut"><option value="Privé" {{ $lmdGet('lmd_bulletin_statut','Privé') === 'Privé' ? 'selected' : '' }}>Privé</option><option value="Public" {{ $lmdGet('lmd_bulletin_statut','Privé') === 'Public' ? 'selected' : '' }}>Public</option></select></div>
                            <div class="lb-config-field"><label>Direction affichée</label><input name="lmd_bulletin_direction" value="{{ $lmdGet('lmd_bulletin_direction','') }}" placeholder="Ex. Direction des Études"><span class="lb-font-hint">Texte du bandeau, distinct du nom du directeur signataire.</span></div>
                        </div>
                    </div>

                    <div class="lb-config-section">
                        <h3>Champs académiques</h3>
                        <div class="lb-config-grid">
                            @foreach([
                                'domaine'=>'Domaine','mention'=>'Mention','specialite'=>'Spécialité','parcours'=>'Parcours','effectif'=>'Effectif','redoublant'=>'Redoublant','affectation'=>'Affectation'
                            ] as $key=>$label)
                            <div class="lb-config-field"><label>Afficher {{ $label }}</label><select name="lmd_bulletin_show_{{ $key }}"><option value="1" {{ $lmdGet('lmd_bulletin_show_'.$key,$key==='specialite'?0:1) ? 'selected' : '' }}>Oui</option><option value="0" {{ !$lmdGet('lmd_bulletin_show_'.$key,$key==='specialite'?0:1) ? 'selected' : '' }}>Non</option></select></div>
                            @endforeach
                            <div class="lb-config-field"><label>Libellé affectation</label><input name="lmd_bulletin_label_affectation" value="{{ $lmdGet('lmd_bulletin_label_affectation','AFFECTATION') }}"></div>
                            <div class="lb-config-field"><label>Parcours automatique</label><select name="lmd_bulletin_parcours_auto"><option value="1" {{ $lmdGet('lmd_bulletin_parcours_auto',1) ? 'selected' : '' }}>Oui</option><option value="0" {{ !$lmdGet('lmd_bulletin_parcours_auto',1) ? 'selected' : '' }}>Non</option></select></div>
                            @foreach(['domaine'=>'Domaine','mention'=>'Mention','specialite'=>'Spécialité','parcours'=>'Parcours'] as $key=>$label)
                            <div class="lb-config-field"><label>Libellé {{ $label }}</label><input name="lmd_bulletin_label_{{ $key }}" value="{{ $lmdGet('lmd_bulletin_label_'.$key,$key==='specialite'?'SPÉCIALITÉ':'') }}" placeholder="{{ $label }}"></div>
                            @endforeach
                        </div>
                    </div>

                    <div class="lb-config-section">
                        <h3>Textes du document</h3>
                        <div class="lb-config-grid">
                            <div class="lb-config-field lb-config-field--wide"><label>Notice importante</label><textarea rows="2" name="lmd_bulletin_notice_text">{{ $lmdGet('lmd_bulletin_notice_text', \App\Services\LMDBulletinService::NOTICE_DEFAUT) }}</textarea></div>
                            <div class="lb-config-field"><label>Pied de page sur une seule ligne</label><select name="lmd_bulletin_bottom_single_line"><option value="1" {{ $lmdGet('lmd_bulletin_bottom_single_line','1') == '1' ? 'selected' : '' }}>Oui (police adaptée)</option><option value="0" {{ $lmdGet('lmd_bulletin_bottom_single_line','1') == '0' ? 'selected' : '' }}>Non (deux lignes)</option></select></div>
                            <div class="lb-config-field lb-config-field--wide"><label>Pied de page</label><input name="lmd_bulletin_bottom_text" value="{{ $lmdGet('lmd_bulletin_bottom_text','Conservez soigneusement ce bulletin de notes. Aucun duplicata ne sera délivré.') }}"></div>
                        </div>
                    </div>

                    <div class="lb-config-section">
                        <h3>Mise en page du PDF</h3>
                        <p style="margin:-.3rem 0 .8rem;color:#64748b;font-size:.8rem;">Ces réglages contrôlent directement le bandeau 50/50 et la zone de signature visibles sur le bulletin.</p>
                        <div class="lb-config-grid">
                            @foreach($layoutFields as $key=>$field)
                            <div class="lb-config-field">
                                <label>{{ $field['label'] }}</label>
                                <input type="number" min="{{ $field['min'] }}" max="{{ $field['max'] }}" step="{{ $field['step'] }}" name="{{ $key }}" value="{{ $lmdGet($key,$field['default']) }}">
                                <span class="lb-font-hint">{{ $field['hint'] }} ({{ $field['min'] }}–{{ $field['max'] }} px)</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="lb-config-section">
                        <h3>Tailles de police par zone</h3>
                        <p style="margin:-.3rem 0 .8rem;color:#64748b;font-size:.8rem;">Chaque valeur agit sur la zone nommée du PDF. Pour les longues listes UE/ECUE, KLASSCI resserre les marges de ligne sans réduire la police choisie.</p>
                        <div class="lb-config-grid lb-config-grid--fonts">
                            @foreach($fontFields as $key=>$field)
                            <div class="lb-config-field">
                                <label>{{ $field['label'] }}</label>
                                <input type="number" min="6" max="32" step="0.5" name="{{ $key }}" value="{{ $lmdGet($key,$field['default']) }}">
                                <span class="lb-font-hint">6 à 32 px</span>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="lb-config-actions">
                        <span id="lmd-config-feedback" style="font-size:.78rem;color:#64748b;margin-right:auto;"></span>
                        <button class="lb-config-save" type="submit"><i class="fas fa-save"></i><span>Enregistrer la configuration LMD</span></button>
                    </div>
                </form>
            </div>
        </details>
        @endcan

        <form method="GET" action="{{ route('esbtp.lmd.bulletins.index') }}" id="lb-filter-form">
            <div class="lb-filters">
                <div class="lb-filter-group"><label class="lb-filter-label">Classe</label><x-au-select name="classe_id" :value="request('classe_id')" placeholder="Toutes les classes" icon="fa-chalkboard" :searchable="$classes->count()>8" :options="$classes->pluck('name','id')" onchange="document.getElementById('lb-filter-form').submit()" /></div>
                <div class="lb-filter-group"><label class="lb-filter-label">Année</label><x-au-select name="annee_universitaire_id" :value="request('annee_universitaire_id')" placeholder="Toutes les années" icon="fa-calendar-alt" :searchable="$annees->count()>8" :options="$annees->mapWithKeys(fn($a)=>[$a->id=>($a->name??$a->libelle??$a->display_name)])" onchange="document.getElementById('lb-filter-form').submit()" /></div>
                <div class="lb-filter-group" style="max-width:180px"><label class="lb-filter-label">Semestre</label><x-au-select name="semestre" :value="request('semestre')" placeholder="Tous" icon="fa-layer-group" :options="collect(range(1,10))->mapWithKeys(fn($s)=>[$s=>'S'.$s])" onchange="document.getElementById('lb-filter-form').submit()" /></div>
                <div class="lb-filter-group"><label class="lb-filter-label">Recherche</label><input type="text" class="lb-filter-input" name="search" value="{{ request('search') }}" placeholder="Matricule, nom..."></div>
                <div class="lb-filter-actions"><button type="submit" class="lb-filter-btn lb-filter-btn--primary"><i class="fas fa-search"></i></button><button type="button" class="lb-filter-btn lb-filter-btn--reset" aria-label="Réinitialiser"><i class="fas fa-times"></i></button></div>
            </div>
        </form>

        @can('lmd.bulletins.export.bulk')
        <section class="lb-export-panel" x-data="lmdBulletinsExport()" aria-label="Export groupé des bulletins LMD">
            <div class="lb-export-heading">
                <div class="lb-export-title"><i class="fas fa-file-pdf"></i> Impression groupée des bulletins LMD</div>
                <span style="font-size:.75rem;color:#64748b;">Un seul PDF · jusqu’à 400 bulletins</span>
            </div>
            <p class="lb-export-help">Choisissez les étudiants dans le tableau, ou exportez tous les bulletins correspondant aux filtres actifs. Les bulletins conservent leur mise en page LMD officielle.</p>
            <p class="lb-export-status lb-export-status--error" x-show="nombreCible()>400" x-cloak role="alert">Ce lot dépasse 400 bulletins. Affinez les filtres ou réduisez la sélection avant l’export.</p>
            <div class="lb-export-controls">
                <div class="lb-export-scope" role="group" aria-label="Portée de l’export">
                    <button type="button" @click="portee='selection'" :aria-pressed="portee==='selection'" :disabled="occupé">Sélection (<span x-text="selection.length">0</span>)</button>
                    <button type="button" @click="portee='filtres'" :aria-pressed="portee==='filtres'" :disabled="occupé">Tous les filtrés (<span x-text="totalFiltres">{{ $bulletins->total() }}</span>)</button>
                </div>
                <button type="button" class="lb-export-button" @click="lancer('apercu')" :disabled="occupé || !peutLancer()"><i class="fas fa-eye"></i> Aperçu du PDF</button>
                <button type="button" class="lb-export-button lb-export-button--primary" @click="lancer('telechargement')" :disabled="occupé || !peutLancer()"><i class="fas fa-file-download"></i> Télécharger le PDF groupé</button>
            </div>
            <div class="lb-export-progress" x-show="tache || erreur" x-cloak aria-live="polite">
                <template x-if="tache">
                    <div>
                        <div class="lb-export-progress-head"><span x-text="tache.libelle || 'Préparation du document'"></span><span x-text="`${tache.pourcent || 0} %`"></span></div>
                        <div class="lb-export-rail" role="progressbar" aria-label="Progression PDF LMD" :aria-valuenow="tache.pourcent || 0" aria-valuemin="0" aria-valuemax="100"><span :style="`width:${tache.pourcent || 0}%`"></span></div>
                        <p class="lb-export-status" x-text="tache.message || (tache.finale ? 'Traitement terminé.' : `${tache.position} / ${tache.total} bulletins traités`)"></p>
                        <a x-show="tache.statut==='terminee' && tache.url" :href="tache.url" class="lb-export-button lb-export-button--primary" target="_blank" rel="noopener"><i class="fas fa-file-pdf"></i><span x-text="tache.mode==='apercu' ? 'Ouvrir l’aperçu' : 'Récupérer le PDF'"></span></a>
                    </div>
                </template>
                <p class="lb-export-status lb-export-status--error" x-show="erreur" x-text="erreur" role="alert"></p>
            </div>
        </section>
        @endcan

        <div class="lb-table-card">
            <div class="lb-table-header"><div class="lb-table-title"><i class="fas fa-list-ul"></i>Liste des snapshots</div><div class="lb-table-count" data-total="{{ $bulletins->total() }}">{{ $bulletins->total() }} bulletin{{ $bulletins->total()>1?'s':'' }}</div></div>
            <div class="lb-table-wrapper"><table class="lb-table"><thead><tr>@can('lmd.bulletins.export.bulk')<th class="lb-export-cell"><input type="checkbox" class="lb-export-select-visible" aria-label="Sélectionner ou désélectionner les bulletins chargés" title="Sélectionner les lignes chargées"></th>@endcan<th>Matricule</th><th>Étudiant</th><th>Classe</th><th style="text-align:center">Sem.</th><th style="text-align:center">Moyenne</th><th style="text-align:center">Crédits</th><th style="text-align:center">Rang</th><th style="text-align:center">Statut</th><th style="text-align:center;width:130px">Actions</th></tr></thead><tbody id="lb-tbody">@forelse($bulletins as $b)@include('esbtp.lmd.bulletins._ligne')@empty<tr><td colspan="{{ auth()->user()->can('lmd.bulletins.export.bulk') ? 10 : 9 }}"><div class="lb-empty"><div class="lb-empty-title">Aucun bulletin trouvé</div><div class="lb-empty-text">Les résultats live restent consultables sans générer de snapshot.</div><a href="{{ route('esbtp.lmd.bulletins.select') }}" class="lb-empty-btn"><i class="fas fa-plus"></i>Générer des bulletins</a></div></td></tr>@endforelse</tbody></table></div>
            <x-liste-infinie :paginateur="$bulletins" cible="#lb-tbody" libelle="bulletins" />
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function(){'use strict';var form=document.getElementById('lb-filter-form');if(!form||form.dataset.ajaxReady==='1')return;form.dataset.ajaxReady='1';var recherche=form.querySelector('[name="search"]'),reset=form.querySelector('.lb-filter-btn--reset'),noms=['classe_id','annee_universitaire_id','semestre','search'],requete=null,numero=0,timer=null,suspendre=false;
function valeur(n){var c=form.elements.namedItem(n);return c?String(c.value||'').trim():''}function actifs(){return noms.some(function(n){return valeur(n)!==''})}function resetVisible(){if(reset)reset.hidden=!actifs()}function loading(on){var c=document.querySelector('.lb-table-card');if(!c)return;c.style.opacity=on?'.5':'';c.style.pointerEvents=on?'none':'';c.setAttribute('aria-busy',on?'true':'false')}function url(){var u=new URL(form.action,location.origin);new FormData(form).forEach(function(v,k){v=String(v||'').trim();if(v)u.searchParams.set(k,v)});u.searchParams.delete('page');u.searchParams.delete('mode');return u}
async function filtrer(){if(suspendre)return;clearTimeout(timer);var u=url(),id=++numero;if(requete)requete.abort();requete=new AbortController();loading(true);try{var r=await fetch(u.toString(),{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'},credentials:'same-origin',signal:requete.signal});if(!r.ok)throw new Error('HTTP '+r.status);var doc=new DOMParser().parseFromString(await r.text(),'text/html');if(id!==numero)return;var next=doc.querySelector('.lb-table-card'),current=document.querySelector('.lb-table-card');if(!next||!current)throw new Error('table absente');current.replaceWith(next);var a=document.querySelectorAll('.lb-hero-kpis .lb-kpi-value'),b=doc.querySelectorAll('.lb-hero-kpis .lb-kpi-value');a.forEach(function(el,i){if(b[i])el.textContent=b[i].textContent});history.replaceState({lmdBulletinsFiltres:true},'',u.pathname+u.search);window.dispatchEvent(new CustomEvent('lmd-bulletins:filtres',{detail:{total:Number(next.querySelector('.lb-table-count')?.dataset.total||0)}}));resetVisible()}catch(e){if(e.name!=='AbortError'){console.error('Filtrage bulletins LMD',e);alert('Le filtrage des bulletins LMD a échoué.')}}finally{if(id===numero){requete=null;loading(false)}}}
form.submit=function(){if(!suspendre)filtrer()};form.addEventListener('submit',function(e){e.preventDefault();filtrer()});if(recherche)recherche.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(filtrer,400)});if(reset)reset.addEventListener('click',function(){suspendre=true;noms.forEach(function(n){var c=form.elements.namedItem(n);if(c)c.value=''});suspendre=false;filtrer()});resetVisible();
})();
</script>
@can('lmd.bulletins.export.bulk')
<script>
// Composant indépendant du tableau remplacé en AJAX : la sélection reste
// réactive pendant le scroll infini et est remise à zéro si les filtres changent.
window.lmdBulletinsExport = function () {
    return {
        portee: 'selection', selection: [], totalFiltres: {{ (int) $bulletins->total() }},
        occupé: false, tache: null, erreur: '', timer: null, observer: null,
        surLigne: null, surFiltres: null,
        urls: {
            lancer: @json(route('esbtp.lmd.bulletins.export.lancer')),
            avancer: @json(route('esbtp.bulletins.taches.avancer', ['tache' => '__ID__'])),
            etat: @json(route('esbtp.bulletins.taches.etat', ['tache' => '__ID__'])),
        },
        init() {
            this.surLigne = (event) => {
                if (this.occupé) return;
                const target = event.target;
                if (target.matches('.lb-export-select-visible')) {
                    const ensemble = new Set(this.selection);
                    document.querySelectorAll('#lb-tbody .lb-export-checkbox').forEach(input => {
                        const id = Number(input.value);
                        target.checked ? ensemble.add(id) : ensemble.delete(id);
                    });
                    this.selection = [...ensemble];
                    this.portee = 'selection';
                } else if (target.matches('.lb-export-checkbox')) {
                    const ensemble = new Set(this.selection), id = Number(target.value);
                    target.checked ? ensemble.add(id) : ensemble.delete(id);
                    this.selection = [...ensemble];
                    this.portee = 'selection';
                } else return;
                this.synchroniser();
            };
            this.surFiltres = (event) => {
                this.selection = [];
                this.totalFiltres = Number(event.detail?.total || 0);
                this.synchroniser();
            };
            document.addEventListener('change', this.surLigne);
            window.addEventListener('lmd-bulletins:filtres', this.surFiltres);
            this.observer = new MutationObserver(() => this.synchroniser());
            const page = document.querySelector('.lb-page');
            if (page) this.observer.observe(page, { childList: true, subtree: true });
            this.synchroniser();
        },
        destroy() {
            clearTimeout(this.timer);
            this.observer?.disconnect();
            document.removeEventListener('change', this.surLigne);
            window.removeEventListener('lmd-bulletins:filtres', this.surFiltres);
        },
        synchroniser() {
            const ensemble = new Set(this.selection);
            const inputs = [...document.querySelectorAll('#lb-tbody .lb-export-checkbox')];
            inputs.forEach(input => { input.checked = ensemble.has(Number(input.value)); input.disabled = this.occupé; });
            const toutes = document.querySelector('.lb-export-select-visible');
            if (toutes) {
                toutes.checked = inputs.length > 0 && inputs.every(input => input.checked);
                toutes.indeterminate = inputs.some(input => input.checked) && !toutes.checked;
                toutes.disabled = this.occupé;
            }
        },
        nombreCible() { return this.portee === 'filtres' ? this.totalFiltres : this.selection.length; },
        peutLancer() { return this.nombreCible() > 0 && this.nombreCible() <= 400; },
        filtresActifs() {
            const form = document.getElementById('lb-filter-form');
            const data = {};
            if (form) new FormData(form).forEach((value, key) => {
                if (['classe_id', 'annee_universitaire_id', 'semestre', 'search'].includes(key) && String(value).trim()) data[key] = String(value).trim();
            });
            return data;
        },
        informer(type, message) {
            window.dispatchEvent(new CustomEvent('toast', { detail: { type, message } }));
        },
        async lancer(mode) {
            if (this.occupé || !this.peutLancer()) return;
            this.occupé = true; this.erreur = ''; this.tache = null; this.synchroniser();
            try {
                const response = await fetch(this.urls.lancer, {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                    body: JSON.stringify({ ...this.filtresActifs(), mode, portee: this.portee,
                        ids: this.portee === 'selection' ? this.selection : [] }),
                });
                const donnees = await response.json().catch(() => ({}));
                if (!response.ok || !donnees.success || !donnees.tache) {
                    throw new Error(Object.values(donnees.errors || {})[0]?.[0] || donnees.message || 'L’export n’a pas pu démarrer.');
                }
                this.tache = donnees.tache;
                window.dispatchEvent(new CustomEvent('taches-bulletins:nouvelle', { detail: this.tache }));
                window.dispatchEvent(new CustomEvent('taches-bulletins:suivie', { detail: { id: this.tache.id } }));
                this.suivre();
            } catch (error) {
                this.erreur = error.message || 'Une erreur est survenue.';
                this.occupé = false; this.synchroniser();
                this.informer('error', this.erreur);
            }
        },
        async suivre() {
            if (!this.tache || this.tache.finale) { this.terminer(); return; }
            const id = String(this.tache.id);
            try {
                if (!document.hidden) await fetch(this.urls.avancer.replace('__ID__', id), {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const response = await fetch(this.urls.etat.replace('__ID__', id), {
                    credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (response.status === 401 || response.status === 403 || response.status === 404) {
                    throw new Error('Le suivi de cet export n’est plus autorisé.');
                }
                if (response.ok) {
                    const resultat = await response.json();
                    if (resultat.tache) this.tache = resultat.tache;
                }
            } catch (error) {
                if (/plus autorisé/.test(error.message || '')) {
                    this.erreur = error.message; this.terminer(); return;
                }
                // Une requête interrompue ne détruit pas la tâche : l'ordonnanceur poursuit.
            }
            if (this.tache?.finale) { this.terminer(); return; }
            this.timer = setTimeout(() => this.suivre(), 5000);
        },
        terminer() {
            clearTimeout(this.timer);
            this.occupé = false; this.synchroniser();
            if (this.tache?.statut === 'terminee') this.informer('success', 'Le PDF groupé LMD est prêt.');
            if (this.tache?.statut === 'echouee') {
                this.erreur = this.tache.message || 'La génération du PDF a échoué.';
                this.informer('error', this.erreur);
            }
        },
    };
};
</script>
@endcan
<script>
(function(){'use strict';
var cfg=document.getElementById('lmd-bulletin-config-form');
if(!cfg||cfg.dataset.ajaxReady==='1')return;
cfg.dataset.ajaxReady='1';
cfg.addEventListener('submit',async function(e){
    e.preventDefault();
    var btn=cfg.querySelector('.lb-config-save'),label=btn?btn.querySelector('span'):null,feedback=document.getElementById('lmd-config-feedback');
    if(btn){btn.disabled=true;btn.setAttribute('aria-busy','true')}
    if(label)label.textContent='Enregistrement…';
    if(feedback){feedback.textContent='';feedback.style.color='#64748b'}
    try{
        var response=await fetch(cfg.action,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content||''},body:new FormData(cfg),credentials:'same-origin'});
        var payload=await response.json().catch(function(){return {}});
        if(!response.ok||payload.success===false){
            var first=payload.errors?Object.values(payload.errors)[0]?.[0]:null;
            throw new Error(first||payload.message||'Enregistrement impossible.');
        }
        if(feedback){feedback.textContent='Configuration enregistrée';feedback.style.color='#047857'}
        window.dispatchEvent(new CustomEvent('toast',{detail:{type:'success',message:payload.message||'Configuration LMD enregistrée.'}}));
    }catch(error){
        if(feedback){feedback.textContent=error.message||'Erreur';feedback.style.color='#b91c1c'}
        window.dispatchEvent(new CustomEvent('toast',{detail:{type:'error',message:error.message||'Enregistrement impossible.'}}));
    }finally{
        if(btn){btn.disabled=false;btn.removeAttribute('aria-busy')}
        if(label)label.textContent='Enregistrer la configuration LMD';
    }
});
})();
</script>

@endpush
