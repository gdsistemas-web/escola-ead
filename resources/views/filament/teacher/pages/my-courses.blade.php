<x-filament-panels::page>
    @php($selectedCourse = $this->selectedCourse())

    <style>
        .teacher-lms { display: grid; gap: 18px; }
        .teacher-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .teacher-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #fff 0%, #eef8f2 100%); }
        .teacher-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .teacher-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .teacher-text { margin: 0; color: #536a5b; }
        .teacher-stats, .teacher-mini-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .teacher-stat, .teacher-mini { padding: 14px; border-radius: 12px; background: #f8fafc; }
        .teacher-stat strong, .teacher-mini strong { display: block; color: #071527; font-size: 22px; }
        .teacher-stat span, .teacher-mini span { color: #64748b; font-size: 12px; font-weight: 800; }
        .teacher-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
        .teacher-course { display: grid; gap: 14px; }
        .teacher-course-head, .teacher-room-head { display: flex; justify-content: space-between; gap: 14px; align-items: flex-start; }
        .teacher-course h3, .teacher-room-head h2, .teacher-section-title { margin: 0; color: #071527; font-weight: 900; }
        .teacher-badge { display: inline-flex; align-items: center; min-height: 26px; padding: 0 10px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 900; }
        .teacher-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .teacher-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .teacher-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .teacher-modal-backdrop { position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, .54); backdrop-filter: blur(5px); }
        .teacher-modal { position: fixed; top: 96px; right: 24px; z-index: 90; width: min(520px, calc(100vw - 48px)); overflow: auto; border: 1px solid #dbe3ee; border-radius: 14px; background: #fff; padding: 22px; box-shadow: 0 28px 80px rgba(15, 23, 42, .28); }
        .teacher-form { display: grid; gap: 14px; }
        .teacher-field { display: grid; gap: 6px; }
        .teacher-field label { color: #334155; font-size: 13px; font-weight: 900; }
        .teacher-field input, .teacher-field select { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; color: #071527; }
        .teacher-error { color: #b91c1c; font-size: 12px; font-weight: 800; }
        .teacher-detail-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(300px, .75fr); gap: 16px; align-items: start; }
        .teacher-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; }
        .teacher-table th { color: #64748b; font-size: 12px; text-align: left; text-transform: uppercase; }
        .teacher-table td { padding: 12px; background: #f8fafc; color: #334155; }
        .teacher-table td:first-child { border-radius: 10px 0 0 10px; font-weight: 900; color: #071527; }
        .teacher-table td:last-child { border-radius: 0 10px 10px 0; }
        .teacher-list { display: grid; gap: 10px; }
        .teacher-list-item { padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        @media (max-width: 1040px) { .teacher-grid, .teacher-detail-grid, .teacher-hero { grid-template-columns: 1fr; } }
        @media (max-width: 720px) { .teacher-stats, .teacher-mini-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .teacher-room-head { display: grid; } }
    </style>

    <div class="teacher-lms">
        @if ($selectedCourse)
            <section class="teacher-card teacher-room-head">
                <div>
                    <span class="teacher-kicker">{{ $selectedCourse->category?->name ?? 'Curso' }}</span>
                    <h2>{{ $selectedCourse->name }}</h2>
                    <p class="teacher-text">{{ $selectedCourse->short_description }}</p>
                </div>
                <div class="teacher-actions">
                    <button type="button" class="teacher-btn" wire:click="closeCourse">Voltar</button>
                    <a class="teacher-btn primary" href="/professor/forum">Fórum</a>
                    <a class="teacher-btn" href="/professor/autoria-cursos">Editar/autoria</a>
                </div>
            </section>

            <section class="teacher-card">
                <div class="teacher-mini-grid">
                    <div class="teacher-mini"><strong>{{ $selectedCourse->modules_count }}</strong><span>Módulos</span></div>
                    <div class="teacher-mini"><strong>{{ $selectedCourse->lessons_count }}</strong><span>Aulas</span></div>
                    <div class="teacher-mini"><strong>{{ $selectedCourse->enrollments_count }}</strong><span>Alunos</span></div>
                    <div class="teacher-mini"><strong>{{ $selectedCourse->certificates_count }}</strong><span>Certificados</span></div>
                </div>
            </section>

            <div class="teacher-detail-grid">
                <section class="teacher-card">
                    <h3 class="teacher-section-title">Alunos matriculados</h3>
                    <table class="teacher-table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Situação</th>
                                <th>Progresso</th>
                                <th>Nota</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selectedCourse->enrollments as $enrollment)
                                <tr>
                                    <td>{{ $enrollment->user?->name }}</td>
                                    <td>{{ match ($enrollment->status) {
                                        'active' => 'Ativa',
                                        'completed' => 'Concluída',
                                        'cancelled' => 'Cancelada',
                                        'waiting' => 'Lista de espera',
                                        default => $enrollment->status,
                                    } }}</td>
                                    <td>{{ $enrollment->progress_percent }}%</td>
                                    <td>{{ $enrollment->final_grade ?? 'Pendente' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4">Nenhum aluno matriculado.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                <aside class="teacher-lms">
                    <section class="teacher-card">
                        <h3 class="teacher-section-title">Avaliações</h3>
                        <div class="teacher-list" style="margin-top: 12px;">
                            @forelse ($selectedCourse->exams as $exam)
                                <div class="teacher-list-item">
                                    <strong>{{ $exam->title }}</strong>
                                    <p class="teacher-text">{{ $exam->attempts->count() }} tentativa(s) enviada(s)</p>
                                </div>
                            @empty
                                <p class="teacher-text">Nenhuma avaliação cadastrada.</p>
                            @endforelse
                        </div>
                    </section>

                    <section class="teacher-card">
                        <h3 class="teacher-section-title">Dúvidas recentes</h3>
                        <div class="teacher-list" style="margin-top: 12px;">
                            @forelse ($selectedCourse->forumTopics->take(5) as $topic)
                                <div class="teacher-list-item">
                                    <strong>{{ $topic->title }}</strong>
                                    <p class="teacher-text">{{ $topic->visibleReplies->count() }} resposta(s)</p>
                                </div>
                            @empty
                                <p class="teacher-text">Nenhuma dúvida registrada.</p>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </div>
        @else
            @php($summary = $this->summary())

            <section class="teacher-card teacher-hero">
                <div>
                    <span class="teacher-kicker">Painel do professor</span>
                    <h2 class="teacher-title">Acompanhe seus cursos</h2>
                    <div class="teacher-actions" style="margin-top: 14px;">
                        <button type="button" class="teacher-btn primary" wire:click="openScormImport">Importar SCORM</button>
                        <a class="teacher-btn" href="/professor/autoria-cursos">Novo curso</a>
                    </div>
                    <p class="teacher-text">Veja turmas, progresso, dúvidas e alertas pedagógicos em uma visão de trabalho.</p>
                </div>
                <div class="teacher-stats">
                    <div class="teacher-stat"><strong>{{ $summary['courses'] }}</strong><span>Cursos</span></div>
                    <div class="teacher-stat"><strong>{{ $summary['students'] }}</strong><span>Alunos</span></div>
                    <div class="teacher-stat"><strong>{{ $summary['topics'] }}</strong><span>Tópicos</span></div>
                    <div class="teacher-stat"><strong>{{ $summary['certificates'] }}</strong><span>Certificados</span></div>
                </div>
            </section>

            <div class="teacher-grid">
                @forelse ($this->getCourses() as $course)
                    <article class="teacher-card teacher-course">
                        <div class="teacher-course-head">
                            <div>
                                <span class="teacher-kicker">{{ $course->category?->name ?? 'Curso' }}</span>
                                <h3>{{ $course->name }}</h3>
                            </div>
                            <span class="teacher-badge">{{ match ($course->status) {
                                'draft' => 'Rascunho',
                                'pending_review' => 'Em revisão',
                                'changes_requested' => 'Ajustes solicitados',
                                'published' => 'Publicado',
                                'closed' => 'Encerrado',
                                default => $course->status,
                            } }}</span>
                        </div>
                        <p class="teacher-text">{{ $course->short_description }}</p>
                        <div class="teacher-mini-grid">
                            <div class="teacher-mini"><strong>{{ $course->modules_count }}</strong><span>Módulos</span></div>
                            <div class="teacher-mini"><strong>{{ $course->enrollments_count }}</strong><span>Alunos</span></div>
                            <div class="teacher-mini"><strong>{{ $this->studentsAtRisk($course) }}</strong><span>Risco</span></div>
                            <div class="teacher-mini"><strong>{{ $this->unansweredTopics($course) }}</strong><span>Sem resposta</span></div>
                        </div>
                        <div class="teacher-actions">
                            <button type="button" class="teacher-btn primary" wire:click="openCourse({{ $course->id }})">Ver curso</button>
                            <a class="teacher-btn" href="/professor/forum">Fórum</a>
                            <a class="teacher-btn" href="/professor/comunicacao">Chats</a>
                            <a class="teacher-btn" href="/professor/autoria-cursos">Autoria</a>
                        </div>
                    </article>
                @empty
                    <section class="teacher-card" style="text-align: center;">
                        <h2 class="teacher-title">Nenhum curso encontrado</h2>
                        <p class="teacher-text">Os cursos atribuídos ao seu perfil aparecerão aqui.</p>
                        <div class="teacher-actions" style="justify-content: center; margin-top: 14px;">
                            <button type="button" class="teacher-btn primary" wire:click="openScormImport">Importar SCORM</button>
                        </div>
                    </section>
                @endforelse
            </div>
        @endif

        @if ($showScormImport)
            <div class="teacher-modal-backdrop" wire:click="closeScormImport"></div>
            <section class="teacher-modal" role="dialog" aria-modal="true" aria-label="Importar pacote SCORM">
                <form class="teacher-form" wire:submit="importScorm">
                    <div>
                        <span class="teacher-kicker">Importacao automatica</span>
                        <h2 class="teacher-title" style="font-size: 22px;">Importar pacote SCORM</h2>
                        <p class="teacher-text">O pacote sera lido pelo manifesto e criado como curso em rascunho.</p>
                    </div>

                    <div class="teacher-field">
                        <label>Categoria</label>
                        <select wire:model.defer="scormForm.category_id">
                            <option value="">Selecione</option>
                            @foreach ($this->categories() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('scormForm.category_id') <span class="teacher-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="teacher-field">
                        <label>Nome do curso</label>
                        <input wire:model.defer="scormForm.course_name" placeholder="Deixe vazio para usar o titulo do manifesto">
                        @error('scormForm.course_name') <span class="teacher-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="teacher-field">
                        <label>Pacote SCORM (.zip)</label>
                        <input type="file" wire:model="scormFile" accept=".zip,application/zip">
                        @error('scormFile') <span class="teacher-error">{{ $message }}</span> @enderror
                    </div>

                    <div wire:loading wire:target="scormFile,importScorm" class="teacher-text">Processando pacote...</div>

                    <div class="teacher-actions" style="justify-content: flex-end;">
                        <button type="button" class="teacher-btn" wire:click="closeScormImport">Cancelar</button>
                        <button type="submit" class="teacher-btn primary">Importar pacote</button>
                    </div>
                </form>
            </section>
        @endif
    </div>
</x-filament-panels::page>
