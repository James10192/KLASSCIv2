<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    use HasFactory;

    /**
     * La table associée au modèle.
     * 
     * @var string
     */
    protected $table = 'attendances';

    /**
     * Les attributs qui sont assignables en masse.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'course_session_id',
        'status',
        'arrival_time',
        'departure_time',
        'excuse_reason',
        'has_supporting_document',
        'supporting_document_path',
        'comments',
        'recorded_by',
        'updated_by',
    ];

    /**
     * Les attributs qui doivent être convertis.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'arrival_time' => 'datetime',
        'departure_time' => 'datetime',
        'has_supporting_document' => 'boolean',
    ];

    /**
     * Relation avec l'étudiant concerné par cette présence.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relation avec la session de cours concernée.
     */
    public function courseSession()
    {
        return $this->belongsTo(CourseSession::class);
    }

    /**
     * Relation avec l'utilisateur qui a enregistré cette présence.
     */
    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * Relation avec l'utilisateur qui a mis à jour cette présence.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Vérifier si l'étudiant était présent.
     * 
     * @return bool
     */
    public function isPresent()
    {
        return $this->status === 'present';
    }

    /**
     * Vérifier si l'étudiant était absent.
     * 
     * @return bool
     */
    public function isAbsent()
    {
        return $this->status === 'absent';
    }

    /**
     * Vérifier si l'étudiant était excusé.
     * 
     * @return bool
     */
    public function isExcused()
    {
        return $this->status === 'excused';
    }

    /**
     * Vérifier si l'étudiant était en retard.
     * 
     * @return bool
     */
    public function isLate()
    {
        return $this->status === 'late';
    }

    /**
     * Calculer la durée de présence en minutes.
     * 
     * @return int|null
     */
    public function getDurationInMinutes()
    {
        if (!$this->arrival_time || !$this->departure_time) {
            return null;
        }
        
        return $this->departure_time->diffInMinutes($this->arrival_time);
    }

    /**
     * Scope pour filtrer les présences par statut.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope pour filtrer les présences par date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereHas('courseSession', function($q) use ($date) {
            $q->whereDate('date', $date);
        });
    }

    /**
     * Scope pour filtrer les présences par période.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereHas('courseSession', function($q) use ($startDate, $endDate) {
            $q->whereBetween('date', [$startDate, $endDate]);
        });
    }

    /**
     * Scope pour filtrer les présences par élément constitutif.
     */
    public function scopeForEC($query, $ecId)
    {
        return $query->whereHas('courseSession', function($q) use ($ecId) {
            $q->where('element_constitutif_id', $ecId);
        });
    }

    /**
     * Scope pour filtrer les présences par unité d'enseignement.
     */
    public function scopeForUE($query, $ueId)
    {
        return $query->whereHas('courseSession.elementConstitutif', function($q) use ($ueId) {
            $q->where('unite_enseignement_id', $ueId);
        });
    }

    /**
     * Scope pour filtrer les présences par enseignant.
     */
    public function scopeWithTeacher($query, $teacherId)
    {
        return $query->whereHas('courseSession', function($q) use ($teacherId) {
            $q->where('teacher_id', $teacherId);
        });
    }

    /**
     * Calcule le pourcentage de présence pour un étudiant spécifique.
     * 
     * @param int $studentId ID de l'étudiant
     * @param string|null $startDate Date de début (format Y-m-d)
     * @param string|null $endDate Date de fin (format Y-m-d)
     * @return float Pourcentage de présence (0-100)
     */
    public static function getStudentAttendancePercentage($studentId, $startDate = null, $endDate = null)
    {
        try {
            // Récupérer l'étudiant et ses classes
            $student = Student::find($studentId);
            if (!$student) {
                return 0.0;
            }
            
            // Trouver les IDs des classes de l'étudiant
            $classIds = DB::table('student_course_class')
                ->where('student_id', $studentId)
                ->pluck('course_class_id');
                
            if ($classIds->isEmpty()) {
                // Alternative : vérifier si l'étudiant est dans le modèle ESBTPEtudiant
                $esbtpStudent = \App\Models\ESBTPEtudiant::where('id', $studentId)
                    ->orWhere('user_id', function($query) use ($studentId) {
                        $query->select('user_id')
                            ->from('students')
                            ->where('id', $studentId);
                    })
                    ->first();
                    
                if ($esbtpStudent && $esbtpStudent->classe_id) {
                    // Utiliser la logique ESBTP pour les présences
                    return self::getESBTPAttendancePercentage($esbtpStudent->id, $startDate, $endDate);
                }
                
                return 100.0; // Si pas de classe, on considère 100% de présence
            }

            // Requête de base pour les sessions de cours des classes de l'étudiant
            $courseSessions = CourseSession::whereIn('course_class_id', $classIds);
            
            // Filtrer par période si spécifiée
            if ($startDate && $endDate) {
                $courseSessions->whereBetween('date', [$startDate, $endDate]);
            } elseif ($startDate) {
                $courseSessions->where('date', '>=', $startDate);
            } elseif ($endDate) {
                $courseSessions->where('date', '<=', $endDate);
            } else {
                // Par défaut, on prend le semestre en cours
                $currentDate = now();
                // Si on est dans le premier semestre (septembre à janvier)
                if ($currentDate->month >= 9 || $currentDate->month <= 1) {
                    $startDate = $currentDate->copy()->month(9)->startOfMonth();
                    $endDate = $currentDate->copy()->addYear()->month(1)->endOfMonth();
                } else { // Deuxième semestre (février à juin)
                    $startDate = $currentDate->copy()->month(2)->startOfMonth();
                    $endDate = $currentDate->copy()->month(6)->endOfMonth();
                }
                $courseSessions->whereBetween('date', [$startDate, $endDate]);
            }
            
            // Sessions passées seulement
            $courseSessions->where('date', '<=', now());
            
            // Compter le total de sessions
            $totalSessions = $courseSessions->count();
            
            if ($totalSessions === 0) {
                return 100.0; // Si pas de session, on considère 100% de présence
            }
            
            // Compter les présences
            $presentCount = self::where('student_id', $studentId)
                ->whereIn('status', ['present', 'late']) // présent ou en retard est considéré comme présent
                ->whereIn('course_session_id', $courseSessions->pluck('id'))
                ->count();
            
            // Calculer le pourcentage
            return ($presentCount / $totalSessions) * 100;
        } catch (\Exception $e) {
            \Log::error('Erreur dans le calcul du taux de présence: ' . $e->getMessage());
            return 0.0;
        }
    }
    
    /**
     * Calcule le pourcentage de présence pour un étudiant ESBTP.
     * 
     * @param int $studentId ID de l'étudiant ESBTP
     * @param string|null $startDate Date de début (format Y-m-d)
     * @param string|null $endDate Date de fin (format Y-m-d)
     * @return float Pourcentage de présence (0-100)
     */
    public static function getESBTPAttendancePercentage($studentId, $startDate = null, $endDate = null)
    {
        try {
            // Utiliser le modèle ESBTPAttendance pour calculer le taux de présence
            $query = \App\Models\ESBTPAttendance::where('etudiant_id', $studentId);
            
            // Filtrer par période si spécifiée
            if ($startDate && $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where('date', '>=', $startDate);
            } elseif ($endDate) {
                $query->where('date', '<=', $endDate);
            }
            
            $attendances = $query->get();
            $totalAttendances = $attendances->count();
            
            if ($totalAttendances === 0) {
                return 100.0; // Si pas de présence enregistrée, on considère 100% de présence
            }
            
            $presentCount = $attendances->whereIn('status', ['present', 'late'])->count();
            
            // Calculer le pourcentage
            return ($presentCount / $totalAttendances) * 100;
        } catch (\Exception $e) {
            \Log::error('Erreur dans le calcul du taux de présence ESBTP: ' . $e->getMessage());
            return 0.0;
        }
    }
} 