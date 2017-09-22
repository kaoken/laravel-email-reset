<?php

namespace Kaoken\LaravelMailReset\Facades;

use Illuminate\Support\Facades\Facade;

class MailReset extends Facade
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
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'auth.mail_reset';
    }
}
