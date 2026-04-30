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

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

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

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isSuperAdmin()
    {
        return $this->can('access_admin');
    }

    public function isSecretary()
    {
        return $this->can('can_manage_school');
    }

    public function isTeacher()
    {
        return $this->can('can_teach');
    }

    public function isStudent()
    {
        return $this->can('can_view_student_features');
    }

    public function isParent()
    {
        return $this->can('can_view_parent_features');
    }

    public function isAdmin()
    {
        return $this->can('access_admin');
    }

    public function superAdmin()
    {
        return $this->hasOne(SuperAdmin::class);
    }

    public function secretaire()
    {
        return $this->hasOne(Secretaire::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function etudiant()
    {
        return $this->hasOne(\App\Models\ESBTPEtudiant::class);
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

    /**
     * Relation avec le profil enseignant (nouveau système)
     */
    public function enseignantProfile()
    {
        return $this->hasOne(ESBTPEnseignantProfile::class, 'user_id');
    }
}


// namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens;
// use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Permission\Traits\HasRoles;

// class User extends Authenticatable
// {
//     use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

//     /**
//      * La table associée au modèle.
//      *
//      * @var string
//      */
//     protected $table = 'users';

//     /**
//      * Les attributs qui sont assignables en masse.
//      *
//      * @var array<int, string>
//      */
//     protected $fillable = [
//         'name',
//         'first_name',
//         'last_name',
//         'email',
//         'username',
//         'password',
//         'phone',
//         'address',
//         'city',
//         'profile_photo_path',
//         'is_active',
//         'last_login_at',
//         'created_by',
//         'updated_by',
//         'position',
//         'department',
//         'office_location',
//         'employee_id',
//         'appointment_date',
//         'birth_date',
//     ];

//     /**
//      * Les attributs qui doivent être cachés pour la sérialisation.
//      *
//      * @var array<int, string>
//      */
//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     /**
//      * Les attributs qui doivent être convertis.
//      *
//      * @var array<string, string>
//      */
//     protected $casts = [
//         'email_verified_at' => 'datetime',
//         'birth_date' => 'date',
//         'last_login_at' => 'datetime',
//         'is_active' => 'boolean',
//         'appointment_date' => 'date',
//    //     'password' => 'hashed',
//     ];

//     /**
//      * Obtenir le nom complet de l'utilisateur.
//      *
//      * @return string
//      */
//     public function getFullNameAttribute()
//     {
//         return "{$this->first_name} {$this->last_name}";
//     }

//     /**
//      * Vérifier si l'utilisateur est un superadmin.
//      *
//      * @return bool
//      */
//     public function isSuperAdmin()
//     {
//         return $this->hasRole('superAdmin');
//     }

//     /**
//      * Vérifier si l'utilisateur est un secrétaire.
//      *
//      * @return bool
//      */
//     public function isSecretary()
//     {
//         return $this->hasRole('secretaire');
//     }

//     /**
//      * Vérifier si l'utilisateur est un enseignant.
//      *
//      * @return bool
//      */
//     public function isTeacher()
//     {
//         return $this->hasRole('teacher');
//     }

//     /**
//      * Vérifier si l'utilisateur est un étudiant.
//      *
//      * @return bool
//      */
//     public function isStudent()
//     {
//         return $this->hasRole('etudiant');
//     }

//     /**
//      * Vérifier si l'utilisateur est un parent.
//      *
//      * @return bool
//      */
//     public function isParent()
//     {
//         return $this->hasRole('parent');
//     }

//     /**
//      * Vérifier si l'utilisateur est un administrateur.
//      *
//      * @return bool
//      */
//     public function isAdmin()
//     {
//         return $this->hasAnyRole(['superAdmin']);
//     }

//     /**
//      * Relation avec le profil de superadmin.
//      */
//     public function superAdmin()
//     {
//         return $this->hasOne(SuperAdmin::class);
//     }

//     /**
//      * Relation avec le profil de secrétaire.
//      */
//     public function secretaire()
//     {
//         return $this->hasOne(Secretaire::class);
//     }

//     /**
//      * Relation avec le profil d'enseignant.
//      */
//     public function teacher()
//     {
//         return $this->hasOne(Teacher::class);
//     }

//     /**
//      * Relation avec le profil d'étudiant.
//      */
//     public function etudiant()
//     {
//         return $this->hasOne(ESBTP\ESBTPEtudiant::class);
//     }

//     /**
//      * Relation avec le profil de parent.
//      */
//     public function parent()
//     {
//         return $this->hasOne(ESBTPParent::class);
//     }

//     /**
//      * Relation avec les annonces créées par l'utilisateur.
//      */
//     public function announcements()
//     {
//         return $this->hasMany(Announcement::class, 'created_by');
//     }

//     /**
//      * Relation avec les annonces reçues par l'utilisateur.
//      */
//     public function receivedAnnouncements()
//     {
//         return $this->belongsToMany(Announcement::class, 'announcement_user')
//                     ->withPivot('read_at', 'is_read')
//                     ->withTimestamps();
//     }

//     /**
//      * Relation avec l'utilisateur qui a créé ce compte.
//      */
//     public function createdBy()
//     {
//         return $this->belongsTo(User::class, 'created_by');
//     }

//     /**
//      * Relation avec l'utilisateur qui a mis à jour ce compte.
//      */
//     public function updatedBy()
//     {
//         return $this->belongsTo(User::class, 'updated_by');
//     }

//     /**
//      * Relation avec les UFRs dirigées par l'utilisateur.
//      */
//     public function directedUfrs()
//     {
//         return $this->hasMany(UFR::class, 'director_id');
//     }

//     /**
//      * Relation avec les formations coordonnées par l'utilisateur.
//      */
//     public function coordinatedFormations()
//     {
//         return $this->hasMany(Formation::class, 'coordinator_id');
//     }

//     /**
//      * Relation avec les parcours dont l'utilisateur est responsable.
//      */
//     public function responsibleParcours()
//     {
//         return $this->hasMany(Parcours::class, 'responsable_id');
//     }

//     /**
//      * Relation avec les UEs dont l'utilisateur est responsable.
//      */
//     public function responsibleUEs()
//     {
//         return $this->hasMany(UniteEnseignement::class, 'responsable_id');
//     }

//     /**
//      * Relation avec les ECs dont l'utilisateur est responsable.
//      */
//     public function responsibleECs()
//     {
//         return $this->hasMany(ElementConstitutif::class, 'responsable_id');
//     }

//     /**
//      * Relation avec les sessions de cours données par l'utilisateur.
//      */
//     public function courseSessions()
//     {
//         return $this->hasMany(CourseSession::class, 'teacher_id');
//     }

//     /**
//      * Relation avec les évaluations supervisées par l'utilisateur.
//      */
//     public function supervisedEvaluations()
//     {
//         return $this->belongsToMany(Evaluation::class, 'evaluation_supervisor');
//     }

//     /**
//      * Relation avec les documents créés par l'utilisateur.
//      */
//     public function documents()
//     {
//         return $this->hasMany(Document::class, 'created_by');
//     }

//     /**
//      * Obtenir le nombre d'annonces non lues.
//      */
//     public function getUnreadAnnouncementsCountAttribute()
//     {
//         return $this->receivedAnnouncements()
//                     ->wherePivot('is_read', false)
//                     ->count();
//     }

//     /**
//      * Scope pour les utilisateurs actifs.
//      */
//     public function scopeActive($query)
//     {
//         return $query->where('is_active', true);
//     }

//     /**
//      * Scope pour filtrer par type d'utilisateur.
//      */
//     public function scopeOfType($query, $type)
//     {
//         return $query->where('user_type', $type);
//     }
// }
