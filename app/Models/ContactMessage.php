<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A message from the contact page. `contact` is the email or phone number the sender left; `reply` is the team's answer. */
class ContactMessage extends Model
{
    public const TOPICS = [
        'question' => 'A general question',
        'wrong-info' => 'Some information is wrong',
        'add-facility' => 'Add or update my facility',
        'partnership' => 'Partnership or press',
    ];

    protected $fillable = ['name', 'contact', 'topic', 'facility_name', 'message', 'ip', 'read_at', 'reply', 'replied_at', 'replied_by'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'replied_at' => 'datetime'];
    }

    public function repliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by');
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? $this->topic;
    }

    /** The sender's email address, when that is what they left. */
    public function contactEmail(): ?string
    {
        return filter_var($this->contact, FILTER_VALIDATE_EMAIL) ?: null;
    }

    /** A WhatsApp chat with the sender, the reply already typed in; null unless they left a phone number. */
    public function whatsappReplyUrl(): ?string
    {
        if ($this->contactEmail()) {
            return null;
        }
        $phone = str_starts_with(trim($this->contact), '+')
            ? preg_replace('/[^\d+]/', '', $this->contact)
            : PhoneNumber::format(PhoneNumber::DEFAULT_COUNTRY, $this->contact);

        return $phone ? 'https://wa.me/'.ltrim($phone, '+').($this->reply ? '?text='.rawurlencode($this->reply) : '') : null;
    }
}
