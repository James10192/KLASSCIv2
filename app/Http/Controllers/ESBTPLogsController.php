<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ESBTPLogsController extends Controller
{
    /**
     * Affiche la liste des logs système
     */
    public function index(Request $request)
    {
        $logFiles = [];
        $selectedLog = $request->get('log', 'laravel.log');
        $logContent = '';
        
        // Récupérer la liste des fichiers de logs
        $logPath = storage_path('logs');
        if (File::exists($logPath)) {
            $files = File::files($logPath);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'log') {
                    $fileName = pathinfo($file, PATHINFO_BASENAME);
                    $logFiles[] = [
                        'name' => $fileName,
                        'size' => File::size($file),
                        'modified' => Carbon::createFromTimestamp(File::lastModified($file))
                    ];
                }
            }
        }
        
        // Lire le contenu du log sélectionné
        $selectedLogPath = storage_path('logs/' . $selectedLog);
        if (File::exists($selectedLogPath)) {
            $logContent = File::get($selectedLogPath);
            
            // Limiter la taille pour éviter les problèmes de mémoire
            if (strlen($logContent) > 1048576) { // 1MB
                $logContent = substr($logContent, -1048576);
                $logContent = "... (fichier tronqué - affichage des dernières 1MB)\n\n" . $logContent;
            }
        }
        
        // Analyser les logs pour extraire les erreurs récentes
        $recentErrors = $this->parseRecentErrors($logContent);
        
        return view('esbtp.logs.index', compact('logFiles', 'selectedLog', 'logContent', 'recentErrors'));
    }
    
    /**
     * Affiche le détail d'un log spécifique
     */
    public function show($logFile)
    {
        $logPath = storage_path('logs/' . $logFile);
        
        if (!File::exists($logPath) || !str_ends_with($logFile, '.log')) {
            abort(404, 'Fichier de log non trouvé');
        }
        
        $logContent = File::get($logPath);
        $logInfo = [
            'name' => $logFile,
            'size' => File::size($logPath),
            'modified' => Carbon::createFromTimestamp(File::lastModified($logPath)),
            'lines' => substr_count($logContent, "\n") + 1
        ];
        
        return view('esbtp.logs.show', compact('logFile', 'logContent', 'logInfo'));
    }
    
    /**
     * Vide un fichier de log
     */
    public function clear($logFile)
    {
        $logPath = storage_path('logs/' . $logFile);
        
        if (!File::exists($logPath) || !str_ends_with($logFile, '.log')) {
            return back()->with('error', 'Fichier de log non trouvé');
        }
        
        File::put($logPath, '');
        
        return back()->with('success', "Le fichier de log {$logFile} a été vidé avec succès.");
    }
    
    /**
     * Télécharge un fichier de log
     */
    public function download($logFile)
    {
        $logPath = storage_path('logs/' . $logFile);
        
        if (!File::exists($logPath) || !str_ends_with($logFile, '.log')) {
            abort(404, 'Fichier de log non trouvé');
        }
        
        return response()->download($logPath);
    }
    
    /**
     * Parse les erreurs récentes du contenu du log
     */
    private function parseRecentErrors($content)
    {
        $errors = [];
        $lines = explode("\n", $content);
        
        foreach (array_reverse($lines) as $line) {
            if (preg_match('/\[(.*?)\] local\.ERROR: (.*)/', $line, $matches)) {
                $errors[] = [
                    'timestamp' => $matches[1],
                    'message' => trim($matches[2]),
                    'level' => 'ERROR'
                ];
                
                if (count($errors) >= 10) break; // Limiter à 10 erreurs récentes
            } elseif (preg_match('/\[(.*?)\] local\.WARNING: (.*)/', $line, $matches)) {
                $errors[] = [
                    'timestamp' => $matches[1],
                    'message' => trim($matches[2]),
                    'level' => 'WARNING'
                ];
                
                if (count($errors) >= 10) break;
            }
        }
        
        return array_reverse($errors);
    }
}
