<?php

namespace App\Models;

use App\Concerns\HasFileUtils;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ESBTPEtudiantDocument extends Model
{
    use HasFileUtils;

    protected $table = 'esbtp_etudiant_documents';

    protected $fillable = [
        'etudiant_id',
        'titre',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'uploaded_by',
    ];

    public function etudiant()
    {
        return $this->belongsTo(ESBTPEtudiant::class, 'etudiant_id');
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getDownloadUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function toDocumentArray(): array
    {
        return [
            'id'           => $this->id,
            'titre'        => $this->titre,
            'description'  => $this->description,
            'file_name'    => $this->file_name,
            'file_size'    => $this->getFormattedFileSize(),
            'file_type'    => $this->file_type,
            'file_icon'    => $this->getFileIcon(),
            'download_url' => $this->getDownloadUrl(),
            'uploaded_by'  => $this->uploadedBy?->name,
            'created_at'   => $this->created_at->format('d/m/Y'),
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (ESBTPEtudiantDocument $doc) {
            Storage::disk('public')->delete($doc->file_path);
        });
    }
}
