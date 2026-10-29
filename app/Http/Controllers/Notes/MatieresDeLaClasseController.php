<?php

namespace App\Http\Controllers\Notes;

use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Services\Notes\MatieresSaisissables;
use Illuminate\Http\JsonResponse;

class MatieresDeLaClasseController extends Controller
{
    public function __invoke(ESBTPClasse $classe, MatieresSaisissables $matieres): JsonResponse
    {
        $anneeId = ESBTPAnneeUniversitaire::where('is_current', true)->value('id');

        return response()->json($matieres->pour($classe, $anneeId ? (int) $anneeId : null));
    }
}
