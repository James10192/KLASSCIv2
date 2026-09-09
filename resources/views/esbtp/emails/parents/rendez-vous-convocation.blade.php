@php
    $emailTitle = 'Convocation au guichet';
    $emailPrimaryColor = $emailPrimaryColor ?? \App\Helpers\SettingsHelper::get('pdf_primary_color', '#0453cb');
    $emailHeaderBgColor = $emailHeaderBgColor ?? \App\Helpers\SettingsHelper::get('pdf_header_bg_color', $emailPrimaryColor);
    $emailHeaderTextColor = $emailHeaderTextColor ?? \App\Helpers\SettingsHelper::getPdfSettings()['header_text_on_bg'];
@endphp

@extends('esbtp.emails.parents.layout')

@section('content')
    <div class="message-intro">
        <p>Votre rendez-vous au guichet est confirmé. Le détail figure aussi dans le PDF joint.</p>
    </div>

    <table class="info-table">
        <tr>
            <th style="width: 40%;">Date</th>
            <td><strong>{{ $date }}</strong></td>
        </tr>
        <tr>
            <th>Heure</th>
            <td><strong>{{ $heure }}</strong></td>
        </tr>
        <tr>
            <th>Nom</th>
            <td>{{ $nom }}</td>
        </tr>
        @if($reference)
        <tr>
            <th>Référence</th>
            <td><span class="badge badge-info">{{ $reference }}</span></td>
        </tr>
        @endif
    </table>

    <div class="instruction-box">
        <h3>Au guichet</h3>
        <p>Présentez-vous à l'heure indiquée avec vos pièces. En cas d'empêchement, modifiez ou annulez le rendez-vous sur klassci.com avec votre référence.</p>
    </div>

    @if($lien)
    <div class="button-container">
        <a href="{{ $lien }}" class="button">Voir mon rendez-vous</a>
    </div>
    @endif
@endsection
