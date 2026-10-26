<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public const TOPICS = [
        'question' => 'A general question',
        'wrong-info' => 'Some information is wrong',
        'add-facility' => 'Add or update my facility',
        'partnership' => 'Partnership or press',
    ];

    protected $fillable = ['name', 'contact', 'topic', 'facility_name', 'message', 'ip', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function topicLabel(): string
    {
        return self::TOPICS[$this->topic] ?? $this->topic;
    }
}
