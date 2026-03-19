<?php

namespace App\Jobs;

use App\Imports\StudentImport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class ImportStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    public $timeout = 1800; // 30 minutos
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->onQueue('imports'); // Cola específica para importaciones
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Aumentar límites de memoria y tiempo
            ini_set('memory_limit', '1024M');
            ini_set('max_execution_time', 1800);

            // Deshabilitar validaciones únicas temporalmente para mejorar rendimiento
            config(['excel.imports.read_only' => true]);

            Log::info('Iniciando importación de estudiantes', ['file' => $this->filePath]);

            Excel::import(new StudentImport, $this->filePath);

            Log::info('Importación de estudiantes completada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error en importación de estudiantes', [
                'error' => $e->getMessage(),
                'file' => $this->filePath,
                'line' => $e->getLine()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job de importación falló completamente', [
            'error' => $exception->getMessage(),
            'file' => $this->filePath
        ]);
    }
}
