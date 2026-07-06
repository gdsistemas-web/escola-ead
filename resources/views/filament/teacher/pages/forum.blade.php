<x-filament-panels::page>
    @php($summary = $this->summary())
    @php($selectedTopic = $this->selectedTopic())

    <style>
        .forum-lms { display: grid; gap: 18px; }
        .forum-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .forum-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #fff 0%, #eef8f2 100%); }
        .forum-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .forum-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .forum-text { margin: 0; color: #536a5b; }
        .forum-stats { display: grid; grid-template-columns: repeat(4, minmax(92px, 1fr)); gap: 10px; }
        .forum-stat { padding: 14px; border-radius: 12px; background: #f8fafc; }
        .forum-stat strong { display: block; color: #071527; font-size: 22px; }
        .forum-stat span { color: #64748b; font-size: 12px; font-weight: 800; }
        .forum-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .forum-tab, .forum-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .forum-tab.active, .forum-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .forum-layout { display: grid; grid-template-columns: minmax(320px, .78fr) minmax(0, 1.22fr); gap: 16px; align-items: start; }
        .topic-list { display: grid; gap: 12px; }
        .topic-card { width: 100%; display: grid; gap: 10px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; text-align: left; cursor: pointer; }
        .topic-card:hover, .topic-card.active { border-color: rgba(0, 143, 67, .4); background: #fbfdfb; }
        .topic-card h2, .topic-detail h2 { margin: 0; color: #071527; font-weight: 900; }
        .topic-meta { display: flex; flex-wrap: wrap; gap: 8px; color: #64748b; font-size: 12px; font-weight: 800; }
        .badge-soft { display: inline-flex; align-items: center; width: fit-content; height: 26px; padding: 0 10px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 900; }
        .topic-detail { display: grid; gap: 16px; }
        .topic-body, .reply-card { padding: 16px; border-radius: 12px; background: #f8fafc; color: #334155; line-height: 1.65; }
        .reply-list { display: grid; gap: 10px; }
        .reply-card { border: 1px solid #e2e8f0; background: #fff; }
        .reply-card strong { color: #071527; }
        .reply-form { display: grid; gap: 10px; }
        .reply-form textarea { width: 100%; min-height: 130px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; color: #071527; }
        .empty-forum { padding: 42px 18px; text-align: center; color: #64748b; }
        @media (max-width: 1100px) { .forum-layout, .forum-hero { grid-template-columns: 1fr; } }
        @media (max-width: 720px) { .forum-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>

    <div class="forum-lms">
        <section class="forum-card forum-hero">
            <div>
                <span class="forum-kicker">Mediação acadêmica</span>
                <h2 class="forum-title">Fórum dos seus cursos</h2>
                <p class="forum-text">Acompanhe dúvidas abertas pelos alunos e responda diretamente pelo painel.</p>
            </div>
            <div class="forum-stats">
                <div class="forum-stat"><strong>{{ $summary['topics'] }}</strong><span>Tópicos</span></div>
                <div class="forum-stat"><strong>{{ $summary['open'] }}</strong><span>Abertos</span></div>
                <div class="forum-stat"><strong>{{ $summary['resolved'] }}</strong><span>Resolvidos</span></div>
                <div class="forum-stat"><strong>{{ $summary['unanswered'] }}</strong><span>Sem resposta</span></div>
            </div>
        </section>

        <section class="forum-card">
            <div class="forum-tabs">
                <button type="button" class="forum-tab {{ $statusFilter === 'all' ? 'active' : '' }}" wire:click="$set('statusFilter', 'all')">Todos</button>
                <button type="button" class="forum-tab {{ $statusFilter === 'open' ? 'active' : '' }}" wire:click="$set('statusFilter', 'open')">Abertos</button>
                <button type="button" class="forum-tab {{ $statusFilter === 'resolved' ? 'active' : '' }}" wire:click="$set('statusFilter', 'resolved')">Resolvidos</button>
                <a class="forum-btn primary" href="/professor/comunicacao">Chats dos alunos</a>
            </div>
        </section>

        <div class="forum-layout">
            <section class="topic-list">
                @forelse ($this->getTopics() as $topic)
                    <button type="button" class="topic-card {{ $selectedTopic?->id === $topic->id ? 'active' : '' }}" wire:click="openTopic({{ $topic->id }})">
                        <span class="forum-kicker">{{ $topic->course?->name ?? $topic->category?->course?->name ?? 'Curso' }}</span>
                        <h2>{{ $topic->title }}</h2>
                        <p class="forum-text">{{ str($topic->body)->stripTags()->limit(150) }}</p>
                        <div class="topic-meta">
                            <span>{{ $topic->author?->name ?? 'Participante' }}</span>
                            <span>{{ $topic->visible_replies_count }} resposta(s)</span>
                            <span>{{ optional($topic->last_activity_at ?? $topic->updated_at)->diffForHumans() }}</span>
                        </div>
                        <span class="badge-soft">{{ match ($topic->status ?? 'open') {
                            'open' => 'Aberto',
                            'resolved' => 'Resolvido',
                            'pinned' => 'Fixado',
                            'closed' => 'Encerrado',
                            'hidden' => 'Oculto',
                            default => $topic->status ?? 'Aberto',
                        } }}</span>
                    </button>
                @empty
                    <section class="forum-card empty-forum">
                        <h2 class="forum-title">Nenhum tópico nos seus cursos</h2>
                        <p>Quando alunos participarem, os tópicos aparecerão aqui.</p>
                    </section>
                @endforelse
            </section>

            <section class="forum-card topic-detail">
                @if ($selectedTopic)
                    <div>
                        <span class="forum-kicker">{{ $selectedTopic->course?->name ?? $selectedTopic->category?->course?->name ?? 'Curso' }}</span>
                        <h2>{{ $selectedTopic->title }}</h2>
                        <div class="topic-meta" style="margin-top: 8px;">
                            <span>{{ $selectedTopic->author?->name ?? 'Participante' }}</span>
                            <span>{{ $selectedTopic->visible_replies_count }} resposta(s)</span>
                        </div>
                    </div>
                    <div class="topic-body">{!! nl2br(e(strip_tags($selectedTopic->body))) !!}</div>
                    <div class="reply-list">
                        @forelse ($selectedTopic->visibleReplies as $reply)
                            <article class="reply-card">
                                <strong>{{ $reply->author?->name ?? 'Participante' }}</strong>
                                <p class="forum-text">{!! nl2br(e(strip_tags($reply->body))) !!}</p>
                            </article>
                        @empty
                            <article class="reply-card">
                                <strong>Ainda sem respostas</strong>
                                <p class="forum-text">Responda para orientar a turma.</p>
                            </article>
                        @endforelse
                    </div>
                    <form class="reply-form" wire:submit="submitReply">
                        <label class="forum-kicker" for="replyBody">Responder como professor</label>
                        <textarea id="replyBody" wire:model.defer="replyBody" placeholder="Escreva uma orientação clara para o aluno..."></textarea>
                        @error('replyBody')
                            <span style="color: #b91c1c; font-size: 13px; font-weight: 800;">{{ $message }}</span>
                        @enderror
                        <div style="display: flex; justify-content: flex-end;">
                            <button type="submit" class="forum-btn primary">Publicar resposta</button>
                        </div>
                    </form>
                @else
                    <div class="empty-forum">
                        <h2 class="forum-title">Selecione um tópico</h2>
                        <p>Escolha uma dúvida à esquerda para responder.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
