<?php
/**
 * Called after changing mail address
 */
namespace Kaoken\LaravelMailReset\Events;

use Illuminate\Queue\SerializesModels;

class ChangedMailAddressEvent
{
    use SerializesModels;
    /**
     * Auth user Model
     * @var object
     */
    public $user;

    /**
     * old email
     * @var string
     */
    public $oldEmail;
    /**
     * new email
     * @var string
     */
    public $newEmail;

    /**
     * Create a new event instance.
     *
     * @param object $user     Auth user Model
     * @param string $oldEmail old email
     * @param string $newEmail new email
     */
    public function __construct($user, $oldEmail, $newEmail)
    {
        $this->user = $user;
        $this->oldEmail = $oldEmail;
        $this->newEmail = $newEmail;
    }
}
