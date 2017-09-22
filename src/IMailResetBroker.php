<?php

namespace Kaoken\LaravelMailReset;

use Closure;

interface IMailResetBroker
{
    /**
     * Successfully sent
     *
     * @var string
     */
    const CHANGE_LINK_SENT = 'mail_reset.sent';

    /**
     * Successful email change\
     *
     * @var string
     */
    const CHANGE_EMAIL = 'mail_reset.change_email';
    /**
     * The same mail address already exists.
     * @var string
     */
    const SAME_EMAIL_EXIST= 'mail_reset.same_email_exist';
   /**
     * Invalid user
     *
     * @var string
     */
    const INVALID_USER = 'mail_reset.user';

    /**
     * Constant representing an invalid confirmation.
     *
     * @var string
     */
    const INVALID_CONFIRMATION = 'mail_reset.confirmation';

    /**
     * Constant representing an invalid token.
     *
     * @var string
     */
    const INVALID_TOKEN = 'mail_reset.token';

    /**
     * Send a confirmation link to the user.
     * At the same time, a change mail address and token record is also created.
     *
     * @param  int    $userId   Auth user id
     * @param  string $newEmail Change new mail address
     * @return string
     */
    public function sendMailAddressChangeLink($userId, $newEmail);

    /**
     * Does the specified ID, mail address, and token exist?
     * @param int    $userId
     * @param string $email
     * @param string $token
     * @return bool Returns true if it exists.
     */
    public function existenceMailAddress($userId, $email, $token);

    /**
     * Change the mail address from the specified user ID
     * @param int    $userId
     * @param string $email
     * @param string $token
     * @return Returns true if it succeeds.
     * @throws \Exception
     */
    public function userChangeMailAddress($userId, $email, $token);

    /**
     * Delete expired tokens
     * @return int Number of deleted tokens
     */
    public function deleteUserAndToken();
}
