# Cambios en MonthlyFeeConfig - Carrera y Modalidad

## Resumen
Se agregaron campos para filtrar por **Carrera** y **Modalidad** en las configuraciones de mensualidades (`MonthlyFeeConfig`), permitiendo aplicar configuraciones específicas solo a estudiantes inscritos en determinada carrera y/o modalidad.

## Archivos Modificados

### 1. Base de Datos
**Migración**: `2026_05_08_183018_add_career_and_modality_to_monthly_fee_configs_table.php`
- Se agregaron dos nuevos campos a la tabla `monthly_fee_configs`:
  - `career_id` (nullable, foreign key a `careers`)
  - `modality_id` (nullable, foreign key a `modalities`)
- Ambos campos son opcionales (NULL = aplica a todas)

### 2. Modelo MonthlyFeeConfig
**Archivo**: `app/Models/MonthlyFeeConfig.php`
- Se agregaron `career_id` y `modality_id` al array `$fillable`
- Se agregaron dos nuevas relaciones:
  - `career()`: relación BelongsTo con Career
  - `modality()`: relación BelongsTo con Modality

### 3. Recurso de Filament
**Archivo**: `app/Filament/Resources/MonthlyFeeConfigResource.php`
- Se importaron los modelos `Career` y `Modality`
- **Formulario**: Se agregaron dos nuevos campos Select:
  - "Carrera (vacío = todas)" - carga carreras activas
  - "Modalidad (vacío = todas)" - carga modalidades activas
- **Tabla**: Se agregaron dos nuevas columnas:
  - Carrera (muestra "Todas" si es NULL)
  - Modalidad (muestra "Todas" si es NULL)

### 4. Comando GenerateMonthlyFees
**Archivo**: `app/Console/Commands/GenerateMonthlyFees.php`
- Se agregó eager loading de `career` y `modality` en la consulta de configuraciones
- Se implementó filtrado por carrera:
  - Filtra inscripciones solo de estudiantes cuyo grupo pertenece al periodo de la carrera especificada
  - Relación: `inscription → group → period → career_id`
- Se implementó filtrado por modalidad:
  - Filtra inscripciones solo de estudiantes cuyo grupo pertenece a una carrera con la modalidad especificada
  - Relación: `inscription → group → period → career → modality_id`
- Se mejoró el mensaje de log para mostrar los filtros aplicados

## Funcionamiento

### Configuración
Al crear/editar una configuración de mensualidades, ahora puedes:
1. Seleccionar una **Carrera específica** o dejar vacío para aplicar a todas
2. Seleccionar una **Modalidad específica** o dejar vacío para aplicar a todas
3. Combinar con el filtro de **Generación** existente

### Comportamiento del Comando
Cuando se ejecuta `php artisan payments:generate-monthly-fees`:
- Si `career_id` está configurado: solo generará mensualidades para estudiantes inscritos en grupos de esa carrera
- Si `modality_id` está configurado: solo generará mensualidades para estudiantes inscritos en grupos cuya carrera tenga esa modalidad
- Si ambos están vacíos: generará para todos los estudiantes activos (respetando generación si está configurada)

### Ejemplos de Uso

**Ejemplo 1**: Mensualidad solo para Licenciatura en Derecho
- Carrera: Licenciatura en Derecho
- Modalidad: (vacío)
- Generación: (vacío)
- Resultado: Aplica a todos los estudiantes de Derecho, cualquier modalidad y generación

**Ejemplo 2**: Mensualidad solo para carreras de modalidad "Escolarizado"
- Carrera: (vacío)
- Modalidad: Escolarizado
- Generación: 20
- Resultado: Aplica a todos los estudiantes de la generación 20 en cualquier carrera que sea modalidad escolarizado

**Ejemplo 3**: Mensualidad para todas las carreras y modalidades
- Carrera: (vacío)
- Modalidad: (vacío)
- Generación: (vacío)
- Resultado: Aplica a absolutamente todos los estudiantes activos

## Prueba
El comando fue probado exitosamente en modo `--dry-run`:
```bash
php artisan payments:generate-monthly-fees --dry-run
```

## Estructura de Relaciones
```
MonthlyFeeConfig
  ├─ generation_id → Generation
  ├─ career_id → Career
  └─ modality_id → Modality

Filtrado aplicado en:
Inscription (status=active)
  └─ Student
      └─ generation_id (si config.generation_id está configurado)
  └─ Group
      └─ Period
          └─ career_id (si config.career_id está configurado)
          └─ Career
              └─ modality_id (si config.modality_id está configurado)
```

