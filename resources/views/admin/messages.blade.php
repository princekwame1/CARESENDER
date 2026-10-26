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
                            <td>@if ($m->read_at)<span class="badge-pending">Read</span>@else<span class="badge bg-danger-soft text-danger">New</span>@endif</td>
                            <td class="whitespace-nowrap text-right">
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
</x-layouts.admin>
