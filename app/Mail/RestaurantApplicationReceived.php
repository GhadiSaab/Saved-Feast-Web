<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RestaurantApplicationReceived extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The application data.
     *
     * @var array
     */
    public $applicationData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $applicationData)
    {
        $this->applicationData = $applicationData;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'New Restaurant Partner Application - SavedFeast',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        // Use the Markdown view created by the make:mail command
        return new Content(
            markdown: 'emails.restaurant.application_received',
            with: [ // Pass data to the view
                'data' => $this->applicationData,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
