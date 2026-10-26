<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'topic' => ['nullable', 'in:'.implode(',', array_keys(ContactMessage::TOPICS))],
            'status' => ['nullable', 'in:new,read'],
        ]);

        $messages = ContactMessage::query()
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('contact', 'like', $like)->orWhere('facility_name', 'like', $like)->orWhere('message', 'like', $like));
            })
            ->when($filters['topic'] ?? null, fn ($query, string $topic) => $query->where('topic', $topic))
            ->when(($filters['status'] ?? null) === 'new', fn ($query) => $query->whereNull('read_at'))
            ->when(($filters['status'] ?? null) === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.messages', compact('messages', 'filters'));
    }

    public function toggleRead(ContactMessage $message): RedirectResponse
    {
        $message->update(['read_at' => $message->read_at ? null : now()]);

        return back();
    }
}
