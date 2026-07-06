<x-filament-panels::page>
    @php($rooms = $this->getRooms())
    @php($room = $this->selectedRoom())

    <style>
        .teacher-chat { display: grid; gap: 18px; }
        .chat-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .chat-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; padding: 22px; background: linear-gradient(135deg, #fff 0%, #eef8f2 100%); }
        .chat-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .chat-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .chat-text { margin: 0; color: #536a5b; }
        .chat-grid { display: grid; grid-template-columns: 360px minmax(0, 1fr); gap: 16px; align-items: start; }
        .room-list { display: grid; gap: 10px; padding: 14px; }
        .room-btn { width: 100%; display: grid; gap: 8px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; color: #334155; text-align: left; cursor: pointer; }
        .room-btn:hover, .room-btn.active { border-color: rgba(0, 143, 67, .42); background: #eef8f2; }
        .room-btn strong { color: #071527; font-weight: 900; }
        .room-btn span { color: #64748b; font-size: 13px; font-weight: 700; }
        .chat-head { display: flex; justify-content: space-between; gap: 14px; align-items: center; padding: 18px 20px; border-bottom: 1px solid #e2e8f0; background: #fbfdfb; }
        .message-list { min-height: 420px; max-height: 56vh; overflow: auto; display: grid; align-content: end; gap: 12px; padding: 20px; background: #f8fafc; }
        .message-row { display: flex; }
        .message-row.mine { justify-content: flex-end; }
        .bubble { max-width: min(680px, 82%); padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; color: #334155; }
        .message-row.mine .bubble { border-color: #008f43; background: #008f43; color: #fff; }
        .meta { display: flex; gap: 8px; margin-bottom: 6px; font-size: 12px; font-weight: 900; opacity: .8; }
        .composer { display: grid; gap: 10px; padding: 16px; border-top: 1px solid #e2e8f0; background: #fff; }
        .composer textarea { width: 100%; min-height: 92px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; color: #071527; }
        .chat-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 38px; padding: 0 16px; border: 1px solid #008f43; border-radius: 10px; background: #008f43; color: #fff; font-size: 13px; font-weight: 900; cursor: pointer; }
        .empty { padding: 54px 18px; text-align: center; color: #64748b; }
        .empty h2 { margin: 0 0 8px; color: #071527; font-size: 24px; font-weight: 900; }
        @media (max-width: 980px) { .chat-grid, .chat-hero { grid-template-columns: 1fr; } .bubble { max-width: 100%; } }
    </style>

    <div class="teacher-chat">
        <section class="chat-card chat-hero">
            <div>
                <span class="chat-kicker">Atendimento pedagógico</span>
                <h2 class="chat-title">Chats com alunos</h2>
                <p class="chat-text">Responda mensagens diretas enviadas pelos alunos nos cursos que você ministra.</p>
            </div>
            <span class="chat-kicker">{{ $rooms->count() }} conversa(s)</span>
        </section>

        <div class="chat-grid">
            <aside class="chat-card room-list">
                @forelse ($rooms as $chatRoom)
                    @php($student = $chatRoom->participants->first(fn ($participant) => $participant->user_id !== auth()->id())?->user)
                    @php($last = $chatRoom->messages->last())
                    <button type="button" class="room-btn {{ $room?->id === $chatRoom->id ? 'active' : '' }}" wire:click="openRoom({{ $chatRoom->id }})">
                        <strong>{{ $student?->name ?? 'Aluno' }}</strong>
                        <span>{{ $chatRoom->course?->name }}</span>
                        <span>{{ $last?->body ? str($last->body)->limit(80) : 'Sem mensagens ainda' }}</span>
                    </button>
                @empty
                    <div class="empty">
                        <h2>Nenhum chat aberto</h2>
                        <p>Quando um aluno iniciar conversa, ela aparecerá aqui.</p>
                    </div>
                @endforelse
            </aside>

            <section class="chat-card">
                @if ($room)
                    @php($student = $room->participants->first(fn ($participant) => $participant->user_id !== auth()->id())?->user)
                    <header class="chat-head">
                        <div>
                            <span class="chat-kicker">{{ $room->course?->name }}</span>
                            <h2 style="margin: 4px 0 0; color: #071527; font-weight: 900;">{{ $student?->name ?? 'Aluno' }}</h2>
                        </div>
                    </header>
                    <div class="message-list">
                        @forelse ($room->messages as $message)
                            <article class="message-row {{ $message->user_id === auth()->id() ? 'mine' : '' }}">
                                <div class="bubble">
                                    <div class="meta">
                                        <span>{{ $message->user?->name ?? 'Participante' }}</span>
                                        <span>{{ $message->sent_at?->format('d/m/Y H:i') }}</span>
                                    </div>
                                    <div style="white-space: pre-wrap;">{{ $message->body }}</div>
                                </div>
                            </article>
                        @empty
                            <div class="empty">
                                <h2>Sem mensagens</h2>
                                <p>Envie a primeira orientação para este aluno.</p>
                            </div>
                        @endforelse
                    </div>
                    <form class="composer" wire:submit="sendMessage">
                        <textarea wire:model.defer="messageBody" placeholder="Digite sua resposta ao aluno..."></textarea>
                        @error('messageBody')
                            <span style="color: #b91c1c; font-size: 13px; font-weight: 800;">{{ $message }}</span>
                        @enderror
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="chat-btn">Enviar resposta</button>
                        </div>
                    </form>
                @else
                    <div class="empty">
                        <h2>Selecione uma conversa</h2>
                        <p>Escolha um aluno à esquerda para responder.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
