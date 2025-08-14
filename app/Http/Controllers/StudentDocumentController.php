<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Document;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    public function destroy($studentId, $documentId): RedirectResponse
    {
        $student = Student::findOrFail($studentId);
        $document = $student->documents()->findOrFail($documentId);

        if ($document->src && Storage::disk('public')->exists($document->src)) {
            Storage::disk('public')->delete($document->src);
        }
        $document->delete();
        return redirect()->back()->with('success', 'Documento eliminado correctamente.');
    }
}
