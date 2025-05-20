<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ESBTPLogsController extends Controller
{
    /**
     * Display the system logs page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get Laravel logs from storage/logs
        $logFiles = File::files(storage_path('logs'));
        $logs = [];

        foreach ($logFiles as $file) {
            $filename = basename($file);
            $size = File::size($file);
            $lastModified = File::lastModified($file);

            $logs[] = [
                'filename' => $filename,
                'size' => $this->formatSize($size),
                'last_modified' => date('Y-m-d H:i:s', $lastModified),
                'path' => $file->getPathname(),
            ];
        }

        return view('esbtp.logs.index', compact('logs'));
    }

    /**
     * Show the contents of a specific log file.
     *
     * @param  string  $filename
     * @return \Illuminate\View\View
     */
    public function show($filename)
    {
        $path = storage_path('logs/' . $filename);

        if (!File::exists($path)) {
            return redirect()->route('esbtp.logs.index')
                ->with('error', 'Le fichier de log demandé n\'existe pas.');
        }

        $contents = File::get($path);

        return view('esbtp.logs.show', compact('contents', 'filename'));
    }

    /**
     * Download a log file.
     *
     * @param  string  $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($filename)
    {
        $path = storage_path('logs/' . $filename);

        if (!File::exists($path)) {
            return redirect()->route('esbtp.logs.index')
                ->with('error', 'Le fichier de log demandé n\'existe pas.');
        }

        return response()->download($path);
    }

    /**
     * Delete a log file.
     *
     * @param  string  $filename
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($filename)
    {
        $path = storage_path('logs/' . $filename);

        if (File::exists($path)) {
            File::delete($path);
            return redirect()->route('esbtp.logs.index')
                ->with('success', 'Le fichier de log a été supprimé avec succès.');
        }

        return redirect()->route('esbtp.logs.index')
            ->with('error', 'Le fichier de log n\'existe pas.');
    }

    /**
     * Format file size to human readable format.
     *
     * @param  int  $size
     * @return string
     */
    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
    }
}
