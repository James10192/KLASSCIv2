<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ESBTPBonSortie extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'esbtp_bons_sortie';

    protected $fillable = [
        'reference',
        'titre',
        'description',
        'destinataire',
        'date_sortie',
        'statut',
        'createur_id',
        'approbateur_id',
        'notification_sent_at',
        'approved_at',
    ];

    /**
     * Get the creator of the bon de sortie.
     */
    public function createur()
    {
        return $this->belongsTo(User::class, 'createur_id');
    }

    /**
     * Get the approver of the bon de sortie.
     */
    public function approbateur()
    {
        return $this->belongsTo(User::class, 'approbateur_id');
    }

    /**
     * Get the notifications for the bon de sortie.
     */
    public function notifications()
    {
        return $this->hasMany(ESBTPBonSortieNotification::class, 'bon_sortie_id');
    }

    /**
     * Get the depense associated with the bon de sortie.
     */
    public function depense()
    {
        return $this->hasOne(ESBTPDepense::class, 'bon_sortie_id');
    }

    /**
     * Get the depenses associated with the bon de sortie.
     */
    public function depenses()
    {
        return $this->hasMany(ESBTPDepense::class, 'bon_sortie_id');
    }
} 