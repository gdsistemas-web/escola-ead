<x-filament-panels::page>
    @php($courses = $this->getCourses())
    @php($room = $this->selectedRoom())

    <style>
        .chat-lms { display: grid; gap: 18px; }
        .chat-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .chat-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; padding: 22px; background: linear-gradient(135deg, #ffffff 0%, #eef8f2 100%); }
        .chat-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .chat-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .chat-text { margin: 0; color: #536a5b; }
        .chat-grid { display: grid; grid-template-columns: 330px minmax(0, 1fr); gap: 16px; align-items: start; }
        .course-list { display: grid; gap: 10px; padding: 14px; }
        .course-chat-button { width: 100%; display: grid; gap: 8px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; color: #334155; text-align: left; cursor: pointer; }
        .course-chat-button:hover, .course-chat-button.active { border-color: rgba(0, 143, 67, .42); background: #eef8f2; }
        .course-chat-button strong { color: #071527; font-weight: 900; }
        .course-chat-button span { color: #64748b; font-size: 13px; font-weight: 700; }
        .chat-room { overflow: hidden; }
        .chat-room-head { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 18px 20px; border-bottom: 1px solid #e2e8f0; background: #fbfdfb; }
        .teacher-pill { display: inline-flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 13px; font-weight: 900; }
        .message-list { min-height: 420px; max-height: 56vh; overflow: auto; display: grid; align-content: end; gap: 12px; padding: 20px; background: #f8fafc; }
        .message-row { display: flex; }
        .message-row.mine { justify-content: flex-end; }
        .message-bubble { max-width: min(680px, 82%); padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; color: #334155; }
        .message-row.mine .message-bubble { border-color: #008f43; background: #008f43; color: #fff; }
        .message-meta { display: flex; gap: 8px; align-items: center; margin-bottom: 6px; font-size: 12px; font-weight: 900; opacity: .8; }
        .message-body { white-space: pre-wrap; line-height: 1.55; }
        .chat-composer { display: grid; gap: 10px; padding: 16px; border-top: 1px solid #e2e8f0; background: #fff; }
        .chat-composer textarea { width: 100%; min-height: 92px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; color: #071527; }
        .chat-actions { display: flex; justify-content: space-between; gap: 10px; align-items: center; }
        .chat-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 16px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .chat-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .chat-empty { padding: 54px 18px; text-align: center; color: #64748b; }
        .chat-empty h2 { margin: 0 0 8px; color: #071527; font-size: 24px; font-weight: 900; }
        @media (max-width: 980px) { .chat-grid, .chat-hero { grid-template-columns: 1fr; } .message-bubble { max-width: 100%; } }
    </style>

    <div class="chat-lms">
        <section class="chat-card chat-hero">
            <div>
                <span class="chat-kicker">Comunicação do curso</span>
                <h2 class="chat-title">Chat com o professor</h2>
                <p class="chat-text">Use este espaço para dúvidas rápidas, orientação de estudos e acompanhamento individual.</p>
            </div>
            <a class="chat-btn" href="/aluno/forum">Ir para fórum acadêmico</a>
        </section>

        <div class="chat-grid">
            <aside class="chat-card course-list">
                @forelse ($courses as $course)
                    <button type="button" class="course-chat-button {{ $selectedCourseId === $course->id ? 'active' : '' }}" wire:click="openCourse({{ $course->id }})">
                        <strong>{{ $course->name }}</strong>
                        <span>Professor: {{ $course->teacher?->name ?? 'Equipe EAD EPI' }}</span>
                    </button>
                @empty
                    <div class="chat-empty">
                        <h2>Nenhum curso ativo</h2>
                        <p>Faça uma matrícula para liberar o chat com professor.</p>
                    </div>
                @endforelse
            </aside>

            <section class="chat-card chat-room">
                @if ($room)
                    <header class="chat-room-head">
                        <div>
                            <span class="chat-kicker">{{ $room->course?->name }}</span>
                            <h2 style="margin: 4px 0 0; color: #071527; font-weight: 900;">Atendimento aluno-professor</h2>
                        </div>
                        <span class="teacher-pill">{{ $room->course?->teacher?->name ?? 'Professor' }}</span>
                    </header>

                    <div class="message-list">
                        @forelse ($room->messages as $message)
                            <article class="message-row {{ $message->user_id === auth()->id() ? 'mine' : '' }}">
                                <div class="message-bubble">
                                    <div class="message-meta">
                                        <span>{{ $message->user?->name ?? 'Participante' }}</span>
                                        <span>{{ $message->sent_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <div class="message-body">{{ $message->body }}</div>
                                </div>
                            </article>
                        @empty
                            <div class="chat-empty">
                                <h2>Comece a conversa</h2>
                                <p>Envie sua primeira mensagem para o professor deste curso.</p>
                            </div>
                        @endforelse
                    </div>

                    <form class="chat-composer" wire:submit="sendMessage">
                        <textarea wire:model.defer="messageBody" placeholder="Digite sua mensagem para o professor..."></textarea>
                        @error('messageBody')
                            <span style="color: #b91c1c; font-size: 13px; font-weight: 800;">{{ $message }}</span>
                        @enderror
                        <div class="chat-actions">
                            <span class="chat-text">Mensagens ficam registradas para acompanhamento pedagógico.</span>
                            <button type="submit" class="chat-btn primary">Enviar mensagem</button>
                        </div>
                    </form>
                @else
                    <div class="chat-empty">
                        <h2>Selecione um curso</h2>
                        <p>Escolha um curso à esquerda para abrir o chat direto com o professor responsável.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
