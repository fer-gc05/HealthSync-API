<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AppointmentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $reminderData;
    protected $recipientType;

    /**
     * Create a new message instance.
     */
    public function __construct(array $reminderData, string $recipientType = 'patient')
    {
        $this->reminderData = $reminderData;
        $this->recipientType = $recipientType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getSubject();

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->getView();

        return new Content(
            view: $view,
            with: [
                'appointment' => $this->reminderData['appointment'],
                'patient' => $this->reminderData['patient'],
                'doctor' => $this->reminderData['doctor'],
                'specialty' => $this->reminderData['specialty'],
                'reminder_type' => $this->reminderData['reminder_type'],
                'meeting_link' => $this->reminderData['meeting_link'] ?? null,
                'meeting_password' => $this->reminderData['meeting_password'] ?? null,
                'recipient_type' => $this->recipientType,
            ]
        );
    }

    /**
     * Get the subject based on reminder type and recipient
     */
    private function getSubject(): string
    {
        $appointment = $this->reminderData['appointment'];
        $reminderType = $this->reminderData['reminder_type'];

        $timeText = $this->getTimeText($reminderType);

        if ($this->recipientType === 'patient') {
            return "Recordatorio de cita médica - {$timeText}";
        } else {
            return "Recordatorio de consulta médica - {$timeText}";
        }
    }

    /**
     * Get the view based on recipient type
     */
    private function getView(): string
    {
        if ($this->recipientType === 'patient') {
            return 'emails.appointment-reminder-patient';
        } else {
            return 'emails.appointment-reminder-doctor';
        }
    }

    /**
     * Get time text for reminder type
     */
    private function getTimeText(string $reminderType): string
    {
        switch ($reminderType) {
            case '24h':
                return 'en 24 horas';
            case '2h':
                return 'en 2 horas';
            case '30min':
                return 'en 30 minutos';
            default:
                return 'próximamente';
        }
    }
}
