<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ESBTPClasse;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPSessionReport;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminSessionReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'teacher_id'  => $request->input('teacher_id'),
            'classe_id'   => $request->input('classe_id'),
            'matiere_id'  => $request->input('matiere_id'),
            'behavior'    => $request->input('behavior'),
            'date_from'   => $request->input('date_from'),
            'date_to'     => $request->input('date_to'),
            'search'      => trim((string) $request->input('search', '')),
        ];

        $query = ESBTPSessionReport::query()
            ->with([
                'teacher:id,name,email',
                'seanceCours:id,classe_id,matiere_id,date_seance,heure_debut,heure_fin,emploi_temps_id',
                'seanceCours.matiere:id,name,code',
                'seanceCours.classe:id,name,code',
            ])
            ->where('status', 'submitted');

        if ($filters['teacher_id']) {
            $query->where('teacher_id', $filters['teacher_id']);
        }

        if ($filters['behavior']) {
            $query->where('student_behavior', $filters['behavior']);
        }

        if ($filters['search'] !== '') {
            $query->where('content_summary', 'like', '%' . $filters['search'] . '%');
        }

        if ($filters['classe_id'] || $filters['matiere_id'] || $filters['date_from'] || $filters['date_to']) {
            $query->whereHas('seanceCours', function ($q) use ($filters) {
                if ($filters['classe_id']) {
                    $q->where('classe_id', $filters['classe_id']);
                }
                if ($filters['matiere_id']) {
                    $q->where('matiere_id', $filters['matiere_id']);
                }
                if ($filters['date_from']) {
                    $q->whereDate('date_seance', '>=', $filters['date_from']);
                }
                if ($filters['date_to']) {
                    $q->whereDate('date_seance', '<=', $filters['date_to']);
                }
            });
        }

        $reports = $query->orderByDesc('submitted_at')->paginate(15)->withQueryString();

        $now = Carbon::now();
        $kpis = [
            'total_week'    => ESBTPSessionReport::where('status', 'submitted')
                                ->whereBetween('submitted_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()])
                                ->count(),
            'total_month'   => ESBTPSessionReport::where('status', 'submitted')
                                ->whereBetween('submitted_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                                ->count(),
            'total_all'     => ESBTPSessionReport::where('status', 'submitted')->count(),
            'difficult'     => ESBTPSessionReport::where('status', 'submitted')
                                ->where('student_behavior', 'difficult')
                                ->whereBetween('submitted_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                                ->count(),
        ];

        $teachers = User::role(['enseignant', 'teacher'])
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        $classes  = ESBTPClasse::select('id', 'name', 'code')->orderBy('name')->get();
        $matieres = ESBTPMatiere::select('id', 'name', 'code')->orderBy('name')->get();

        $behaviors = [
            'excellent'    => 'Excellent',
            'good'         => 'Bon',
            'satisfactory' => 'Satisfaisant',
            'difficult'    => 'Difficile',
        ];

        return view('esbtp.rapports-cours.index', compact(
            'reports', 'kpis', 'filters', 'teachers', 'classes', 'matieres', 'behaviors'
        ));
    }

    public function show($reportId)
    {
        $report = ESBTPSessionReport::with([
            'teacher:id,name,email',
            'seanceCours.matiere:id,name,code',
            'seanceCours.classe:id,name,code',
        ])->findOrFail($reportId);

        return view('esbtp.rapports-cours.show', compact('report'));
    }
}
