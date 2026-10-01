@php
    $emailTitle = "Confirmation d'inscription";
    $statutTon = 'succes';
    $statutTexte = 'Inscription confirmée';
    $service = 'Scolarité';
    $raison = "Message automatique envoyé au contact parent de l'élève {$studentName}, à son inscription. Pour toute question, contactez l'administration.";
    $annee = ! empty($anneeUniversitaire) && $anneeUniversitaire !== 'N/A' ? $anneeUniversitaire : null;
    // Identifiants en police à chasse fixe : un « l » et un « 1 » s'y distinguent.
    $chasseFixe = fn ($texte) => new \Illuminate\Support\HtmlString('<span style="font-family:\'Courier New\',monospace;font-size:15px;">'.e($texte).'</span>');
@endphp
@extends('esbtp.emails.parents.recu')

@section('preheader'){{ $studentName }} est inscrit(e){{ $annee ? ' pour '.$annee : '' }}. Vos identifiants de connexion sont dans ce message.
@endsection

@section('titre'){{ $studentName }} est inscrit(e){{ $annee ? ' pour '.$annee : '' }}
@endsection

@section('content')
<p style="margin:0 0 22px;">Bonjour {{ $parentName }}, l'inscription a été enregistrée le {{ $dateInscription }}. Avec ces identifiants, vous suivez sa scolarité sur la plateforme {{ $schoolName }}&nbsp;: notes, absences et paiements.</p>
@include('esbtp.emails.parents.partials.vedette', [
    'libelle' => 'Identifiants de connexion',
    'lignes' => [
        ["Nom d'utilisateur", $chasseFixe($username)],
        ['Mot de passe', $chasseFixe($password)],
    ],
])
<p style="margin:10px 0 0;font-size:13px;font-weight:600;color:{{ $emailWarningText }};">Changez le mot de passe à la première connexion.</p>
@include('esbtp.emails.parents.partials.lignes', ['lignes' => [
    ['Élève', $studentName],
    ['Matricule', $matricule ?? null],
    ['Classe', $classe ?? null],
    ['Filière', $filiere ?? null],
    ["Niveau d'étude", $niveauEtude ?? null],
    ['Année', $annee],
], 'marge' => '8px 0 0'])
@if(isset($montantDu) && $montantDu > 0 && ($montantTotal ?? 0) > 0)
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Frais de scolarité'])
@include('esbtp.emails.parents.partials.progression', [
    'pct' => $montantPaye / $montantTotal * 100,
    'gauche' => new \Illuminate\Support\HtmlString(\App\Helpers\MontantFcfa::nombre($montantPaye).' payés sur '.\App\Helpers\MontantFcfa::html($montantTotal)),
    'droite' => new \Illuminate\Support\HtmlString('Reste '.\App\Helpers\MontantFcfa::html($montantDu)),
    'fort' => true,
])
@endif
@include('esbtp.emails.partials.bouton', ['url' => $platformUrl, 'libelle' => 'Accéder à la plateforme', 'pleineLargeur' => true])
@include('esbtp.emails.parents.partials.intertitre', ['texte' => 'Et ensuite ?'])
<ol style="margin:0;padding-left:20px;font-size:14px;line-height:1.7;color:#334155;">
<li>Connectez-vous avec ces identifiants.</li>
<li>Changez le mot de passe dans les paramètres de sécurité.</li>
<li>Consultez le profil et la scolarité de votre enfant.</li>
<li>Vérifiez les paiements et réglez ce qui reste dû.</li>
</ol>
<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#64748b;">Une question ou une difficulté&nbsp;? Contactez le service administratif.</p>
@endsection
