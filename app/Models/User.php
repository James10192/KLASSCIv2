<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash; // ✅ CORRIGÉ : Ajouté pour le hashing
use OwenIt\Auditing\Contracts\Auditable;

class User extends Authenticatable implements Auditable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, \OwenIt\Auditing\Auditable;

    /** Seuil de présence "en ligne" : last_seen_at < N minutes. */
    public const PRESENCE_ONLINE_THRESHOLD_MINUTES = 2;

    /** True si le user a été actif dans la fenêtre de présence. */
    public function isOnline(): bool
    {
        return $this->last_seen_at
            && $this->last_seen_at->gt(now()->subMinutes(self::PRESENCE_ONLINE_THRESHOLD_MINUTES));
    }

    /**
     * Colonnes auditées (whitelist).
     *
     * Le mot de passe et les tokens sont déjà exclus globalement via
     * config/audit.php > exclude (password, remember_token, api_token,
     * two_factor_secret, two_factor_recovery_codes).
     *
     * @var array
     */
    protected $auditInclude = [
        'name',
        'first_name',
        'last_name',
        'email',
        'username',
        'phone',
        'is_active',
        'must_change_password',
        'employee_id',
        'position',
        'department',
        'profile_photo_path',
    ];

    /**
     * Événements à auditer.
     *
     * @var array
     */
    protected $auditEvents = [
        'created',
        'updated',
        'deleted',
        'restored',
    ];

    protected $table = 'users';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'username',
        'password',
        'phone',
        'address',
        'city',
        'profile_photo_path',
        'is_active',
        'last_login_at',
        'last_seen_at',
        'created_by',
        'updated_by',
        'position',
        'department',
        'office_location',
        'employee_id',
        'appointment_date',
        'birth_date',
        'must_change_password',
        'password_changed_at',
        'first_login_at',
        'dashboard_widgets',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'last_login_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'is_active' => 'boolean',
        'appointment_date' => 'date',
        'must_change_password' => 'boolean',
        'password_changed_at' => 'datetime',
        'first_login_at' => 'datetime',
        'dashboard_widgets' => 'array',
        // 'password' => 'hashed', // ❌ SUPPRIMÉ car non supporté
    ];

    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            // Si déjà hashé (bcrypt $2y$/$2b$, argon2 $argon2) on ne re-hashe pas
            $this->attributes['password'] = (str_starts_with($value, '$2y$') || str_starts_with($value, '$2b$') || str_starts_with($value, '$argon2'))
                ? $value
                : Hash::make($value);

            // Auto-update password_changed_at à chaque changement de MDP
            // Sauf si le model est en cours de création (pas encore en DB)
            if ($this->exists) {
                $this->attributes['password_changed_at'] = now();
                $this->attributes['must_change_password'] = false;
            }
        }
    }

    /**
     * Hook Eloquent : révoque TOUS les tokens Sanctum quand le password change
     * (audit sécurité 2026-05-21).
     *
     * Pourquoi : un token Sanctum volé survivait au changement de mot de passe.
     * Avec cette révocation, dès qu'un user (ou un admin pour lui) change son
     * password, tous les tokens existants sont invalidés et il faut s'en
     * générer de nouveaux.
     *
     * Impact pratique :
     *   - Auth web (session) : pas d'impact (la session reste valide).
     *   - klassci-cli (Sanctum token) : le user devra régénérer un token via
     *     `php artisan klassci:create-token` après reset.
     */
    protected static function booted(): void
    {
        static::updated(function (User $user) {
            if ($user->wasChanged('password')) {
                // tokens() est défini par HasApiTokens (Laravel\Sanctum).
                // Ne touche pas aux sessions HTTP — uniquement aux PAT.
                $user->tokens()->delete();
            }
        });
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isSuperAdmin()
    {
        return $this->can('admin.access');
    }

    public function isSecretary()
    {
        return $this->can('identity.school_manager');
    }

    public function isTeacher()
    {
        return $this->can('identity.teach');
    }

    public function isStudent()
    {
        return $this->can('identity.student');
    }

    public function isAdmin()
    {
        return $this->can('admin.access');
    }

    public function superAdmin()
    {
        return $this->hasOne(SuperAdmin::class);
    }

    public function secretaire()
    {
        return $this->hasOne(Secretaire::class);
    }

    public function etudiant()
    {
        return $this->hasOne(\App\Models\ESBTPEtudiant::class);
    }

    public function teacherProfile()
    {
        return $this->hasOne(\App\Models\ESBTPTeacher::class);
    }

    /** Alias rétrocompat : $user->teacher → ESBTPTeacher (utilisé par TeacherController, GradeController, NotificationController). */
    public function teacher()
    {
        return $this->teacherProfile();
    }

    /** Alias rétrocompat : $user->enseignant → ESBTPTeacher (utilisé par TeacherGradeController, TeacherAttendanceController). */
    public function enseignant()
    {
        return $this->teacherProfile();
    }

    public function parent()
    {
        return $this->hasOne(ESBTPParent::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    public function receivedAnnouncements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_user')
                    ->withPivot('read_at', 'is_read')
                    ->withTimestamps();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function directedUfrs()
    {
        return $this->hasMany(UFR::class, 'director_id');
    }

    public function coordinatedFormations()
    {
        return $this->hasMany(Formation::class, 'coordinator_id');
    }

    public function responsibleParcours()
    {
        return $this->hasMany(Parcours::class, 'responsable_id');
    }

    public function responsibleUEs()
    {
        return $this->hasMany(UniteEnseignement::class, 'responsable_id');
    }

    public function responsibleECs()
    {
        return $this->hasMany(ElementConstitutif::class, 'responsable_id');
    }

    public function courseSessions()
    {
        return $this->hasMany(CourseSession::class, 'teacher_id');
    }

    public function supervisedEvaluations()
    {
        return $this->belongsToMany(Evaluation::class, 'evaluation_supervisor');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function getUnreadAnnouncementsCountAttribute()
    {
        return $this->receivedAnnouncements()
                    ->wherePivot('is_read', false)
                    ->count();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Relation avec les séances de cours enseignées par l'utilisateur
     */
    public function seancesCours()
    {
        return $this->hasMany(ESBTPSeanceCours::class, 'teacher_id');
    }

}
