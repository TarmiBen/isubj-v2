# QR público de estatus de unidad + columna de firma

## 1. Qué se implementó

### a) UUID por unidad
- Nueva migración: `database/migrations/2026_06_13_000000_add_uuid_to_units_table.php`
  - Agrega columna `uuid` a `units` y la rellena para los registros existentes.
  - `app/Models/Unit.php`: genera `uuid` automáticamente al crear nuevas unidades (`booted()` -> `creating`).
  - Accessor `Unit::getPublicStatusUrlAttribute()` -> `public_status_url` = `route('unit.status', $uuid)`.

### b) Vista pública de estatus (sin login)
- Ruta: `GET /unidad/{uuid}` -> `unit.status` (en `routes/web.php`).
- Controlador: `app/Http/Controllers/UnitStatusController.php`.
- Vista: `resources/views/public/unit-status.blade.php`.
- Muestra (en grande): Materia, Unidad, Grupo, Ciclo (y si es el ciclo actual o uno anterior).
- Mensaje de estatus según el caso:
  - `CALIFICACIONES CARGADAS CORRECTAMENTE`
  - `Falta(n) N calificación(es) de alumno(s) por cargar`
  - `No hay calificaciones cargadas para esta unidad`
  - `No hay documento ni calificaciones cargadas para esta unidad`
  - `No hay alumnos activos registrados para esta unidad`
- No expone nombres ni calificaciones de alumnos (información pública/informativa solamente).

### c) Documento de unidad con QR y solo lectura
- `app/Services/UnitDocumentExportService.php`:
  - Toma el `document_src` de la unidad.
  - Inserta el QR (apunta a `public_status_url`) en el rango `AK2:AL5` (merge).
  - Protege la hoja (`getProtection()->setSheet(true)`) -> el archivo descargado queda de solo lectura.
  - Genera un archivo temporal nuevo en cada descarga; **no modifica el `document_src` original**.
- Botón "Descargar Documento de Unidad" (`InfolistAction::make('downloadUnitDocument')`) actualizado en:
  - `app/Filament/Resources/AssignmentResource/Pages/ViewAssignment.php` (admin)
  - `app/Filament/Teacher/Resources/AssignmentResource/Pages/ViewAssignment.php` (teacher)
  - Antes: `->url()` directo al archivo. Ahora: `->action()` que llama a `UnitDocumentExportService::download($record)`.

### d) Columna de firma en el formato de calificaciones
- `app/Filament/Exports/UnitGradesExport.php`:
  - Antes: `AK:AL` mergeados, header "Total".
  - Ahora: **sin merge** -> `AK11 = "Total"`, `AL11 = "Firma"`.
  - Por cada alumno: `AK{fila}` lleva la fórmula del total, `AL{fila}` queda vacía para que el alumno firme al imprimir.

## 2. Pasos para desplegar en producción

```bash
# Correr solo esta migración (evita conflictos con migraciones viejas pendientes)
php artisan migrate --path=database/migrations/2026_06_13_000000_add_uuid_to_units_table.php --force

# Verificar que todas las unidades tengan uuid
php artisan tinker --execute="echo App\Models\Unit::whereNull('uuid')->count();"
# Debe imprimir 0
```

No se requiere ningún seeder ni paso adicional. Las unidades creadas después de la migración generan su `uuid` automáticamente.

## 3. Pendientes / a revisar después
- Confirmar que `APP_URL` en `.env` de producción sea el dominio público correcto (el QR usa `route()`, que depende de esto).
- Probar la descarga de `downloadUnitDocument` con un `document_src` real en producción (en dev no había archivos físicos disponibles para varias unidades de prueba).
- Revisar visualmente el tamaño/posición del QR en `AK2:AL5` al imprimir el documento (ancho de columnas AK/AL = 10 cada una).
- Revisar el ancho de la columna `AL` ("Firma") para que tenga espacio suficiente al imprimir.