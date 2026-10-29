<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'template',
    ];

    /**
     * Get the certificates for the certificate type.
     */
    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Get the number of certificates issued for this type.
     */
    public function getCertificateCount()
    {
        return $this->certificates()->count();
    }

    /**
     * Get the number of active certificates for this type.
     */
    public function getActiveCertificateCount()
    {
        return $this->certificates()->where('status', 'issued')->count();
    }

    /**
     * Get the number of revoked certificates for this type.
     */
    public function getRevokedCertificateCount()
    {
        return $this->certificates()->where('status', 'revoked')->count();
    }

    /**
     * Get the number of expired certificates for this type.
     */
    public function getExpiredCertificateCount()
    {
        return $this->certificates()->where('status', 'expired')->count();
    }
} 