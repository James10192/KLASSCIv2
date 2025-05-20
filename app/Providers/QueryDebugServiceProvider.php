<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryDebugServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        if (config('app.debug')) {
            DB::listen(function ($query) {
                $sql = $query->sql;
                $bindings = $query->bindings;
                $time = $query->time;

                // Replace bindings with actual values
                foreach ($bindings as $binding) {
                    $value = is_numeric($binding) ? $binding : "'".$binding."'";
                    $sql = preg_replace('/\?/', $value, $sql, 1);
                }

                Log::channel('queries')->debug('SQL Query', [
                    'sql' => $sql,
                    'time' => $time.'ms',
                    'connection' => $query->connectionName,
                    'file' => $this->getCallingFile(),
                ]);
            });
        }
    }

    protected function getCallingFile()
    {
        $trace = collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

        return $trace->first(function ($frame) {
            return !str_contains($frame['file'] ?? '', 'vendor')
                && !str_contains($frame['file'] ?? '', 'QueryDebugServiceProvider.php');
        })['file'] ?? 'unknown';
    }
}
