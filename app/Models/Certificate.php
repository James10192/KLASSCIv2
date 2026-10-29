<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'student_id',
        'certificate_type_id',
        'certificate_number',
        'issue_date',
        'expiry_date',
        'remarks',
        'issued_by',
        'status', // issued, revoked, expired
        'file_path',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    /**
     * Get the student that owns the certificate.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the certificate type that owns the certificate.
     */
    public function certificateType()
    {
        return $this->belongsTo(CertificateType::class);
    }

    /**
     * Get the user who issued the certificate.
     */
    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * Scope a query to only include certificates of a specific type.
     */
    public function scopeOfType($query, $typeId)
    {
        return $query->where('certificate_type_id', $typeId);
    }

    /**
     * Scope a query to only include certificates with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include certificates issued in a specific date range.
     */
    public function scopeIssuedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('issue_date', [$startDate, $endDate]);
    }

    /**
     * Check if the certificate is expired.
     */
    public function isExpired()
    {
        if (!$this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    /**
     * Check if the certificate is valid.
     */
    public function isValid()
    {
        return $this->status === 'issued' && !$this->isExpired();
    }

    /**
     * Revoke the certificate.
     */
    public function revoke($remarks = null)
    {
        $this->status = 'revoked';
        
        if ($remarks) {
            $this->remarks = $remarks;
        }
        
        $this->save();
    }
} 