{{-- Gabarit principal KLASSCI : même enveloppe « Reçu » que les alertes
     d'encaissements. Ancien design sauvegardé dans layout-ancien.blade.php.
     Les modèles existants conservent leurs sections content/preheader/subtitle/
     mention et toutes leurs données, sans modifier les workflows d'envoi. --}}
@php
    $emailTitle = $emailTitle ?? $title ?? $schoolName ?? 'Information';
    $statutTon = $statutTon ?? 'info';
    $statutTexte = $statutTexte ?? 'Information de votre établissement';
    $service = $service ?? 'Communication KLASSCI';
    $raison = $raison ?? "Vous recevez ce message dans le cadre des services de votre établissement.";
@endphp
@extends('esbtp.emails.parents.recu')

@section('titre')
{{ $emailTitle }}
@endsection

{{-- Quelques anciennes vues utilisent encore ces classes CSS plutôt que
     des styles en ligne. Ne les charger que sur le chemin historique pour
     préserver la légèreté des onze avis aux parents existants. --}}
@push('courrielCompat')
<style>
.info-table{width:100%;border-collapse:collapse;margin:20px 0;border:1px solid #e5eaf2}
.info-table th,.info-table td{padding:12px 16px;text-align:left;border-bottom:1px solid #eef2f7;font-size:14px}
.info-table th{background:{{ $emailPrimarySoft }};color:{{ $emailPrimaryColor }}}
.info-table tr:last-child td{border-bottom:none}
.kpi-section{display:table;width:100%;margin:20px 0;border-spacing:8px}
.kpi-row{display:table-row}
.kpi-card{display:table-cell;padding:16px;background:{{ $emailPrimarySoft }};border-radius:12px;text-align:center}
.kpi-label{font-size:12px;color:#64748b;margin-top:4px}
.kpi-title{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase}
.kpi-value{font-size:24px;font-weight:700;color:{{ $emailPrimaryColor }}}
.kpi-desc{font-size:12px;color:#64748b}
.badge{display:inline-block;padding:4px 12px;border-radius:999px;font-size:12px;font-weight:700}
.badge-success{background:#dcfce7;color:#166534}
.badge-warning{background:#fef3c7;color:#92400e}
.badge-danger{background:#fee2e2;color:#991b1b}
.badge-info{background:{{ $emailPrimarySoft }};color:{{ $emailPrimaryColor }}}
.alert{padding:14px 16px;margin:18px 0;border-radius:10px;border-left:4px solid;font-size:14px}
.alert-success{background:#f0fdf4;border-color:{{ $emailSuccessColor }};color:{{ $emailSuccessText }}}
.alert-warning{background:#fffbeb;border-color:{{ $emailWarningColor }};color:{{ $emailWarningText }}}
.alert-danger{background:#fef2f2;border-color:{{ $emailDangerColor }};color:{{ $emailDangerColor }}}
.alert-info{background:{{ $emailPrimarySoft }};border-color:{{ $emailPrimaryColor }};color:#1e293b}
.message-intro{background:{{ $emailPrimarySoft }};border-left:4px solid {{ $emailPrimaryColor }};padding:14px 16px;margin:20px 0;border-radius:10px}
.message-intro p{margin:0;color:#1e293b;font-weight:600}
.instruction-box{background:#f8fafc;border:1px solid #e5eaf2;border-radius:12px;padding:18px 20px;margin:20px 0}
.instruction-box h3{color:{{ $emailPrimaryColor }};margin:0 0 12px;font-size:15px}
.instruction-box li{margin-bottom:6px;color:#475569}
.greeting{font-size:16px;color:#0f172a;font-weight:600;margin:0 0 16px}
@media only screen and (max-width:620px){.kpi-card{display:block!important;margin-bottom:8px}}
</style>
@endpush
