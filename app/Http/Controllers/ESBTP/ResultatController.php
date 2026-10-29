<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPNote;
use App\Models\ESBTPEvaluation;

class ResultatController extends Controller
{
    /**
     * Affiche la liste des résultats.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $classes = ESBTPClasse::all();
        return view('esbtp.resultats.index', compact('classes'));
    }

    /**
     * Affiche les résultats pour une classe spécifique.
     *
     * @param  int  $classe_id
     * @return \Illuminate\Http\Response
     */
    public function showByClasse($classe_id)
    {
        $classe = ESBTPClasse::findOrFail($classe_id);
        $etudiants = $classe->etudiants;

        return view('esbtp.resultats.classe', compact('classe', 'etudiants'));
    }

    /**
     * Affiche les résultats pour un étudiant spécifique dans une classe.
     *
     * @param  int  $classe_id
     * @param  int  $etudiant_id
     * @return \Illuminate\Http\Response
     */
    public function showByEtudiant($classe_id, $etudiant_id)
    {
        $classe = ESBTPClasse::findOrFail($classe_id);
        $etudiant = ESBTPEtudiant::findOrFail($etudiant_id);

        // Récupérer les notes de l'étudiant
        $notes = ESBTPNote::whereHas('evaluation', function($query) use ($classe_id) {
            $query->whereHas('matiere', function($q) use ($classe_id) {
                $q->whereHas('classes', function($c) use ($classe_id) {
                    $c->where('esbtp_classes.id', $classe_id);
                });
            });
        })->where('etudiant_id', $etudiant_id)->get();

        return view('esbtp.resultats.etudiant', compact('classe', 'etudiant', 'notes'));
    }
}
