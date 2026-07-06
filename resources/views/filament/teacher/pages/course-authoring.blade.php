<x-filament-panels::page>
    <style>
        .authoring { display: grid; gap: 18px; }
        .author-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .author-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #fff 0%, #eef8f2 100%); }
        .author-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .author-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .author-text { margin: 0; color: #536a5b; }
        .author-grid { display: grid; grid-template-columns: minmax(0, 1fr) 420px; gap: 16px; align-items: start; }
        .author-form { display: grid; gap: 14px; }
        .author-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .field { display: grid; gap: 6px; }
        .field.full { grid-column: 1 / -1; }
        .field label { color: #334155; font-size: 13px; font-weight: 900; }
        .field input, .field select, .field textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 12px; color: #071527; }
        .field textarea { min-height: 92px; resize: vertical; }
        .author-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .author-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .author-btn.warning { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
        .course-stack { display: grid; gap: 10px; }
        .course-review-card { display: grid; gap: 10px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        .course-review-card h3 { margin: 0; color: #071527; font-weight: 900; }
        .badge { display: inline-flex; width: fit-content; min-height: 26px; align-items: center; padding: 0 10px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 900; }
        .badge.warn { background: #fffbeb; color: #92400e; }
        .badge.gray { background: #f1f5f9; color: #475569; }
        .missing-list { margin: 0; padding-left: 18px; color: #92400e; font-size: 13px; }
        @media (max-width: 1100px) { .author-grid, .author-hero, .author-form-grid { grid-template-columns: 1fr; } .field.full { grid-column: auto; } }
    </style>

    <div class="authoring">
        <section class="author-card author-hero">
            <div>
                <span class="author-kicker">Fluxo editorial</span>
                <h2 class="author-title">Crie cursos e envie para revisão</h2>
                <p class="author-text">O curso nasce como rascunho. Depois de enviado, o administrador revisa e libera para matrícula.</p>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a class="author-btn" href="/professor/conteudos">Montar conteúdos</a>
                <button type="button" class="author-btn primary" wire:click="newCourse">Novo rascunho</button>
            </div>
        </section>

        <div class="author-grid">
            <section class="author-card">
                <form class="author-form" wire:submit="saveDraft">
                    <div>
                        <span class="author-kicker">{{ $editingCourseId ? 'Editando rascunho' : 'Novo curso' }}</span>
                        <h2 class="author-title" style="font-size: 22px;">Dados do curso</h2>
                    </div>

                    <div class="author-form-grid">
                        <div class="field">
                            <label>Categoria</label>
                            <select wire:model.defer="courseForm.category_id">
                                <option value="">Selecione</option>
                                @foreach ($this->categories() as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('courseForm.category_id') <span style="color:#b91c1c;">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label>Nome</label>
                            <input wire:model.defer="courseForm.name" type="text">
                            @error('courseForm.name') <span style="color:#b91c1c;">{{ $message }}</span> @enderror
                        </div>
                        <div class="field full">
                            <label>Descrição curta</label>
                            <textarea wire:model.defer="courseForm.short_description"></textarea>
                        </div>
                        <div class="field full">
                            <label>Descrição completa</label>
                            <textarea wire:model.defer="courseForm.description"></textarea>
                        </div>
                        <div class="field">
                            <label>Carga horária</label>
                            <input wire:model.defer="courseForm.workload_hours" type="number" min="0">
                        </div>
                        <div class="field">
                            <label>Nota mínima</label>
                            <input wire:model.defer="courseForm.minimum_grade" type="number" min="0" max="10" step="0.1">
                        </div>
                        <div class="field">
                            <label>Conclusão mínima (%)</label>
                            <input wire:model.defer="courseForm.minimum_progress_percent" type="number" min="0" max="100">
                        </div>
                        <div class="field">
                            <label>Limite de vagas</label>
                            <input wire:model.defer="courseForm.seat_limit" type="number" min="1">
                        </div>
                        <div class="field">
                            <label>Início</label>
                            <input wire:model.defer="courseForm.starts_at" type="date">
                        </div>
                        <div class="field">
                            <label>Término</label>
                            <input wire:model.defer="courseForm.ends_at" type="date">
                        </div>
                        <div class="field full">
                            <label>Vídeo de apresentação</label>
                            <input wire:model.defer="courseForm.presentation_video_url" type="text">
                        </div>
                    </div>

                    <div style="display:flex; gap:8px; justify-content:flex-end;">
                        <button type="submit" class="author-btn primary">Salvar rascunho</button>
                    </div>
                </form>
            </section>

            <aside class="author-card">
                <span class="author-kicker">Esteira editorial</span>
                <h2 class="author-title" style="font-size: 22px;">Meus cursos</h2>
                <div class="course-stack">
                    @forelse ($this->getCourses() as $course)
                        @php($missing = $this->missingRequirements($course))
                        <article class="course-review-card">
                            <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start;">
                                <div>
                                    <h3>{{ $course->name }}</h3>
                                    <p class="author-text">{{ $course->category?->name ?? 'Sem categoria' }}</p>
                                </div>
                                <span @class(['badge', 'warn' => in_array($course->status, ['draft', 'changes_requested']), 'gray' => $course->status === 'closed'])>
                                    {{ match ($course->status) {
                                        'pending_review' => 'Em revisão',
                                        'changes_requested' => 'Ajustes solicitados',
                                        'published' => 'Publicado',
                                        'closed' => 'Encerrado',
                                        default => 'Rascunho',
                                    } }}
                                </span>
                            </div>

                            @if ($course->review_notes)
                                <p class="author-text"><strong>Observação:</strong> {{ $course->review_notes }}</p>
                            @endif

                            @if ($missing)
                                <ul class="missing-list">
                                    @foreach ($missing as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                @if (in_array($course->status, ['draft', 'changes_requested'], true))
                                    <button type="button" class="author-btn" wire:click="editCourse({{ $course->id }})">Editar</button>
                                    <a class="author-btn" href="/professor/conteudos">Conteúdos</a>
                                    <button type="button" class="author-btn primary" wire:click="submitForReview({{ $course->id }})" @disabled((bool) $missing)>Enviar para revisão</button>
                                @endif
                                @if ($course->status === 'published')
                                    <a class="author-btn" href="/cursos/{{ $course->slug }}" target="_blank">Ver no portal</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="author-text">Você ainda não criou cursos.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</x-filament-panels::page>
