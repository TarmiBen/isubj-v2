# Mejoras en Visualización de Descuentos - Comando GenerateMonthlyFees

## Resumen
Se mejoró el comando `payments:generate-monthly-fees` para mostrar información detallada de todos los descuentos aplicados a cada alumno durante la generación de mensualidades, incluyendo descuentos automáticos, descuentos por referidos, y otra información relevante del estudiante.

## Cambios Realizados

### 1. Mejora en el método calculateDiscounts()
**Archivo**: `app/Console/Commands/GenerateMonthlyFees.php`

#### Antes:
- Mostraba solo el nombre del descuento: "Descuento automático: {nombre}"

#### Ahora:
- Muestra el nombre del descuento con el tipo y valor:
  - `"Descuento automático: {nombre} ({tipo})"` donde tipo puede ser:
    - `"10%"` para descuentos porcentuales
    - `"Monto fijo"` para descuentos de cantidad fija
- Para descuentos por referidos, muestra el nombre completo del alumno referido:
  - `"Descuento por referido: {nombre del referido} ({tipo})"`

### 2. Nuevo método getStudentExtraInfo()
Se agregó un nuevo método privado que recolecta información adicional del estudiante:

#### Información que muestra:
1. **Convenios activos**: 
   - Muestra el folio del convenio y su balance pendiente
   - Ejemplo: `"Convenio activo: CONV-2026-000001 (Balance: $5000)"`

2. **Referidos que hizo el alumno**:
   - Lista cuántos alumnos ha referido y sus nombres
   - Ejemplo: `"Ha referido 2 alumno(s): Juan Pérez, María García"`

3. **Quién lo refirió**:
   - Muestra el nombre del alumno que lo refirió
   - Ejemplo: `"Referido por: Carlos López"`

### 3. Mejora en la salida del modo --dry-run

#### Formato de salida mejorado:
```
[DRY] Alumno {id}: {nombre} → Subtotal: ${monto}
    • {nombre descuento 1} (-${monto})
    • {nombre descuento 2} (-${monto})
    ℹ️  {información adicional 1}
    ℹ️  {información adicional 2}
```

#### Ejemplos:

**Con descuentos:**
```
[DRY] Alumno 185: Ailin Jocelyn Hernández Jiménez → Subtotal: $2900.00 | Descuento: $200 → Total: $2700
    • Descuento automático: Descuento por Referido (Monto fijo) (-$200)
    ℹ️  Ha referido 2 alumno(s): Juan Pérez, María García
```

**Sin descuentos:**
```
[DRY] Alumno 187: Evelyn Trejo Cazares → Subtotal: $2900.00 (sin descuentos)
    ℹ️  Convenio activo: CONV-2026-000015 (Balance: $3500)
```

### 4. Mejora en la salida del proceso real (sin --dry-run)

#### Formato similar al dry-run:
```
✓ Alumno {id}: {nombre}
  Subtotal: ${subtotal} | Descuento: ${descuento} → Total: ${total}
    • {nombre descuento 1} (-${monto})
    • {nombre descuento 2} (-${monto})
    ℹ️  {información adicional}
```

**Sin descuentos:**
```
✓ Alumno {id}: {nombre} → ${total} (sin descuentos)
    ℹ️  {información adicional si existe}
```

### 5. Importaciones agregadas
Se agregó la importación del modelo `Agreement`:
```php
use App\Models\Agreement;
```

## Tipos de Descuentos Mostrados

### 1. Descuentos Automáticos
- Descuentos del catálogo configurados como automáticos
- Aplican a todos los estudiantes que cumplan las condiciones
- Se muestra el tipo (porcentaje o monto fijo) y el valor

### 2. Descuentos por Referidos
- Cuando el estudiante es referidor de otros alumnos activos
- Se muestra el nombre del alumno referido
- Se indica el tipo de descuento aplicado

### 3. Descuentos Apilables (Stackable)
- Si un descuento NO es apilable, se detiene después del primero
- Si es apilable, se aplican todos los descuentos elegibles

## Información Adicional Mostrada

### Convenios Activos
Muestra si el alumno tiene convenios de pago vigentes con:
- Folio del convenio
- Balance pendiente

### Referidos Activos
Si el alumno ha referido a otros estudiantes, muestra:
- Cantidad de referidos
- Nombres de los referidos

### Referidor
Si el alumno fue referido por otro, muestra:
- Nombre del alumno que lo refirió

## Beneficios

1. **Transparencia**: Se puede ver exactamente qué descuentos se están aplicando y por qué
2. **Auditoría**: Facilita verificar que los descuentos se están aplicando correctamente
3. **Contexto completo**: Muestra información adicional relevante del alumno
4. **Debugging**: Ayuda a identificar problemas en la configuración de descuentos
5. **Reportes**: Proporciona información valiosa para análisis y reportes

## Ejemplo de Salida Completa

```bash
$ php artisan payments:generate-monthly-fees --dry-run --config=2

Generando mensualidades para 5/2026 [DRY-RUN]
  Config #2 (Mensualidad Licenciatura Escolarizado) [Gen: 20, Carrera: Licenciatura en Enfermería, Modalidad: Escolarizada]: 2 alumnos
    [DRY] Alumno 185: Ailin Jocelyn Hernández Jiménez → Subtotal: $2900.00 | Descuento: $200 → Total: $2700
        • Descuento automático: Descuento por Referido (Monto fijo) (-$200)
        ℹ️  Ha referido 1 alumno(s): Juan Pérez
    [DRY] Alumno 187: Evelyn Trejo Cazares → Subtotal: $2900.00 | Descuento: $200 → Total: $2700
        • Descuento automático: Descuento por Referido (Monto fijo) (-$200)
        ℹ️  Convenio activo: CONV-2026-000023 (Balance: $1500)
Resultado: 2 generadas, 0 existentes, 0 errores.
```

## Notas Técnicas

- Los descuentos se calculan antes de crear las órdenes de pago
- La información se muestra tanto en modo simulación (--dry-run) como en producción
- El método `getStudentExtraInfo()` solo consulta información si es necesario
- Los convenios y referidos se consultan con sus relaciones para optimizar las queries

## Uso

```bash
# Ver descuentos en modo simulación
php artisan payments:generate-monthly-fees --dry-run

# Ver descuentos para una configuración específica
php artisan payments:generate-monthly-fees --dry-run --config=2

# Generar mensualidades mostrando descuentos (producción)
php artisan payments:generate-monthly-fees

# Generar para un mes específico
php artisan payments:generate-monthly-fees --month=6 --year=2026
```

