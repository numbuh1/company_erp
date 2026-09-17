<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class WeeklyApprovalReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User       $recipient,
        public Collection $pendingLeaves,
        public Collection $pendingOts,
    ) {}

    public function envelope(): Envelope
    {
        $total = $this->pendingLeaves->count() + $this->pendingOts->count();

        return new Envelope(
            subject: '[' . __('Reminder') . '] ' . __(':total requests pending approval', ['total' => $total]),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.weekly_reminder');
    }
}
