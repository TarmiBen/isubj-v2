<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;

class CleanStudentNameSuffix extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:clean-name-suffix {--dry-run : Solo muestra qué se cambiaría, sin guardar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elimina el sufijo " (B)" del nombre de todos los alumnos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $students = Student::where('name', 'like', '%(B)%')->get();
        $total = $students->count();

        if ($total === 0) {
            $this->info('No se encontraron alumnos con "(B)" en el nombre.');
            return 0;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Se encontraron {$total} alumnos con \"(B)\" en el nombre.");

        $bar = $this->output->createProgressBar($total);
        $updated = 0;

        foreach ($students as $student) {
            $cleanName = trim(preg_replace('/\s*\(B\)\s*/', ' ', $student->name));

            if ($cleanName !== $student->name) {
                $this->newLine();
                $this->line("  {$student->id}: \"{$student->name}\" -> \"{$cleanName}\"");

                if (!$dryRun) {
                    $student->name = $cleanName;
                    $student->save();
                }

                $updated++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "✅ {$updated} alumnos actualizados.");

        return 0;
    }
}