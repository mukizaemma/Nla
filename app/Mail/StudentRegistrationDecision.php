<?php

namespace App\Mail;

use App\Models\StudentRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentRegistrationDecision extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public StudentRegistration $registration,
        public string $schoolName,
    ) {}

    public function envelope(): Envelope
    {
        $verb = $this->registration->status === StudentRegistration::STATUS_CONFIRMED
            ? 'confirmed'
            : 'update';

        return new Envelope(
            subject: 'Registration ' . $verb . ' – ' . $this->registration->student_full_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-registration-decision',
        );
    }
}
