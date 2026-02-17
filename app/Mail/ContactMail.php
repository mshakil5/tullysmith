<?php

namespace App\Mail;

use App\Models\Contact;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\CompanyDetails;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function build()
    {
        return $this->subject('New Contact')
                    ->view('emails.contact')
                    ->with([
                        'first_name' => $this->contact->first_name,
                        'last_name'  => $this->contact->last_name,
                        'email'      => $this->contact->email,
                        'phone'      => $this->contact->phone,
                        'subjectText'    => $this->contact->subject ?? 'New query from website',
                        'contactMessage'    => $this->contact->message,
                        'mailFooter' => CompanyDetails::select('mail_footer')->first()->mail_footer ?? '',
                    ]);
    }
}