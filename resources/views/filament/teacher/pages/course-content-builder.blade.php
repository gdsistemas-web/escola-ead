<x-filament-panels::page>
    @php($course = $this->selectedCourse())

    <style>
        .builder { display: grid; gap: 18px; }
        .builder-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .builder-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #fff 0%, #eef8f2 100%); }
        .builder-kicker { display: block; color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .builder-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .builder-text { margin: 0; color: #536a5b; }
        .course-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }
        .course-open-card { width: 100%; display: grid; gap: 12px; min-height: 190px; padding: 18px; border: 1px solid #dbe3ee; border-radius: 14px; background: #fff; color: #334155; text-align: left; cursor: pointer; box-shadow: 0 12px 28px rgba(15, 23, 42, .05); }
        .course-open-card:hover, .course-open-card.active { border-color: rgba(0, 143, 67, .5); background: #fbfdfb; transform: translateY(-1px); }
        .course-open-card strong { display: block; margin-top: 4px; color: #071527; font-size: 18px; font-weight: 900; }
        .course-metrics { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
        .metric { padding: 10px; border-radius: 10px; background: #f8fafc; }
        .metric b, .metric span { display: block; }
        .metric b { color: #071527; font-size: 18px; }
        .metric span { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; }
        .badge { display: inline-flex; width: fit-content; min-height: 24px; align-items: center; padding: 0 9px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 900; }
        .badge.warn { background: #fffbeb; color: #92400e; }
        .builder-modal-backdrop { position: fixed; inset: 0; z-index: 80; background: rgba(15, 23, 42, .54); backdrop-filter: blur(5px); }
        .builder-modal { position: fixed; inset: 26px; z-index: 90; display: grid; grid-template-rows: auto 1fr; overflow: hidden; border: 1px solid #dbe3ee; border-radius: 16px; background: #f8fafc; box-shadow: 0 30px 90px rgba(15, 23, 42, .28); }
        .builder-modal-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; padding: 20px 22px; border-bottom: 1px solid #dbe3ee; background: #fff; }
        .builder-modal-body { overflow: auto; padding: 18px; }
        .content-layout { display: grid; grid-template-columns: minmax(0, 1fr) 430px; gap: 16px; align-items: start; }
        .section-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 18px; }
        .stack, .form-stack { display: grid; gap: 10px; }
        .module-box, .exam-box { padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        .lesson-box { margin-top: 8px; padding: 12px; border-radius: 10px; background: #fff; border: 1px solid #e2e8f0; }
        .material-pill { display: inline-flex; margin: 6px 6px 0 0; padding: 5px 8px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 800; }
        .section-title { margin: 0; color: #071527; font-weight: 900; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .field { display: grid; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        .field label { color: #334155; font-size: 13px; font-weight: 900; }
        .field input, .field select, .field textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; color: #071527; }
        .field textarea { min-height: 82px; resize: vertical; }
        .check-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .builder-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; cursor: pointer; text-decoration: none; }
        .builder-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .builder-btn.icon { width: 40px; padding: 0; font-size: 20px; }
        .builder-btn:disabled { opacity: .55; cursor: not-allowed; }
        .locked-note { padding: 12px; border-radius: 12px; background: #fffbeb; color: #92400e; font-size: 13px; font-weight: 800; }
        @media (max-width: 1180px) { .course-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .content-layout { grid-template-columns: 1fr; } .builder-modal { inset: 14px; } }
        @media (max-width: 760px) { .course-grid, .builder-hero, .form-grid { grid-template-columns: 1fr; } .builder-modal { inset: 8px; } .field.full { grid-column: auto; } }
    </style>

    <div class="builder">
        <section class="builder-card builder-hero">
            <div>
                <span class="builder-kicker">Autoria pedagógica</span>
                <h2 class="builder-title">Monte módulos, aulas, materiais e provas</h2>
                <p class="builder-text">Clique no nome de um curso para abrir o construtor em modal.</p>
            </div>
            <a class="builder-btn" href="/professor/autoria-cursos">Voltar para autoria</a>
        </section>

        <section class="course-grid">
            @forelse ($this->getCourses() as $item)
                <button type="button" class="course-open-card {{ $selectedCourseId === $item->id ? 'active' : '' }}" wire:click="openCourse({{ $item->id }})">
                    <div>
                        <span class="builder-kicker">{{ $item->status === 'published' ? 'Publicado' : 'Autoria' }}</span>
                        <strong>{{ $item->name }}</strong>
                    </div>
                    <div class="course-metrics">
                        <div class="metric"><b>{{ $item->modules_count }}</b><span>Módulos</span></div>
                        <div class="metric"><b>{{ $item->lessons_count }}</b><span>Aulas</span></div>
                        <div class="metric"><b>{{ $item->exams_count }}</b><span>Provas</span></div>
                    </div>
                    <span @class(['badge', 'warn' => in_array($item->status, ['draft', 'changes_requested'])])>
                        {{ match ($item->status) {
                            'pending_review' => 'Em revisão',
                            'changes_requested' => 'Ajustes solicitados',
                            'published' => 'Publicado',
                            'closed' => 'Encerrado',
                            default => 'Rascunho',
                        } }}
                    </span>
                </button>
            @empty
                <section class="builder-card">
                    <h2 class="builder-title">Nenhum curso encontrado</h2>
                    <p class="builder-text">Crie um rascunho em Autoria de cursos antes de montar conteúdos.</p>
                </section>
            @endforelse
        </section>

        @if ($course)
            @php($editable = in_array($course->status, ['draft', 'changes_requested'], true))
            <div class="builder-modal-backdrop" wire:click="$set('selectedCourseId', null)"></div>
            <section class="builder-modal" role="dialog" aria-modal="true" aria-label="Construtor de conteúdo">
                <header class="builder-modal-head">
                    <div>
                        <span class="builder-kicker">{{ $course->category?->name ?? 'Curso' }}</span>
                        <h2 class="builder-title">{{ $course->name }}</h2>
                        <p class="builder-text">{{ $editable ? 'Você pode editar conteúdos deste curso.' : 'Este curso está bloqueado para edição neste status.' }}</p>
                    </div>
                    <button type="button" class="builder-btn icon" aria-label="Fechar modal" wire:click="$set('selectedCourseId', null)">×</button>
                </header>

                <div class="builder-modal-body">
                    @unless ($editable)
                        <div class="locked-note" style="margin-bottom: 14px;">Para editar, o curso precisa estar como rascunho ou ajustes solicitados. Cursos em revisão/publicados ficam protegidos.</div>
                    @endunless

                    <main class="content-layout">
                        <section class="stack">
                            <div class="section-card">
                                <h3 class="section-title">Trilha do curso</h3>
                                <div class="stack" style="margin-top: 12px;">
                                    @forelse ($course->modules as $module)
                                        <article class="module-box">
                                            <strong>{{ $module->position }}. {{ $module->title }}</strong>
                                            <p class="builder-text">{{ $module->lessons->count() }} aula(s)</p>
                                            @foreach ($module->lessons as $lesson)
                                                <div class="lesson-box">
                                                    <strong>{{ $lesson->position }}. {{ $lesson->title }}</strong>
                                                    <p class="builder-text">{{ $lesson->content_type }} | {{ $lesson->duration_minutes }} min | {{ $lesson->is_required ? 'obrigatória' : 'opcional' }}</p>
                                                    @foreach ($lesson->materials as $material)
                                                        <span class="material-pill">{{ $material->title }}</span>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                        </article>
                                    @empty
                                        <p class="builder-text">Nenhum módulo criado.</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="section-card">
                                <h3 class="section-title">Avaliações</h3>
                                <div class="stack" style="margin-top: 12px;">
                                    @forelse ($course->exams as $exam)
                                        <article class="exam-box">
                                            <strong>{{ $exam->title }}</strong>
                                            <p class="builder-text">Nota mínima {{ $exam->minimum_grade }} | {{ $exam->questions->count() }} questão(ões)</p>
                                            @foreach ($exam->questions as $question)
                                                <div class="lesson-box">
                                                    <strong>{{ $question->statement }}</strong>
                                                    <p class="builder-text">{{ $question->type }} | peso {{ $question->weight }}</p>
                                                </div>
                                            @endforeach
                                        </article>
                                    @empty
                                        <p class="builder-text">Nenhuma prova criada.</p>
                                    @endforelse
                                </div>
                            </div>
                        </section>

                        <aside class="form-stack">
                            <section class="section-card">
                                <h3 class="section-title">1. Módulo</h3>
                                <form class="form-stack" wire:submit="createModule">
                                    <div class="field"><label>Título</label><input wire:model.defer="moduleForm.title" @disabled(! $editable)></div>
                                    <div class="field"><label>Descrição</label><textarea wire:model.defer="moduleForm.description" @disabled(! $editable)></textarea></div>
                                    <div class="field"><label>Posição</label><input type="number" wire:model.defer="moduleForm.position" @disabled(! $editable)></div>
                                    <button type="submit" class="builder-btn primary" @disabled(! $editable)>Criar módulo</button>
                                </form>
                            </section>

                            <section class="section-card">
                                <h3 class="section-title">2. Aula</h3>
                                <form class="form-stack" wire:submit="createLesson">
                                    <div class="field">
                                        <label>Módulo</label>
                                        <select wire:model.defer="lessonForm.course_module_id" @disabled(! $editable)>
                                            <option value="">Selecione</option>
                                            @foreach ($course->modules as $module)
                                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field"><label>Título</label><input wire:model.defer="lessonForm.title" @disabled(! $editable)></div>
                                    <div class="field"><label>Descrição</label><textarea wire:model.defer="lessonForm.description" @disabled(! $editable)></textarea></div>
                                    <div class="form-grid">
                                        <div class="field">
                                            <label>Tipo</label>
                                            <select wire:model.defer="lessonForm.content_type" @disabled(! $editable)>
                                                <option value="youtube">YouTube</option>
                                                <option value="vimeo">Vimeo</option>
                                                <option value="mp4">MP4</option>
                                                <option value="pdf">PDF</option>
                                                <option value="docx">DOCX</option>
                                                <option value="pptx">PPTX</option>
                                                <option value="external_link">Link externo</option>
                                            </select>
                                        </div>
                                        <div class="field"><label>Duração</label><input type="number" wire:model.defer="lessonForm.duration_minutes" @disabled(! $editable)></div>
                                    </div>
                                    <div class="field"><label>URL do conteúdo</label><input wire:model.defer="lessonForm.content_url" @disabled(! $editable)></div>
                                    <div class="check-row">
                                        <label><input type="checkbox" wire:model.defer="lessonForm.is_required" @disabled(! $editable)> Obrigatória</label>
                                        <label><input type="checkbox" wire:model.defer="lessonForm.is_available" @disabled(! $editable)> Disponível</label>
                                    </div>
                                    <button type="submit" class="builder-btn primary" @disabled(! $editable)>Criar aula</button>
                                </form>
                            </section>

                            <section class="section-card">
                                <h3 class="section-title">3. Material / PDF</h3>
                                <form class="form-stack" wire:submit="uploadMaterial">
                                    <div class="field">
                                        <label>Aula</label>
                                        <select wire:model.defer="materialForm.lesson_id" @disabled(! $editable)>
                                            <option value="">Selecione</option>
                                            @foreach ($course->modules as $module)
                                                @foreach ($module->lessons as $lesson)
                                                    <option value="{{ $lesson->id }}">{{ $module->title }} - {{ $lesson->title }}</option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field"><label>Título</label><input wire:model.defer="materialForm.title" @disabled(! $editable)></div>
                                    <div class="field"><label>Arquivo</label><input type="file" wire:model="materialFile" @disabled(! $editable)></div>
                                    <button type="submit" class="builder-btn primary" @disabled(! $editable)>Enviar material</button>
                                </form>
                            </section>

                            <section class="section-card">
                                <h3 class="section-title">4. Prova</h3>
                                <form class="form-stack" wire:submit="createExam">
                                    <div class="field"><label>Título</label><input wire:model.defer="examForm.title" @disabled(! $editable)></div>
                                    <div class="field"><label>Descrição</label><textarea wire:model.defer="examForm.description" @disabled(! $editable)></textarea></div>
                                    <div class="form-grid">
                                        <div class="field"><label>Nota mínima</label><input type="number" step="0.1" wire:model.defer="examForm.minimum_grade" @disabled(! $editable)></div>
                                        <div class="field"><label>Tentativas</label><input type="number" wire:model.defer="examForm.max_attempts" @disabled(! $editable)></div>
                                    </div>
                                    <button type="submit" class="builder-btn primary" @disabled(! $editable)>Criar prova</button>
                                </form>
                            </section>

                            <section class="section-card">
                                <h3 class="section-title">5. Questão</h3>
                                <form class="form-stack" wire:submit="createQuestion">
                                    <div class="field">
                                        <label>Prova</label>
                                        <select wire:model.defer="questionForm.exam_id" @disabled(! $editable)>
                                            <option value="">Selecione</option>
                                            @foreach ($course->exams as $exam)
                                                <option value="{{ $exam->id }}">{{ $exam->title }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="field"><label>Enunciado</label><textarea wire:model.defer="questionForm.statement" @disabled(! $editable)></textarea></div>
                                    <div class="form-grid">
                                        <div class="field"><label>Tipo</label><select wire:model.defer="questionForm.type" @disabled(! $editable)><option value="multiple_choice">Múltipla escolha</option><option value="true_false">V/F</option><option value="essay">Dissertativa</option></select></div>
                                        <div class="field"><label>Peso</label><input type="number" step="0.1" wire:model.defer="questionForm.weight" @disabled(! $editable)></div>
                                    </div>
                                    <div class="form-grid">
                                        <div class="field"><label>A</label><input wire:model.defer="questionForm.option_a" @disabled(! $editable)></div>
                                        <div class="field"><label>B</label><input wire:model.defer="questionForm.option_b" @disabled(! $editable)></div>
                                        <div class="field"><label>C</label><input wire:model.defer="questionForm.option_c" @disabled(! $editable)></div>
                                        <div class="field"><label>D</label><input wire:model.defer="questionForm.option_d" @disabled(! $editable)></div>
                                    </div>
                                    <div class="field"><label>Correta</label><select wire:model.defer="questionForm.correct_option" @disabled(! $editable)><option>A</option><option>B</option><option>C</option><option>D</option></select></div>
                                    <button type="submit" class="builder-btn primary" @disabled(! $editable)>Criar questão</button>
                                </form>
                            </section>
                        </aside>
                    </main>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
