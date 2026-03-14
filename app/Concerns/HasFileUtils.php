<?php

namespace App\Concerns;

trait HasFileUtils
{
    public function getFileIcon(): string
    {
        $iconMap = [
            'application/pdf'  => 'fa-file-pdf',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fa-file-powerpoint',
            'image/jpeg' => 'fa-file-image',
            'image/png'  => 'fa-file-image',
            'image/gif'  => 'fa-file-image',
            'image/svg+xml' => 'fa-file-image',
            'text/plain' => 'fa-file-alt',
            'text/html'  => 'fa-file-code',
            'text/css'   => 'fa-file-code',
            'text/javascript' => 'fa-file-code',
            'application/json' => 'fa-file-code',
            'application/xml'  => 'fa-file-code',
            'application/zip'  => 'fa-file-archive',
            'application/x-rar-compressed' => 'fa-file-archive',
            'application/x-7z-compressed'  => 'fa-file-archive',
            'audio/mpeg' => 'fa-file-audio',
            'audio/wav'  => 'fa-file-audio',
            'video/mp4'  => 'fa-file-video',
            'video/mpeg' => 'fa-file-video',
        ];

        return $iconMap[$this->file_type] ?? 'fa-file';
    }

    public function getFormattedFileSize(): string
    {
        $bytes = $this->file_size;

        if ($bytes < 1024) return $bytes . ' octets';
        if ($bytes < 1048576) return round($bytes / 1024, 2) . ' Ko';
        if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' Mo';
        return round($bytes / 1073741824, 2) . ' Go';
    }
}
