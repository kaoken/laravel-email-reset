<?php
namespace Kaoken\LaravelMailReset\Controllers;

use MailReset;
use \Illuminate\Http\Request;

trait MailResetTrait
{
    /**
     * Send a confirmation link to the user.
     * At the same time, a change mail address and token record is also created.
     *
     * @param  int    $userId   Auth user id
     * @param  string $newEmail Change new mail address
     * @return string
     */
    protected function sendMailAddressChangeLink($userId, $newEmail)
    {
        $broker = null;
        if( method_exists($this, 'broker') )
            $broker = $this->broker;

        $response = MailReset::broker($this->broker)
            ->sendMailAddressChangeLink($userId, $newEmail);
        //MailReset::CHANGE_LINK_SENT
        return $response;
    }

    /**
     * View name to notify of change of e-mail address
     * @return string
     */
    protected function mailResetCompleteView()
    {
        return "vendor.mail_reset.complete";
    }

    /**
     * View name to notify of change of e-mail address
     * @return string
     */
    protected function mailReset404View()
    {
        return "404";
    }
    /**
     * 本登録処理
     * @param Request $request
     * @param string $email
     * @param string $token
     * @return \Illuminate\Http\Response
     */
    public function getChangeMailAddress(Request $request, $userId, $email, $token)
    {
        if( !($email == "" || $token == "") ){
            $broker = null;
            if( method_exists($this, 'broker') )
                $broker = $this->broker;

            $obj = MailReset::broker($broker);
            switch ($obj->userChangeMailAddress($userId, $email, $token)){
                case MailReset::CHANGE_EMAIL:
                    return response()->view($this->mailResetCompleteView());
            }
        }
        return response()->view($this->mailReset404View(), [], 404);
    }
}