<?php

namespace Kaoken\LaravelMailReset\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class MailResetConfirmationToUser extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @var object
     */
    protected $user;
    /**
     * token
     * @var string
     */
    protected $token;
    /**
     * Completely registered URL
     * @var string
     */
    protected $email;

    /**
     * Create a new message instance.
     *
     * @param object $user User model derived from `Model` class
     * @param string $token token
     * @param string $newEMail new mail address
     */
    public function __construct($user, string $token, string $newEMail)
    {
        $this->user = $user;
        $this->token = $token;
        $this->email = $newEMail;
    }

    /**
     * Build the message.
     * @return $this
     */
    public function build()
    {
        $m = $this->text('vendor.mail_reset.mail.confirmation')
            ->subject(env('APP_NAME')." - ".__('mail_reset.email_confirmation_subject'))
            ->to($this->user->email, $this->user->name)
            ->with(['user'=>$this->user, 'token'=>$this->token, 'email'=>$this->email]);

        return $m;
    }
}