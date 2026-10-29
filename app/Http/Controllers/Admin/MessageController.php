<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ContactReply;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class MessageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'topic' => ['nullable', 'in:'.implode(',', array_keys(ContactMessage::TOPICS))],
            'status' => ['nullable', 'in:new,read,unanswered,answered'],
        ]);

        $messages = ContactMessage::query()
            ->with('repliedBy:id,name')
            ->when($filters['q'] ?? null, function ($query, string $term) {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('contact', 'like', $like)->orWhere('facility_name', 'like', $like)->orWhere('message', 'like', $like));
            })
            ->when($filters['topic'] ?? null, fn ($query, string $topic) => $query->where('topic', $topic))
            ->when(($filters['status'] ?? null) === 'new', fn ($query) => $query->whereNull('read_at'))
            ->when(($filters['status'] ?? null) === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->when(($filters['status'] ?? null) === 'unanswered', fn ($query) => $query->whereNull('replied_at'))
            ->when(($filters['status'] ?? null) === 'answered', fn ($query) => $query->whereNotNull('replied_at'))
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

    /**
     * Answers a message. A sender who left an email gets the reply by email straight away; for a phone number the
     * reply is saved and the team sends it on WhatsApp with the link shown on the message.
     */
    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $data = $request->validate(['reply' => ['required', 'string', 'max:5000']], [
            'reply.required' => 'Write your reply first.',
        ]);

        $message->fill(['reply' => $data['reply']]);
        $email = $message->contactEmail();
        if ($email) {
            try {
                Mail::to($email, $message->name)->send(new ContactReply($message));
            } catch (Throwable $exception) {
                report($exception);

                return back()->withInput()->withErrors(['reply' => 'The email couldn’t be sent. Please try again in a moment.']);
            }
        }

        $message->forceFill(['replied_at' => now(), 'replied_by' => $request->user()->id, 'read_at' => $message->read_at ?? now()])->save();

        return back()->with('status', $email
            ? "Reply sent to {$email}."
            : 'Reply saved. Open WhatsApp from the message to send it.');
    }
}
