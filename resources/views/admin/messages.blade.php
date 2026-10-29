<x-layouts.admin title="Messages">
    <div>
        <x-admin.table-card title="Messages" :count="number_format($messages->total())">
            <x-slot:toolbar>
                <form method="GET" data-autosubmit class="flex flex-1 flex-wrap items-center gap-3">
                    <input name="q" type="search" value="{{ $filters['q'] ?? '' }}" placeholder="Search messages" class="field max-w-xs" aria-label="Search messages">
                    <select name="topic" class="field sm:w-auto" aria-label="Topic">
                        <option value="">All topics</option>
                        @foreach (\App\Models\ContactMessage::TOPICS as $value => $label)<option value="{{ $value }}" @selected(($filters['topic'] ?? null) === $value)>{{ $label }}</option>@endforeach
                    </select>
                    <select name="status" class="field sm:w-auto" aria-label="Status">
                        <option value="">New and read</option>
                        <option value="new" @selected(($filters['status'] ?? null) === 'new')>New</option>
                        <option value="read" @selected(($filters['status'] ?? null) === 'read')>Read</option>
                        <option value="unanswered" @selected(($filters['status'] ?? null) === 'unanswered')>Not replied yet</option>
                        <option value="answered" @selected(($filters['status'] ?? null) === 'answered')>Replied</option>
                    </select>
                    <button class="btn-secondary">Search</button>
                    @if (array_filter($filters))<a href="{{ route('admin.messages.index') }}" class="btn-ghost">Clear</a>@endif
                </form>
            </x-slot:toolbar>
            <table class="admin-table min-w-[860px]">
                <thead>
                    <tr><th>From</th><th>Topic</th><th>Message</th><th>Received</th><th>Status</th><th class="text-right">Actions</th></tr>
                </thead>
                <tbody>
                    @forelse ($messages as $m)
                        <tr @class(['font-medium' => ! $m->read_at])>
                            <td class="min-w-44">
                                <p class="text-ink">{{ $m->name }}</p>
                                <p class="text-xs text-muted">{{ $m->contact }}</p>
                            </td>
                            <td class="min-w-40">
                                <span class="badge-confirmed">{{ $m->topicLabel() }}</span>
                                @if ($m->facility_name)<p class="mt-1 text-xs text-muted">{{ $m->facility_name }}</p>@endif
                            </td>
                            <td class="max-w-md">
                                <details>
                                    <summary class="cursor-pointer list-none truncate text-ink [&::-webkit-details-marker]:hidden">{{ Str::limit($m->message, 90) }}</summary>
                                    <p class="mt-2 whitespace-pre-line text-sm font-normal text-ink">{{ $m->message }}</p>
                                </details>
                            </td>
                            <td class="whitespace-nowrap text-xs text-muted" title="{{ $m->created_at->toDayDateTimeString() }}">{{ $m->created_at->diffForHumans() }}</td>
                            <td>
                                @if ($m->replied_at)<span class="badge-confirmed" title="{{ $m->replied_at->toDayDateTimeString() }}"><x-icon name="check" />Replied</span>
                                @elseif ($m->read_at)<span class="badge-pending">Read</span>
                                @else<span class="badge bg-danger-soft text-danger">New</span>@endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <button type="button" class="btn-primary !px-3 !py-1.5 text-xs" data-modal-open="reply-{{ $m->id }}">{{ $m->replied_at ? 'See reply' : 'Reply' }}</button>
                                <form method="POST" action="{{ route('admin.messages.read', $m) }}" class="inline">@csrf @method('PATCH')
                                    <button class="btn-secondary !px-3 !py-1.5 text-xs">{{ $m->read_at ? 'Mark unread' : 'Mark read' }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-muted">{{ array_filter($filters) ? 'No messages match these filters.' : 'No messages yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-admin.table-card>
        <div class="mt-8">{{ $messages->links() }}</div>
    </div>

    @foreach ($messages as $m)
        @php($modal = "reply-{$m->id}")
        <x-admin.modal :id="$modal" :title="'Reply to '.$m->name">
            <div class="rounded-2xl bg-surface-2 p-4 text-sm">
                <p class="text-xs text-muted">{{ $m->topicLabel() }} · {{ $m->created_at->toDayDateTimeString() }}</p>
                <p class="mt-2 whitespace-pre-line text-ink">{{ $m->message }}</p>
            </div>

            @if ($m->replied_at)
                <div class="mt-4 rounded-2xl bg-brand-soft p-4 text-sm">
                    <p class="text-xs text-muted">Replied {{ $m->replied_at->diffForHumans() }}@if ($m->repliedBy) by {{ $m->repliedBy->name }}@endif</p>
                    <p class="mt-2 whitespace-pre-line text-ink">{{ $m->reply }}</p>
                    @if ($m->whatsappReplyUrl())
                        <a href="{{ $m->whatsappReplyUrl() }}" target="_blank" rel="noopener" class="btn-secondary mt-3 !py-2 text-xs"><x-icon name="chat" />Send it on WhatsApp</a>
                    @endif
                </div>
            @endif

            <form id="{{ $modal }}-form" method="POST" action="{{ route('admin.messages.reply', $m) }}" class="mt-4 grid gap-2">
                @csrf
                <input type="hidden" name="_modal" value="{{ $modal }}">
                <label for="{{ $modal }}-text" class="label">{{ $m->replied_at ? 'Send another reply' : 'Your reply' }}</label>
                <textarea id="{{ $modal }}-text" name="reply" rows="6" maxlength="5000" required class="field">{{ old('_modal') === $modal ? old('reply') : '' }}</textarea>
                @if (old('_modal') === $modal)@error('reply')<p class="text-xs text-danger">{{ $message }}</p>@enderror @endif
                <p class="text-xs text-muted">
                    @if ($m->contactEmail())
                        Sent by email to {{ $m->contactEmail() }}.
                    @elseif ($m->whatsappReplyUrl())
                        {{ $m->name }} left a phone number, so the reply is saved here and you send it on WhatsApp.
                    @else
                        {{ $m->name }} didn’t leave an email or a phone number we can use, so the reply is only saved here.
                    @endif
                </p>
            </form>
            <x-slot:footer>
                <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                <button form="{{ $modal }}-form" class="btn-primary">{{ $m->contactEmail() ? 'Send reply' : 'Save reply' }}</button>
            </x-slot:footer>
        </x-admin.modal>
    @endforeach
</x-layouts.admin>
