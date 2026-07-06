<x-filament-panels::page>
    @php($summary = $this->forumSummary())
    @php($selectedTopic = $this->selectedTopic())

    <style>
        .forum-lms { display: grid; gap: 18px; }
        .forum-card { background: #fff; border: 1px solid #dbe3ee; border-radius: 14px; padding: 22px; box-shadow: 0 16px 42px rgba(15, 23, 42, .07); }
        .forum-hero { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 18px; align-items: center; background: linear-gradient(135deg, #ffffff 0%, #eef8f2 100%); }
        .forum-kicker { color: #008f43; font-size: 12px; font-weight: 900; text-transform: uppercase; }
        .forum-title { margin: 4px 0 8px; color: #071527; font-size: 26px; font-weight: 900; }
        .forum-text { margin: 0; color: #536a5b; }
        .forum-stats { display: grid; grid-template-columns: repeat(4, minmax(92px, 1fr)); gap: 10px; }
        .forum-stat { padding: 14px; border-radius: 12px; background: #f8fafc; }
        .forum-stat strong { display: block; color: #071527; font-size: 22px; }
        .forum-stat span { color: #64748b; font-size: 12px; font-weight: 800; }
        .forum-toolbar { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 10px; align-items: center; }
        .forum-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .forum-tab, .forum-btn { display: inline-flex; align-items: center; justify-content: center; min-height: 36px; padding: 0 14px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; color: #334155; font-size: 13px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .forum-tab.active, .forum-btn.primary { border-color: #008f43; background: #008f43; color: #fff; }
        .forum-layout { display: grid; grid-template-columns: minmax(320px, .78fr) minmax(0, 1.22fr); gap: 16px; align-items: start; }
        .topic-list { display: grid; gap: 12px; }
        .topic-card { width: 100%; display: grid; gap: 10px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 14px; background: #fff; text-align: left; cursor: pointer; }
        .topic-card:hover, .topic-card.active { border-color: rgba(0, 143, 67, .4); background: #fbfdfb; }
        .topic-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; }
        .topic-card h2, .topic-detail h2, .reply-card strong { margin: 0; color: #071527; font-weight: 900; }
        .topic-meta { display: flex; flex-wrap: wrap; gap: 8px; color: #64748b; font-size: 12px; font-weight: 800; }
        .forum-badge { display: inline-flex; align-items: center; height: 26px; padding: 0 10px; border-radius: 999px; background: #eef8f2; color: #007a3a; font-size: 12px; font-weight: 900; }
        .forum-badge.gray { background: #f1f5f9; color: #475569; }
        .tag-row { display: flex; flex-wrap: wrap; gap: 6px; }
        .tag-pill { padding: 4px 8px; border-radius: 999px; border: 1px solid #dbe3ee; color: #536a5b; font-size: 12px; font-weight: 800; }
        .topic-detail { display: grid; gap: 16px; }
        .topic-body { padding: 16px; border-radius: 12px; background: #f8fafc; color: #334155; line-height: 1.65; }
        .reply-list { display: grid; gap: 10px; }
        .reply-card { display: grid; gap: 8px; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; }
        .reply-meta { color: #64748b; font-size: 12px; font-weight: 800; }
        .reply-form { display: grid; gap: 10px; }
        .reply-form textarea { width: 100%; min-height: 130px; resize: vertical; border: 1px solid #cbd5e1; border-radius: 12px; padding: 12px; color: #071527; }
        .empty-forum { padding: 42px 18px; text-align: center; color: #64748b; }
        @media (max-width: 1100px) { .forum-layout, .forum-hero { grid-template-columns: 1fr; } }
        @media (max-width: 720px) { .forum-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>

    <div class="forum-lms">
        <section class="forum-card forum-hero">
            <div>
                <span class="forum-kicker">Comunicação acadêmica</span>
                <h2 class="forum-title">Fórum dos seus cursos</h2>
                <p class="forum-text">Acompanhe dúvidas, respostas dos professores e discussões vinculadas às suas matrículas.</p>
            </div>
            <div class="forum-stats">
                <div class="forum-stat"><strong>{{ $summary['topics'] }}</strong><span>Tópicos</span></div>
                <div class="forum-stat"><strong>{{ $summary['open'] }}</strong><span>Abertos</span></div>
                <div class="forum-stat"><strong>{{ $summary['resolved'] }}</strong><span>Resolvidos</span></div>
                <div class="forum-stat"><strong>{{ $summary['unanswered'] }}</strong><span>Sem resposta</span></div>
            </div>
        </section>

        <section class="forum-card forum-toolbar">
            <div class="forum-tabs">
                <button type="button" class="forum-tab {{ $statusFilter === 'all' ? 'active' : '' }}" wire:click="$set('statusFilter', 'all')">Todos</button>
                <button type="button" class="forum-tab {{ $statusFilter === 'open' ? 'active' : '' }}" wire:click="$set('statusFilter', 'open')">Abertos</button>
                <button type="button" class="forum-tab {{ $statusFilter === 'resolved' ? 'active' : '' }}" wire:click="$set('statusFilter', 'resolved')">Resolvidos</button>
                <button type="button" class="forum-tab {{ $statusFilter === 'pinned' ? 'active' : '' }}" wire:click="$set('statusFilter', 'pinned')">Fixados</button>
            </div>
            <a class="forum-btn primary" href="/aluno/comunicacao">Chat com professor</a>
        </section>

        <div class="forum-layout">
            <section class="topic-list">
                @forelse ($this->getTopics() as $topic)
                    <button type="button" class="topic-card {{ $selectedTopic?->id === $topic->id ? 'active' : '' }}" wire:click="openTopic({{ $topic->id }})">
                        <div class="topic-head">
                            <div>
                                <span class="forum-kicker">{{ $topic->course?->name ?? $topic->category?->course?->name ?? 'Fórum' }}</span>
                                <h2>{{ $topic->title }}</h2>
                            </div>
                            <span class="forum-badge {{ $topic->status === 'resolved' ? '' : 'gray' }}">{{ match ($topic->status ?? 'open') {
                                'open' => 'Aberto',
                                'resolved' => 'Resolvido',
                                'pinned' => 'Fixado',
                                'closed' => 'Encerrado',
                                'hidden' => 'Oculto',
                                default => $topic->status ?? 'Aberto',
                            } }}</span>
                        </div>
                        <p class="forum-text">{{ str($topic->body)->stripTags()->limit(160) }}</p>
                        <div class="topic-meta">
                            <span>{{ $topic->author?->name ?? 'Participante' }}</span>
                            <span>{{ $topic->visible_replies_count }} resposta(s)</span>
                            <span>{{ optional($topic->last_activity_at ?? $topic->updated_at)->diffForHumans() }}</span>
                        </div>
                        @if ($topic->tags->isNotEmpty())
                            <div class="tag-row">
                                @foreach ($topic->tags as $tag)
                                    <span class="tag-pill">{{ $tag->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </button>
                @empty
                    <section class="forum-card empty-forum">
                        <h2 class="forum-title">Nenhum tópico disponível</h2>
                        <p>Os fóruns dos cursos em que você está matriculado aparecerão aqui.</p>
                        <a class="forum-btn primary" href="/aluno/comunicacao">Abrir chat com professor</a>
                    </section>
                @endforelse
            </section>

            <section class="forum-card topic-detail">
                @if ($selectedTopic)
                    <div>
                        <span class="forum-kicker">{{ $selectedTopic->course?->name ?? $selectedTopic->category?->course?->name ?? 'Fórum' }}</span>
                        <h2>{{ $selectedTopic->title }}</h2>
                        <div class="topic-meta" style="margin-top: 8px;">
                            <span>{{ $selectedTopic->author?->name ?? 'Participante' }}</span>
                            <span>{{ $selectedTopic->visible_replies_count }} resposta(s)</span>
                            <span>{{ optional($selectedTopic->last_activity_at ?? $selectedTopic->updated_at)->diffForHumans() }}</span>
                        </div>
                    </div>

                    <div class="topic-body">{!! nl2br(e(strip_tags($selectedTopic->body))) !!}</div>

                    <div>
                        <h3 style="margin: 0 0 10px; color: #071527; font-weight: 900;">Respostas</h3>
                        <div class="reply-list">
                            @forelse ($selectedTopic->visibleReplies as $reply)
                                <article class="reply-card">
                                    <div>
                                        <strong>{{ $reply->author?->name ?? 'Participante' }}</strong>
                                        <div class="reply-meta">{{ $reply->created_at?->diffForHumans() }}</div>
                                    </div>
                                    <p class="forum-text">{!! nl2br(e(strip_tags($reply->body))) !!}</p>
                                </article>
                            @empty
                                <div class="reply-card">
                                    <strong>Seja o primeiro a responder</strong>
                                    <p class="forum-text">Ajude a turma com uma dúvida, referência ou comentário sobre o conteúdo.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if (! $selectedTopic->is_closed && ! in_array($selectedTopic->status, ['closed', 'hidden'], true))
                        <form class="reply-form" wire:submit="submitReply">
                            <label class="forum-kicker" for="replyBody">Sua resposta</label>
                            <textarea id="replyBody" wire:model.defer="replyBody" placeholder="Escreva sua contribuição para a turma..."></textarea>
                            @error('replyBody')
                                <span style="color: #b91c1c; font-size: 13px; font-weight: 800;">{{ $message }}</span>
                            @enderror
                            <div style="display: flex; justify-content: flex-end;">
                                <button type="submit" class="forum-btn primary">Publicar resposta</button>
                            </div>
                        </form>
                    @else
                        <div class="reply-card">
                            <strong>Tópico encerrado</strong>
                            <p class="forum-text">Este tópico não aceita novas respostas.</p>
                        </div>
                    @endif
                @else
                    <div class="empty-forum">
                        <h2 class="forum-title">Selecione um tópico</h2>
                        <p>Escolha uma discussão ao lado para ler as respostas e participar.</p>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-filament-panels::page>
