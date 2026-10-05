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
     * @param  \App\Models\LeaveRequest|\App\Models\OvertimeRequest|\App\Models\WfhRequest  $request
     * @param  string  $type   'leave' | 'ot' | 'wfh'
     * @param  User    $requester
     */
    public function __construct(
        public $request,
        public string $type,
        public User $requester,
    ) {}

    public function envelope(): Envelope
    {
        $label = match ($this->type) {
            'leave' => __('Leave Request'),
            'wfh'   => __('WFH Request'),
            default => __('OT Request'),
        };

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
