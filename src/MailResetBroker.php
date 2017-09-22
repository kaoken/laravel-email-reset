<?php

namespace Kaoken\LaravelMailReset;

use Kaoken\LaravelMailReset\Events\MailResetConfirmationEvent;
use Illuminate\Mail\Mailer;

class MailResetBroker implements IMailResetBroker
{

    /**
     * ConfirmationDB instance.
     *
     * @var \Kaoken\LaravelConfirmation\ConfirmationDB
     */
    protected $db;
    /**
     * Middle path of URL
     *
     * @var string
     */
    protected $path;
    /**
     * User model
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Mailer instance.
     *
     * @var \Illuminate\Contracts\Mail\Mailer
     */
    protected $mailer;

    /**
     * It is a confirmation mail link.
     *
     * @var string
     */
    protected $emailConfirmationClass;


    /**
     * Create a new email reset broker instance.
     * @param  MailResetDB $db
     * @param  string $model
     * @param  string $path
     * @param  Mailer  $mailer
     * @param  string  $emailConfirmationClass
     */
    public function __construct(
        MailResetDB $db,
        string $model,
        string $path,
        Mailer $mailer,
        string $emailConfirmationClass)
    {
        $this->db = $db;
        $this->path = $path;
        $this->model = $model;
        $this->mailer = $mailer;
        $this->emailConfirmationClass = $emailConfirmationClass;
    }

    /**
     * Send a confirmation link to the user.
     * At the same time, a change mail address and token record is also created.
     *
     * @param  int    $userId   Auth user id
     * @param  string $newEmail Change new mail address
     * @return string
     */
    public function sendMailAddressChangeLink($userId, $newEmail)
    {
        switch (($token = $this->db->create($userId, $newEmail))) {
            case static::INVALID_USER:
            case static::SAME_EMAIL_EXIST:
            case static::INVALID_CONFIRMATION:
                return $token;
        }

        $user = $this->db->getUser($userId);
        $this->emailConfirmationLink($user, $token);
        event(new MailResetConfirmationEvent($user, $token));

        return static::CHANGE_LINK_SENT;
    }


    /**
     * Send the link in the confirmation reset by e-mail.
     *
     * @param  object  $user
     * @param  string  $token
     * @return void
     */
    protected function emailConfirmationLink($user, $token)
    {
        $class = $this->emailConfirmationClass;
        $this->mailer->send(new $class($user, $token, url($this->path.urlencode($user->email)."/".$token)));
    }


    /**
     * Does the specified ID, mail address, and token exist?
     * @param int    $userId
     * @param string $email
     * @param string $token
     * @return bool Returns true if it exists.
     */
    public function existenceMailAddress($userId, $email, $token)
    {
        return $this->db->existenceMailAddress($userId, $email, $token);
    }

    /**
     * Change the mail address from the specified user ID
     * @param int    $userId
     * @param string $newEmail
     * @param string $token
     * @return Returns true if it succeeds.
     * @throws \Exception
     */
    public function userChangeMailAddress($userId, $newEmail, $token)
    {
        return $this->db->userChangeMailAddress($userId, $newEmail, $token);
    }

    /**
     * Delete expired tokens
     * @return int Number of deleted tokens
     */
    public function deleteUserAndToken()
    {
        return $this->db->deleteUserAndToken();
    }
}
