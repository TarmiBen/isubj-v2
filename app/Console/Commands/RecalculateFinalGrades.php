<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assignment;
use App\Models\FinalGrade;

class RecalculateFinalGrades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grades:recalculate {assignment_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcular calificaciones finales para uno o todos los assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $assignmentId = $this->argument('assignment_id');

        if ($assignmentId) {
            // Recalcular para un assignment específico
            $this->info("Recalculando calificaciones finales para assignment ID: {$assignmentId}");
            FinalGrade::recalculateForAssignment((int)$assignmentId);
            $this->info("✅ Calificaciones recalculadas para assignment {$assignmentId}");
        } else {
            // Recalcular para todos los assignments
            $this->info("Recalculando calificaciones finales para todos los assignments...");

            $assignments = Assignment::with(['units', 'students'])->get();
            $total = $assignments->count();
            $bar = $this->output->createProgressBar($total);

            foreach ($assignments as $assignment) {
                FinalGrade::recalculateForAssignment($assignment->id);
                $bar->advance();
            }

            $bar->finish();
            $this->info("\n✅ Calificaciones recalculadas para {$total} assignments");
        }

        return 0;
    }
}
