<?php

// app/Mail/AccountCreatedMail.php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\AuthenticationAccount;

class AccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $account;
    public $temporaryPassword;

    public function __construct(AuthenticationAccount $account, $temporaryPassword)
    {
        $this->account = $account;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function build()
    {
        return $this->subject('Your New Account Details')
                    ->view('emails.account_created')
                    ->with([
                        'email' => $this->account->email,
                        'temporaryPassword' => $this->temporaryPassword
                    ]);
    }
}
