<?php

namespace App\Http\Controllers;

use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function create(Request $request): View
    {
        return view('contact', ['topic' => $request->query('topic')]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: real people never fill the hidden "website" field.
        if ($request->filled('website')) {
            return redirect()->route('contact')->with('sent', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'contact' => ['required', 'string', 'max:190'],
            'topic' => ['required', 'in:'.implode(',', array_keys(ContactMessage::TOPICS))],
            'facility_name' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [
            'contact.required' => 'Please give us an email or phone number so we can reply.',
            'message.min' => 'Please tell us a little more (at least 10 characters).',
        ]);

        ContactMessage::create([...$data, 'ip' => $request->ip()]);

        return redirect()->route('contact')->with('sent', true);
    }
}
