<?php

namespace App\Livewire;

use App\Enums\ContactMessageStatus;
use App\Enums\ContactSubject;
use App\Mail\NewContactMessageMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Services\TurnstileVerifier;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ContactForm extends Component
{
    #[Validate(['required', 'string', 'max:120'])]
    public string $name = '';

    #[Validate(['required', 'email', 'max:255'])]
    public string $email = '';

    #[Validate(['required', 'string'])]
    public string $subject = '';

    #[Validate(['required', 'string', 'min:10', 'max:8000'])]
    public string $message = '';

    /** Honeypot — muss leer bleiben */
    public string $company_website = '';

    public string $turnstileToken = '';

    public bool $sent = false;

    public function mount(): void
    {
        $this->subject = ContactSubject::Other->value;
    }

    public function submit(): void
    {
        $this->validate();
        $this->validate([
            'subject' => ['required', \Illuminate\Validation\Rule::enum(ContactSubject::class)],
        ]);

        if ($this->company_website !== '') {
            $this->sent = true;
            $this->reset(['name', 'email', 'message', 'company_website', 'turnstileToken']);
            $this->subject = ContactSubject::Other->value;

            return;
        }

        if (! TurnstileVerifier::verify($this->turnstileToken, request()->ip())) {
            $this->addError('turnstileToken', 'Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden und erneut versuchen.');

            return;
        }

        $rateKey = 'contact-form:'.sha1((string) request()->ip());
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            $this->addError('message', 'Zu viele Anfragen. Bitte später erneut versuchen.');

            return;
        }
        RateLimiter::hit($rateKey, 3600);

        /** @var ContactSubject $subjectEnum */
        $subjectEnum = ContactSubject::from($this->subject);

        $record = ContactMessage::query()->create([
            'name' => $this->name,
            'email' => $this->email,
            'subject' => $subjectEnum,
            'message' => $this->message,
            'status' => ContactMessageStatus::New,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 2000),
        ]);

        $inbox = trim((string) Setting::get('shop_email', ''));
        if ($inbox !== '' && filter_var($inbox, FILTER_VALIDATE_EMAIL)) {
            Mail::to($inbox)->send(new NewContactMessageMail($record));
        }

        $this->sent = true;
        $this->reset(['name', 'email', 'message', 'company_website', 'turnstileToken']);
        $this->subject = ContactSubject::Other->value;
    }

    public function render()
    {
        return view('livewire.contact-form');
    }
}
