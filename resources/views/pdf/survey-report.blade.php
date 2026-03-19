<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1a1a1a;
            line-height: 1.5;
        }

        /* ── Encabezado ── */
        .header {
            text-align: center;
            padding: 16px 0 12px;
            border-bottom: 2px solid #1e40af;
            margin-bottom: 16px;
        }
        .header h1 { font-size: 15px; color: #1e40af; }
        .header h2 { font-size: 11px; color: #374151; margin-top: 4px; }
        .header .meta { font-size: 9px; color: #6b7280; margin-top: 4px; }

        /* ── Títulos de sección ── */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #ffffff;
            background-color: #1e40af;
            padding: 6px 10px;
            margin-bottom: 12px;
            margin-top: 20px;
        }

        /* ── Tarjeta de docente ── */
        .teacher-card {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
        .teacher-card-header {
            background-color: #eff6ff;
            padding: 7px 10px;
            border-bottom: 1px solid #bfdbfe;
        }
        .teacher-card-header .teacher-name {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
        }
        .teacher-card-header .teacher-meta {
            font-size: 9px;
            color: #4b5563;
            margin-top: 2px;
        }
        .teacher-card-body { padding: 8px 10px; }

        /* ── Preguntas numéricas ── */
        .questions-numeric { margin-bottom: 8px; }
        .questions-numeric .sub-title {
            font-size: 9px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2px;
        }
        .q-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 4px;
        }
        .q-row:nth-child(even) { background-color: #f9fafb; }
        .q-text { flex: 1; color: #374151; padding-right: 8px; }
        .q-score {
            font-weight: bold;
            min-width: 42px;
            text-align: right;
            color: #1d4ed8;
        }
        .overall-row {
            margin-top: 6px;
            padding: 4px 4px;
            background-color: #dbeafe;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            border-radius: 3px;
        }

        /* ── Barra de progreso ── */
        .score-bar-wrap {
            width: 60px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            display: inline-block;
            vertical-align: middle;
            margin-left: 6px;
        }
        .score-bar-fill {
            height: 6px;
            border-radius: 3px;
            background-color: #1d4ed8;
        }

        /* ── Preguntas abiertas ── */
        .questions-open { margin-top: 8px; }
        .open-question-block { margin-bottom: 8px; }
        .open-question-text {
            font-weight: bold;
            color: #374151;
            margin-bottom: 4px;
            font-size: 9.5px;
        }
        .open-responses { padding-left: 12px; }
        .open-response {
            color: #4b5563;
            padding: 2px 0;
            border-left: 2px solid #93c5fd;
            padding-left: 6px;
            margin-bottom: 2px;
            font-size: 9px;
        }
        .no-responses { color: #9ca3af; font-style: italic; font-size: 9px; }

        /* ── Separador de grupo ── */
        .group-header {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 4px;
            padding: 6px 10px;
            margin-bottom: 10px;
            margin-top: 16px;
        }
        .group-header .group-code {
            font-size: 11px;
            font-weight: bold;
            color: #166534;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 24px;
            border-top: 1px solid #d1d5db;
            padding-top: 8px;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }

        /* ── Color helpers ── */
        .score-high  { color: #16a34a; }
        .score-mid   { color: #ca8a04; }
        .score-low   { color: #dc2626; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- ENCABEZADO                                            --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="header">
    <h1>Reporte de Evaluación Docente</h1>
    <h2>{{ $survey->name }}</h2>
    <div class="meta">Ciclo: <strong>{{ $cycle->name }}</strong> &nbsp;|&nbsp; Generado: {{ now()->format('d/m/Y H:i') }}</div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 1 — RESUMEN POR DOCENTE (TODAS LAS MATERIAS) --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="section-title">SECCIÓN 1 — RESUMEN POR DOCENTE (PROMEDIO DE TODAS LAS MATERIAS)</div>

@forelse($teacherSummaries as $teacher)
    <div class="teacher-card">
        <div class="teacher-card-header">
            <div class="teacher-name">{{ $teacher['teacher_name'] }}</div>
            <div class="teacher-meta">
                Evaluaciones recibidas: {{ $teacher['total_responses'] }}
                @if($teacher['overall_average'] !== null)
                    &nbsp;|&nbsp; Promedio general:
                    <strong class="{{ $teacher['overall_average'] >= 4 ? 'score-high' : ($teacher['overall_average'] >= 3 ? 'score-mid' : 'score-low') }}">
                        {{ $teacher['overall_average'] }}/5.0
                    </strong>
                @endif
            </div>
        </div>
        <div class="teacher-card-body">

            {{-- Preguntas numéricas --}}
            @php
                $numericQs = collect($teacher['question_stats'])->where('type', '!=', 'text');
                $textQs    = collect($teacher['question_stats'])->where('type', 'text');
            @endphp

            @if($numericQs->isNotEmpty())
                <div class="questions-numeric">
                    <div class="sub-title">Preguntas de escala / opción</div>
                    @foreach($numericQs as $q)
                        <div class="q-row">
                            <span class="q-text">{{ $q['question'] }}</span>
                            <span class="q-score">
                                @if($q['average'] !== null)
                                    {{ $q['average'] }}/5.0
                                    <span class="score-bar-wrap">
                                        <span class="score-bar-fill" style="width: {{ min(($q['average'] / 5) * 100, 100) }}%;"></span>
                                    </span>
                                @else
                                    —
                                @endif
                            </span>
                        </div>
                    @endforeach

                    @if($teacher['overall_average'] !== null)
                        <div class="overall-row">
                            <span>Promedio General</span>
                            <span class="{{ $teacher['overall_average'] >= 4 ? 'score-high' : ($teacher['overall_average'] >= 3 ? 'score-mid' : 'score-low') }}">
                                {{ $teacher['overall_average'] }}/5.0
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Preguntas abiertas --}}
            @if($textQs->isNotEmpty())
                <div class="questions-open">
                    <div class="sub-title">Preguntas abiertas</div>
                    @foreach($textQs as $q)
                        <div class="open-question-block">
                            <div class="open-question-text">{{ $q['question'] }}</div>
                            <div class="open-responses">
                                @forelse($q['responses'] as $resp)
                                    <div class="open-response">{{ $resp }}</div>
                                @empty
                                    <div class="no-responses">Sin respuestas abiertas.</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

        </div>
    </div>
@empty
    <p style="color:#9ca3af; font-style:italic;">No hay evaluaciones registradas para este ciclo.</p>
@endforelse

{{-- ══════════════════════════════════════════════════════ --}}
{{-- SECCIÓN 2 — DESGLOSE POR GRUPO                       --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="page-break"></div>
<div class="section-title">SECCIÓN 2 — DESGLOSE POR GRUPO</div>

@forelse($groupDetails as $group)
    <div class="group-header">
        <div class="group-code">Grupo: {{ $group['group_code'] }}</div>
    </div>

    @foreach($group['assignments'] as $assignment)
        <div class="teacher-card">
            <div class="teacher-card-header">
                <div class="teacher-name">{{ $assignment['teacher_name'] }}</div>
                <div class="teacher-meta">
                    Materia: <strong>{{ $assignment['subject_name'] }}</strong>
                    &nbsp;|&nbsp; Evaluaciones: {{ $assignment['total_responses'] }}
                    @if($assignment['overall_average'] !== null)
                        &nbsp;|&nbsp; Promedio:
                        <strong class="{{ $assignment['overall_average'] >= 4 ? 'score-high' : ($assignment['overall_average'] >= 3 ? 'score-mid' : 'score-low') }}">
                            {{ $assignment['overall_average'] }}/5.0
                        </strong>
                    @endif
                </div>
            </div>
            <div class="teacher-card-body">

                @php
                    $numericQs = collect($assignment['question_stats'])->where('type', '!=', 'text');
                    $textQs    = collect($assignment['question_stats'])->where('type', 'text');
                @endphp

                @if($numericQs->isNotEmpty())
                    <div class="questions-numeric">
                        <div class="sub-title">Preguntas de escala / opción</div>
                        @foreach($numericQs as $q)
                            <div class="q-row">
                                <span class="q-text">{{ $q['question'] }}</span>
                                <span class="q-score">
                                    @if($q['average'] !== null)
                                        {{ $q['average'] }}/5.0
                                        <span class="score-bar-wrap">
                                            <span class="score-bar-fill" style="width: {{ min(($q['average'] / 5) * 100, 100) }}%;"></span>
                                        </span>
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        @endforeach

                        @if($assignment['overall_average'] !== null)
                            <div class="overall-row">
                                <span>Promedio del Grupo</span>
                                <span class="{{ $assignment['overall_average'] >= 4 ? 'score-high' : ($assignment['overall_average'] >= 3 ? 'score-mid' : 'score-low') }}">
                                    {{ $assignment['overall_average'] }}/5.0
                                </span>
                            </div>
                        @endif
                    </div>
                @endif

                @if($textQs->isNotEmpty())
                    <div class="questions-open">
                        <div class="sub-title">Preguntas abiertas</div>
                        @foreach($textQs as $q)
                            <div class="open-question-block">
                                <div class="open-question-text">{{ $q['question'] }}</div>
                                <div class="open-responses">
                                    @forelse($q['responses'] as $resp)
                                        <div class="open-response">{{ $resp }}</div>
                                    @empty
                                        <div class="no-responses">Sin respuestas abiertas.</div>
                                    @endforelse
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    @endforeach

@empty
    <p style="color:#9ca3af; font-style:italic;">No hay grupos con evaluaciones en este ciclo.</p>
@endforelse

<div class="footer">
    {{ $survey->name }} &mdash; {{ $cycle->name }} &mdash; Reporte generado el {{ now()->format('d/m/Y \a \l\a\s H:i') }}
</div>

</body>
</html>
