<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendQuotationMailable extends Mailable
{
    use Queueable, SerializesModels;
    public $quotation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($quotation)
    {
        $this->quotation = $quotation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Tu cotización Lanson Shades No. '.$this->quotation['id'].' está aquí.')
        ->view('mails.sendQuotation')
        ;
    }

    // public function attachments(): array
    // {
    //     return [
    //         Attachment::fromStorageDisk('s3', '/path/to/file')
    //                 ->as('name.pdf')
    //                 ->withMime('application/pdf'),
    //     ];
    // }
}
