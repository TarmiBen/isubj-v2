<?php

namespace App\Filament\Support;

use App\Models\Student;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

/**
 * Columna de "Alumno" que se puede buscar y ordenar.
 *
 * `Student::full_name` es un accessor de PHP (Student::getFullNameAttribute()),
 * no una columna real. Si se usa `TextColumn::make('student.full_name')->searchable()`
 * a secas, Filament arma `WHERE students.full_name LIKE ...` y MySQL revienta con
 *   SQLSTATE[42S22] Column not found: 1054 Unknown column 'full_name'
 * Por eso aquí se pasan closures explícitas de búsqueda y ordenamiento.
 */
class StudentColumn
{
    /**
     * @param  string  $relationship  Nombre de la relación al Student (student, referrer, referred…)
     */
    public static function make(string $relationship = 'student', ?string $label = 'Alumno'): TextColumn
    {
        return TextColumn::make("{$relationship}.full_name")
            ->label($label)
            ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas(
                $relationship,
                fn (Builder $q) => static::applyNameSearch($q, $search),
            ))
            ->sortable(query: function (Builder $query, string $direction) use ($relationship): Builder {
                $foreignKey = $query->getModel()->getTable() . '.' . static::foreignKey($relationship);

                return $query
                    ->orderBy(static::subquery('last_name1', $foreignKey), $direction)
                    ->orderBy(static::subquery('last_name2', $foreignKey), $direction)
                    ->orderBy(static::subquery('name', $foreignKey), $direction);
            })
            ->description(fn ($record) => data_get($record, "{$relationship}.student_number"))
            ->placeholder('—');
    }

    /**
     * Busca en nombre completo (en cualquier orden de palabras) y en matrícula.
     */
    public static function applyNameSearch(Builder $query, string $search): Builder
    {
        $terms = preg_split('/\s+/', trim($search), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $query->where(function (Builder $q) use ($terms, $search) {
            foreach ($terms as $term) {
                $q->where(
                    fn (Builder $inner) => $inner
                        ->whereRaw("CONCAT_WS(' ', name, last_name1, last_name2) LIKE ?", ["%{$term}%"])
                        ->orWhere('student_number', 'like', "%{$term}%")
                );
            }

            if ($terms === []) {
                $q->whereRaw("CONCAT_WS(' ', name, last_name1, last_name2) LIKE ?", ["%{$search}%"]);
            }
        });
    }

    protected static function foreignKey(string $relationship): string
    {
        return match ($relationship) {
            'student'  => 'student_id',
            'referrer' => 'referrer_student_id',
            'referred' => 'referred_student_id',
            default    => "{$relationship}_id",
        };
    }

    /**
     * Subconsulta correlacionada para poder ordenar por una columna del alumno
     * sin tener que hacer join (que rompería otros filtros de la tabla).
     */
    protected static function subquery(string $column, string $qualifiedForeignKey): Builder
    {
        return Student::query()
            ->select($column)
            ->whereColumn('students.id', $qualifiedForeignKey)
            ->limit(1);
    }
}
