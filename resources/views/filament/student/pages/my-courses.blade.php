<x-filament-panels::page>
    @php($selectedEnrollment = $this->selectedEnrollment())
    @php($selectedLesson = $this->selectedLesson())

    <style>
        .student-lms { display: grid; gap: 18px; }
        .student-lms-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .student-lms-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #ffffff 0%, #eef8f2 100%); }
        .student-lms-kicker { color: #008f43; font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .student-lms-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .student-lms-text { margin: 0; color: #536a5b; }
        .student-lms-stats { display: grid; grid-template-columns: repeat(4, minmax(92px, 1fr)); gap: 10px; }
        .student-lms-stat { padding: 14px; border-radius: 12px; background: #f8fafc; }
        .student-lms-stat strong { display: block; color: #071527; font-size: 22px; }
        .student-lms-stat span { color: #64748b; font-size: 12px; font-weight: 700; }
        .student-lms-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .student-course { display: grid; gap: 14px; }
        .student-course-head { display: flex; justify-content: space-between; gap: 12px; }
        .student-course h3, .room-head h2, .lesson-main h3, .side-panel h3 { margin: 0; color: #071527; font-weight: 900; }
        .student-badge { display: inline-flex; align-items: center; height: 26px; padding: 0 10px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 800; }
        .student-progress-label { display: flex; justify-content: space-between; color: #334155; font-size: 13px; font-weight: 800; }
        .student-progress { height: 10px; overflow: hidden; border-radius: 999px; background: #e2e8f0; }
        .student-progress div { height: 100%; border-radius: inherit; background: linear-gradient(90deg, #008f43, #00a85a); }
        .student-mini-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .student-mini { padding: 12px; border-radius: 12px; background: #f8fafc; }
        .student-mini strong, .student-mini span { display: block; }
        .student-mini span { color: #64748b; font-size: 12px; font-weight: 700; }
        .student-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .student-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 800; text-decoration: none; cursor: pointer; }
        .student-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .student-btn.success { border-color: #008f43; background: #eef8f2; color: #007a3a; }
        .student-btn:disabled { cursor: not-allowed; opacity: .55; }
        .room-head { display: grid; grid-template-columns: auto minmax(0, 1fr) auto; gap: 16px; align-items: center; }
        .room-grid { display: grid; grid-template-columns: 280px minmax(0, 1fr) 300px; gap: 16px; align-items: start; }
        .playlist, .side-panel { position: sticky; top: 18px; display: grid; gap: 12px; }
        .module-block { display: grid; gap: 8px; }
        .module-block > strong { color: #071527; font-size: 14px; }
        .lesson-row { width: 100%; display: grid; grid-template-columns: 24px 1fr auto; gap: 8px; align-items: center; min-height: 44px; padding: 10px; border: 1px solid #e2e8f0; border-radius: 10px; background: #f8fafc; color: #334155; text-align: left; cursor: pointer; }
        .lesson-row.active { border-color: #008f43; background: #eef8f2; }
        .lesson-row.done { color: #007a3a; }
        .lesson-row span { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-weight: 800; }
        .lesson-row small { color: #64748b; font-weight: 800; }
        .lesson-player { min-height: 280px; display: grid; place-items: center; gap: 12px; padding: 24px; border-radius: 14px; background: #0f172a; color: #fff; text-align: center; }
        .lesson-player iframe, .lesson-player video { width: 100%; aspect-ratio: 16 / 9; border: 0; border-radius: 10px; background: #020617; }
        .lesson-main { display: grid; grid-template-columns: minmax(0, 1fr) 240px; gap: 16px; margin-top: 16px; }
        .lesson-progress-box, .material-link, .exam-item, .certificate-link { border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; padding: 12px; }
        .material-list, .exam-list, .certificate-list { display: grid; gap: 8px; }
        .material-link, .certificate-link { display: grid; grid-template-columns: 1fr auto; gap: 8px; color: #334155; text-decoration: none; }
        .exam-item { display: grid; gap: 8px; }
        .empty-room { padding: 36px; text-align: center; color: #64748b; }
        @media (max-width: 1180px) { .room-grid { grid-template-columns: 1fr; } .playlist, .side-panel { position: static; } }
        @media (max-width: 760px) { .student-lms-hero, .room-head, .lesson-main { grid-template-columns: 1fr; } .student-lms-grid, .student-lms-stats, .student-mini-grid { grid-template-columns: 1fr; } }
    </style>

    <div class="student-lms">
        @if ($selectedEnrollment)
            @php($status = $this->academicStatus($selectedEnrollment))
            <section class="student-lms-card room-head">
                <button type="button" class="student-btn" wire:click="closeEnrollment">Voltar</button>
                <div>
                    <span class="student-lms-kicker">{{ $selectedEnrollment->course?->category?->name ?? 'Curso' }}</span>
                    <h2>{{ $selectedEnrollment->course?->name }}</h2>
                    <p class="student-lms-text">{{ $selectedEnrollment->course?->short_description }}</p>
                </div>
                <button type="button" class="student-btn primary" wire:click="$refresh">Atualizar</button>
            </section>

            <div class="room-grid">
                <aside class="student-lms-card playlist">
                    @foreach ($selectedEnrollment->course->modules as $module)
                        <div class="module-block">
                            <strong>{{ $module->title }}</strong>
                            @foreach ($module->lessons as $lesson)
                                @php($progress = $lesson->progress->first())
                                <button type="button" class="lesson-row {{ $selectedLesson?->id === $lesson->id ? 'active' : '' }} {{ $progress?->is_completed ? 'done' : '' }}" wire:click="selectLesson({{ $lesson->id }})">
                                    <span>{{ $progress?->is_completed ? '✓' : '▶' }}</span>
                                    <span>{{ $lesson->title }}</span>
                                    <small>{{ $progress?->progress_percent ?? 0 }}%</small>
                                </button>
                            @endforeach
                        </div>
                    @endforeach
                </aside>

                <main class="student-lms-card">
                    @if ($selectedLesson)
                        @php($lessonProgress = $selectedLesson->progress->first())
                        <div class="lesson-player">
                            @if (in_array($selectedLesson->content_type, ['youtube', 'vimeo']) && $selectedLesson->content_url)
                                <iframe src="{{ $selectedLesson->content_url }}" title="Player da aula" allowfullscreen></iframe>
                            @elseif ($selectedLesson->content_type === 'mp4' && ($selectedLesson->content_url || $selectedLesson->file_path))
                                <video controls src="{{ $selectedLesson->content_url ?: Storage::disk('public')->url($selectedLesson->file_path) }}"></video>
                            @else
                                <strong>{{ $selectedLesson->content_type === 'pdf' ? 'Material em PDF' : 'Conteúdo da aula' }}</strong>
                                @if ($selectedLesson->content_url)
                                    <a class="student-btn primary" href="{{ $selectedLesson->content_url }}" target="_blank" rel="noopener">Abrir conteúdo</a>
                                @elseif ($selectedLesson->file_path)
                                    <a class="student-btn primary" href="{{ Storage::disk('public')->url($selectedLesson->file_path) }}" target="_blank" rel="noopener">Abrir arquivo</a>
                                @endif
                            @endif
                        </div>

                        <div class="lesson-main">
                            <div>
                                <span class="student-lms-kicker">{{ $selectedLesson->module?->title }}</span>
                                <h3>{{ $selectedLesson->title }}</h3>
                                <p class="student-lms-text">{{ $selectedLesson->description ?: 'Assista ao conteúdo, consulte os materiais e registre a conclusão da aula.' }}</p>
                            </div>
                            <div class="lesson-progress-box">
                                <div class="student-progress-label">
                                    <span>Progresso</span>
                                    <strong>{{ $lessonProgress?->progress_percent ?? 0 }}%</strong>
                                </div>
                                <div class="student-progress" style="margin: 8px 0 12px;">
                                    <div style="width: {{ $lessonProgress?->progress_percent ?? 0 }}%"></div>
                                </div>
                                <button type="button" class="student-btn primary" style="width: 100%;" wire:click="completeLesson({{ $selectedLesson->id }})" @disabled($lessonProgress?->is_completed)>
                                    {{ $lessonProgress?->is_completed ? 'Aula concluída' : 'Marcar como concluída' }}
                                </button>
                            </div>
                        </div>

                        <section style="margin-top: 18px;">
                            <h3 style="margin: 0 0 10px; color: #071527; font-weight: 900;">Materiais da aula</h3>
                            <div class="material-list">
                                @forelse ($selectedLesson->materials as $material)
                                    <a class="material-link" href="{{ Storage::disk('public')->url($material->file_path) }}" target="_blank" rel="noopener">
                                        <span>{{ $material->title }}</span>
                                        <small>{{ $material->mime_type ?: 'arquivo' }}</small>
                                    </a>
                                @empty
                                    <p class="student-lms-text">Nenhum material complementar cadastrado para esta aula.</p>
                                @endforelse
                            </div>
                        </section>
                    @else
                        <div class="empty-room">Este curso ainda não possui aulas disponíveis.</div>
                    @endif
                </main>

                <aside class="side-panel">
                    <section class="student-lms-card">
                        <h3>Avaliações</h3>
                        <div class="exam-list" style="margin-top: 10px;">
                            @forelse ($selectedEnrollment->course->exams as $exam)
                                @php($attempt = $exam->attempts->first())
                                <div class="exam-item">
                                    <strong>{{ $exam->title }}</strong>
                                    <span class="student-lms-text">Nota: {{ $attempt?->grade ?? 'pendente' }} | {{ $attempt?->status ?? 'não iniciada' }}</span>
                                    <a class="student-btn" href="/aluno/comunicacao">Tirar dúvida</a>
                                </div>
                            @empty
                                <p class="student-lms-text">Nenhuma avaliação cadastrada para este curso.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="student-lms-card">
                        <h3>Certificados</h3>
                        <div class="certificate-list" style="margin-top: 10px;">
                            @forelse ($selectedEnrollment->course->certificates as $certificate)
                                <a class="certificate-link" href="/certificado/{{ $certificate->code }}" target="_blank" rel="noopener">
                                    <span>{{ $certificate->code }}</span>
                                    <small>{{ match ($certificate->status) {
                                        'valid' => 'Válido',
                                        'revoked' => 'Revogado',
                                        default => $certificate->status,
                                    } }}</small>
                                </a>
                            @empty
                                <p class="student-lms-text">Conclua os requisitos para emitir seu certificado.</p>
                            @endforelse
                        </div>
                        <button type="button" class="student-btn success" style="width: 100%; margin-top: 10px;" wire:click="issueCertificate({{ $selectedEnrollment->course_id }})" @disabled(! $status['eligible'])>
                            Emitir certificado
                        </button>
                    </section>
                </aside>
            </div>
        @else
            @php($summary = $this->journeySummary())

            <section class="student-lms-card student-lms-hero">
                <div>
                    <span class="student-lms-kicker">Minha jornada</span>
                    <h2 class="student-lms-title">Continue de onde parou</h2>
                    <div class="student-actions" style="margin-top: 14px;">
                        <a class="student-btn primary" href="/cursos">Ver todos os cursos</a>
                    </div>
                    <p class="student-lms-text">Seus cursos, pendências e certificados ficam organizados em um só lugar para facilitar a conclusão.</p>
                </div>
                <div class="student-lms-stats">
                    <div class="student-lms-stat"><strong>{{ $summary['active'] }}</strong><span>Ativos</span></div>
                    <div class="student-lms-stat"><strong>{{ $summary['completed'] }}</strong><span>Concluídos</span></div>
                    <div class="student-lms-stat"><strong>{{ $summary['average_progress'] }}%</strong><span>Média</span></div>
                    <div class="student-lms-stat"><strong>{{ $summary['certificates_available'] }}</strong><span>Certificados</span></div>
                </div>
            </section>

            @if ($summary['next'])
                <section class="student-lms-card" style="display: flex; justify-content: space-between; gap: 14px; align-items: center;">
                    <div>
                        <span class="student-lms-kicker">Próximo passo sugerido</span>
                        <p class="student-lms-text">
                            {{ $summary['next']['enrollment']->course?->name }}:
                            {{ $summary['next']['status']['next_lesson']?->title ?? 'revise as pendências do curso' }}.
                        </p>
                    </div>
                    <button type="button" class="student-btn primary" wire:click="openEnrollment({{ $summary['next']['enrollment']->id }})">Continuar agora</button>
                </section>
            @endif

            <div class="student-lms-grid">
                @forelse ($this->getEnrollments() as $enrollment)
                    @php($status = $this->academicStatus($enrollment))
                    <article class="student-lms-card student-course">
                        <div class="student-course-head">
                            <div>
                                <span class="student-lms-kicker">{{ $enrollment->course?->category?->name ?? 'Curso' }}</span>
                                <h3>{{ $enrollment->course?->name }}</h3>
                            </div>
                            <span class="student-badge">{{ match ($enrollment->status) {
                                'active' => 'Ativa',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                                'waiting' => 'Lista de espera',
                                default => $enrollment->status,
                            } }}</span>
                        </div>
                        <p class="student-lms-text">{{ $enrollment->course?->short_description }}</p>
                        <div>
                            <div class="student-progress-label">
                                <span>Progresso mínimo: {{ $status['required_progress_percent'] }}%</span>
                                <strong>{{ $status['progress_percent'] }}%</strong>
                            </div>
                            <div class="student-progress" style="margin-top: 8px;"><div style="width: {{ $status['progress_percent'] }}%"></div></div>
                        </div>
                        <div class="student-mini-grid">
                            <div class="student-mini"><strong>{{ $status['completed_required_lessons'] }}/{{ $status['required_lessons'] }}</strong><span>Aulas</span></div>
                            <div class="student-mini"><strong>{{ $status['final_grade'] ?? 'Pendente' }}</strong><span>Nota mínima {{ $status['minimum_grade'] }}</span></div>
                            <div class="student-mini"><strong>{{ $status['eligible'] ? 'Disponível' : 'Em curso' }}</strong><span>Certificado</span></div>
                        </div>
                        @if ($status['next_lesson'])
                            <div class="student-mini">
                                <span>Próxima aula</span>
                                <strong>{{ $status['next_lesson']->module?->title }} - {{ $status['next_lesson']->title }}</strong>
                            </div>
                        @endif
                        <div class="student-actions">
                            <button type="button" class="student-btn primary" wire:click="openEnrollment({{ $enrollment->id }})">Continuar</button>
                            <a class="student-btn" href="/aluno/forum">Fórum</a>
                            <a class="student-btn" href="/cursos">Catálogo</a>
                            @if ($status['eligible'])
                                <button type="button" class="student-btn success" wire:click="issueCertificate({{ $enrollment->course_id }})">Certificado</button>
                            @endif
                        </div>
                    </article>
                @empty
                    <section class="student-lms-card" style="text-align: center;">
                        <h2 class="student-lms-title">Você ainda não possui matrículas</h2>
                        <p class="student-lms-text">Escolha um curso publicado no catálogo para começar.</p>
                        <a class="student-btn primary" style="margin-top: 14px;" href="/cursos">Ver catálogo</a>
                    </section>
                @endforelse
            </div>
        @endif
    </div>
</x-filament-panels::page>
