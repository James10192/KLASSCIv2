<?php

namespace App\Exports;

use App\Models\ESBTPTeacherAttendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Http\Request;

class TeacherAttendanceExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function query()
    {
        $query = ESBTPTeacherAttendance::query()
            ->with(['enseignant', 'emploiDuTemps.classe', 'emploiDuTemps.matiere'])
            ->orderBy('validated_at', 'desc');

        // Filtres
        if ($this->request->date_debut) {
            $query->whereDate('validated_at', '>=', $this->request->date_debut);
        }

        if ($this->request->date_fin) {
            $query->whereDate('validated_at', '<=', $this->request->date_fin);
        }

        if ($this->request->enseignant_id) {
            $query->where('enseignant_id', $this->request->enseignant_id);
        }

        if ($this->request->classe_id) {
            $query->whereHas('emploiDuTemps', function($q) {
                $q->where('classe_id', $this->request->classe_id);
            });
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Enseignant',
            'Classe',
            'Matière',
            'Heure Prévue',
            'Heure Émargement',
            'Statut',
            'Adresse IP',
            'Localisation'
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->validated_at->format('d/m/Y'),
            $attendance->enseignant->nom_complet,
            $attendance->emploiDuTemps->classe->nom,
            $attendance->emploiDuTemps->matiere->nom,
            \Carbon\Carbon::parse($attendance->emploiDuTemps->heure_debut)->format('H:i'),
            $attendance->validated_at->format('H:i'),
            $attendance->isOnTime() ? 'À l\'heure' : 'En retard',
            $attendance->ip_address,
            $attendance->geolocation_data ? json_encode($attendance->geolocation_data) : 'Non disponible'
        ];
    }
}
