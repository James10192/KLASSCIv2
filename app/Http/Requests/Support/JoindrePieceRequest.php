<?php

namespace App\Http\Requests\Support;

use App\Services\Care\ClientMasterSupport;
use Illuminate\Foundation\Http\FormRequest;

class JoindrePieceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        // Un premier tri pour un message clair ; le Master relit le contenu et fait foi.
        $max = intdiv(app(ClientMasterSupport::class)->limites()['piece_octets_max'], 1024);

        return [
            'fichier' => ['required', 'file', 'mimes:png,jpg,jpeg,webp,pdf', 'max:'.$max],
            'cle' => ['required', 'string', 'regex:/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i'],
        ];
    }

    public function messages(): array
    {
        return [
            'fichier.required' => 'Choisissez un fichier.',
            'fichier.mimes' => 'Seules les images (PNG, JPEG, WebP) et les PDF sont acceptés.',
            'fichier.max' => $trop = 'Le fichier dépasse '.self::enMo(app(ClientMasterSupport::class)->limites()['piece_octets_max']).' Mo.',
            // PHP l'a refuse avant Laravel (upload_max_filesize) : c'est la meme cause.
            'fichier.uploaded' => $trop,
        ];
    }

    /** 2097152 → « 2 », 1572864 → « 1,5 ». */
    public static function enMo(int $octets): string
    {
        return rtrim(rtrim(number_format($octets / 1048576, 1, ',', ''), '0'), ',');
    }
}
