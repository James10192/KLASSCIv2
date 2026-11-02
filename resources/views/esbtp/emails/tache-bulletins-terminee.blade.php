@php
    $emailTitle = $libelle;
    $statutTon = $reussie ? 'succes' : 'danger';
    $statutTexte = $reussie ? 'Travail terminé' : 'Échec du traitement';
    $service = 'Bulletins et exports';
    $raison = 'Vous recevez ce message parce que vous avez lancé ce traitement depuis votre espace KLASSCI.';
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ $reussie ? 'Le traitement est terminé.' : 'Le traitement a échoué.' }} {{ $libelle }}
@endsection

@section('titre'){{ $libelle }}
@endsection

@section('content')
<p style="margin:0 0 18px;">Bonjour{{ $prenom !== '' ? ' '.$prenom : '' }},</p>
<p style="margin:0 0 18px;">{{ $reussie ? 'Le travail que vous avez lancé est terminé.' : "Le travail que vous avez lancé n'a pas abouti." }}</p>
@include('esbtp.emails.parents.partials.citation', [
    'titre' => $reussie ? 'Traitement réussi' : 'Traitement en échec',
    'texte' => $message,
    'ton' => $reussie ? $emailSuccessColor : $emailDangerColor
])
@if($lien)
    @include('esbtp.emails.partials.bouton', ['url' => $lien, 'libelle' => $libelleLien, 'pleineLargeur' => true])
@endif
@if($reussie && $estExport)
<p style="font-size:13px;color:#64748b;">Le document reste disponible {{ $conservationHeures }} heures. Il faut être connecté à l'application pour l'ouvrir.</p>
@endif
<p style="font-size:13px;color:#64748b;">Ce message est envoyé automatiquement parce que vous avez lancé ce travail.</p>
@endsection
