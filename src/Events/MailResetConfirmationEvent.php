<?php
/**
 * Called after a confirmation email of mail reset
 */
namespace Kaoken\LaravelMailReset\Events;

use Illuminate\Queue\SerializesModels;

class MailResetConfirmationEvent
{
    use SerializesModels;
    /**
     * Auth user Model
     * @var object
     */
    public $user;

    /**
     * token
     * @var string
     */
    public $token;
    /**
     * Create a new event instance.
     *
     * @param $user Auth user Model
     */
    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
