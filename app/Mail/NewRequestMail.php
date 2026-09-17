<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  \App\Models\LeaveRequest|\App\Models\OvertimeRequest  $request
     * @param  string  $type   'leave' | 'ot'
     * @param  User    $requester
     */
    public function __construct(
        public $request,
        public string $type,
        public User $requester,
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->type === 'leave' ? __('Leave Request') : __('OT Request');

        return new Envelope(
            subject: "[{$label}] {$this->requester->name} — "
                . $this->request->start_at->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new_request');
    }
}
