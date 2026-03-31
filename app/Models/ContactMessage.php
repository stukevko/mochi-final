<?php

namespace App\Models;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'email',
        'subject',
        'message',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'subject' => ContactSubject::class,
            'status' => ContactMessageStatus::class,
        ];
    }

    public static function openCount(): int
    {
        return self::query()
            ->whereNot('status', ContactMessageStatus::Done)
            ->count();
    }
}
