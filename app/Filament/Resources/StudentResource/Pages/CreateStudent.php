<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Student;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        $student = $this->record;

        $this->saveDocuments($student);
    }

    protected function saveDocuments($student): void
    {
        $documentInputs = [
            'Acta de nacimiento' => 'acta_nacimiento_path',
            'CURP' => 'curp_documento_path',
            'INE' => 'ine_path',
        ];

        foreach ($documentInputs as $name => $field) {
            $storedPath = $this->data[$field] ?? null;

            if (!$storedPath || !is_string($storedPath)) {
                continue;
            }

            $student->documents()->create([
                'name' => $name,
                'src' => 'documents/' . $storedPath,
                'meta' => [],
            ]);
        }
    }

}
