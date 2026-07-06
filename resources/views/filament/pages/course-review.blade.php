<x-filament-panels::page>
    @php($pending = $this->getPendingCourses())
    @php($course = $this->selectedCourse())

    <style>
        .review { display: grid; gap: 18px; }
        .review-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .review-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #fff 0%, #eef8f2 100%); }
        .review-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .review-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .review-text { margin: 0; color: #536a5b; }
        .review-grid { display: grid; grid-template-columns: 380px minmax(0, 1fr); gap: 16px; align-items: start; }
        .review-list { display: grid; gap: 10px; }
        .review-item { width: 100%; display: grid; gap: 8px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; color: #334155; text-align: left; cursor: pointer; }
        .review-item.active, .review-item:hover { border-color: rgba(0, 143, 67, .42); background: #eef8f2; }
        .review-item strong { color: #071527; font-weight: 900; }
        .review-meta { display: flex; flex-wrap: wrap; gap: 8px; color: #64748b; font-size: 12px; font-weight: 800; }
        .review-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .review-stat { padding: 14px; border-radius: 12px; background: #f8fafc; }
        .review-stat strong { display: block; color: #071527; font-size: 22px; }
        .review-stat span { color: #64748b; font-size: 12px; font-weight: 800; }
        .review-detail { display: grid; gap: 16px; }
        .module-list { display: grid; gap: 10px; }
        .module-box { padding: 12px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; }
        .review-actions { display: grid; gap: 10px; }
        .review-actions textarea { min-height: 110px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; color: #071527; }
        .review-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; cursor: pointer; }
        .review-btn.success { border-color: #008f43; background: #008f43; color: #fff; }
        .review-btn.warning { border-color: #f59e0b; background: #fffbeb; color: #92400e; }
        @media (max-width: 1060px) { .review-grid, .review-hero { grid-template-columns: 1fr; } .review-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>

    <div class="review">
        <section class="review-card review-hero">
            <div>
                <span class="review-kicker">Governança acadêmica</span>
                <h2 class="review-title">Fila de revisão de cursos</h2>
                <p class="review-text">Aprove cursos enviados por professores ou devolva com observações antes da publicação.</p>
            </div>
            <div class="review-stat"><strong>{{ $pending->count() }}</strong><span>Aguardando revisão</span></div>
        </section>

        <div class="review-grid">
            <aside class="review-card review-list">
                @forelse ($pending as $item)
                    <button type="button" class="review-item {{ $course?->id === $item->id ? 'active' : '' }}" wire:click="openCourse({{ $item->id }})">
                        <strong>{{ $item->name }}</strong>
                        <span class="review-text">{{ $item->category?->name }} | {{ $item->teacher?->name }}</span>
                        <div class="review-meta">
                            <span>{{ $item->workload_hours }}h</span>
                            <span>{{ $item->modules->count() }} módulo(s)</span>
                            <span>{{ $item->submitted_for_review_at?->diffForHumans() }}</span>
                        </div>
                    </button>
                @empty
                    <div style="padding: 32px; text-align: center;">
                        <h2 class="review-title" style="font-size: 22px;">Nada pendente</h2>
                        <p class="review-text">Nenhum curso aguardando revisão no momento.</p>
                    </div>
                @endforelse
            </aside>

            <section class="review-card review-detail">
                @if ($course)
                    <div>
                        <span class="review-kicker">{{ $course->category?->name }}</span>
                        <h2 class="review-title">{{ $course->name }}</h2>
                        <p class="review-text">{{ $course->short_description }}</p>
                    </div>

                    <div class="review-stats">
                        <div class="review-stat"><strong>{{ $course->workload_hours }}h</strong><span>Carga</span></div>
                        <div class="review-stat"><strong>{{ $course->minimum_grade }}</strong><span>Nota mínima</span></div>
                        <div class="review-stat"><strong>{{ $course->minimum_progress_percent }}%</strong><span>Conclusão</span></div>
                        <div class="review-stat"><strong>{{ $course->exams->count() }}</strong><span>Avaliações</span></div>
                    </div>

                    <div>
                        <h3 style="margin: 0 0 10px; color: #071527; font-weight: 900;">Módulos e aulas</h3>
                        <div class="module-list">
                            @forelse ($course->modules as $module)
                                <div class="module-box">
                                    <strong>{{ $module->title }}</strong>
                                    <p class="review-text">{{ $module->lessons->count() }} aula(s)</p>
                                </div>
                            @empty
                                <p class="review-text">Nenhum módulo cadastrado ainda.</p>
                            @endforelse
                        </div>
                    </div>

                    <form class="review-actions">
                        <label class="review-kicker" for="reviewNotes">Observações da revisão</label>
                        <textarea id="reviewNotes" wire:model.defer="reviewNotes" placeholder="Informe elogios, ajustes necessários ou observações internas."></textarea>
                        @error('reviewNotes')
                            <span style="color:#b91c1c; font-weight:800;">{{ $message }}</span>
                        @enderror
                        <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                            <button type="button" class="review-btn warning" wire:click="requestChanges">Devolver com ajustes</button>
                            <button type="button" class="review-btn success" wire:click="approve">Aprovar e publicar</button>
                        </div>
                    </form>
                @else
                    <div style="padding: 42px; text-align: center;">
                        <h2 class="review-title" style="font-size: 22px;">Selecione um curso</h2>
                        <p class="review-text">Escolha um item da fila para revisar conteúdo e publicar.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
