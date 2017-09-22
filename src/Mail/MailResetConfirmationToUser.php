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
    protected $model;
    /**
     * token
     * @var string
     */
    protected $token;
    /**
     * Completely registered URL
     * @var string
     */
    protected $registerUrl;

    /**
     * Create a new message instance.
     *
     * @param object $model User model derived from `Model` class
     * @param string $token token
     * @param string $registerUrl Completely registered URL
     */
    public function __construct($model, string $token, string $registerUrl)
    {
        $this->model = $model;
        $this->token = $token;
        $this->registerUrl = $registerUrl;
    }

    /**
     * Build the message.
     * @return $this
     */
    public function build()
    {
        $m = $this->text('vendor.mail_reset.mail.confirmation')
            ->subject(env('APP_NAME')." - ".__('mail_reset.email_confirmation_subject'))
            ->to($this->model->email, $this->model->name)
            ->with(['user'=>$this->model, 'token'=>$this->token, 'registerUrl'=>$this->registerUrl]);

        return $m;
    }
}