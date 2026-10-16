@php
    $estLmd = $inscription->classe?->systeme_academique === 'LMD';
    $autorise = auth()->user()->can('students.view') && ($estLmd
        ? auth()->user()->can('module.lmd.access') && auth()->user()->can('lmd.bulletins.generate')
        : (auth()->user()->can('bulletins.edit') || auth()->user()->can('bulletins.generate')));
    $periodes = $estLmd
        ? collect($inscription->classe?->getSemestresLMD() ?? [])->mapWithKeys(fn ($s) => [(string) $s => 'S'.$s])
        : collect(['semestre1' => 'S1', 'semestre2' => 'S2']);
    $premiereGenerationPermise = $inscription->status === 'active' && $inscription->workflow_step === 'etudiant_cree';
    $classesBts = ! $estLmd && $autorise && $inscription->classe
        ? app(\App\Services\ESBTP\BtsStudentAcademicRecords::class)->classMap($inscription)
        : null;
@endphp
@if($autorise && $inscription->classe && $inscription->annee_universitaire_id)
    <div class="insc-bulletin-actions" style="display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end;align-items:center;">
        <span style="font-size:.74rem;color:#64748b;font-weight:600;">Bulletin :</span>
        @foreach($periodes as $periode => $libelle)
            @php
                $bulletinExiste = $estLmd
                    ? \App\Models\ESBTPLMDBulletin::where('etudiant_id', $inscription->etudiant_id)
                        ->where('classe_id', $inscription->classe_id)
                        ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                        ->where('semestre', (int) $periode)->exists()
                    : \App\Models\ESBTPBulletin::where('etudiant_id', $inscription->etudiant_id)
                        ->where('classe_id', $classesBts[$periode.'_classe_id'])
                        ->where('annee_universitaire_id', $inscription->annee_universitaire_id)
                        ->whereIn('periode', $periode === 'semestre1' ? ['1', 'semestre1'] : ['2', 'semestre2'])->exists();
                $actionPermise = $premiereGenerationPermise || $bulletinExiste;
            @endphp
            <button type="button" class="insc-btn view" style="border:1px solid #bfd4f5;background:#eff6ff;color:#0453cb;"
                    @disabled(!$actionPermise)
                    @if(!$actionPermise) title="Première génération indisponible : inscription historique non active" @endif
                    data-bulletin-regenerate
                    data-bulletin-url="{{ route('esbtp.bulletins.regenerer-inscription', $inscription) }}"
                    data-bulletin-periode="{{ $periode }}"
                    data-bulletin-libelle="{{ $libelle }}"
                    data-bulletin-annee="{{ $inscription->anneeUniversitaire?->name ?? 'Année académique' }}">
                <i class="fas fa-sync-alt" aria-hidden="true"></i> Générer / régénérer {{ $libelle }}
            </button>
        @endforeach
    </div>
@endif
