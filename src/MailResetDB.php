<?php

namespace Kaoken\LaravelMailReset;

use Kaoken\LaravelMailReset\Events\ChangedMailAddressEvent;
use Log;
use \Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Database\ConnectionInterface;

class MailResetDB
{
    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The token database table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Auth user class derived from Mode
     * Example: `\App\User::class`
     * @var string
     */
    protected $model;

    /**
     * The hashing key.
     *
     * @var string
     */
    protected $hashKey;

    /**
     * The number of hours a token should last.
     *
     * @var int
     */
    protected $expires;

    /**
     * Create a new token repository instance.
     *
     * @param  ConnectionInterface  $connection
     * @param  string  $table   The token database table name.
     * @param  string  $model   Auth user class derived from Mode.   {Model}::class
     * @param  string  $hashKey The Hash key
     * @param  int     $expires Unit is hour. Default 24 hours.
     */
    public function __construct(ConnectionInterface $connection,
                                $table, $model, $hashKey, $expires = 24)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->model = $model;
        $this->expires = $expires;
    }

    /**
     * Create a record for the new token.
     *
     * @param  int    $userId
     * @param  string $newEmail
     * @return string
     */
    public function create($userId, $newEmail)
    {
        $this->connection->beginTransaction();
        try{
            $user = ($this->model)::where('id',$userId)
                ->lockForUpdate()
                ->first();
            if( is_null($user))
                throw new Exception(IMailResetBroker::INVALID_USER,422);
            if( ($this->model)::where('email',$newEmail)->count() > 0)
                throw new Exception(IMailResetBroker::SAME_EMAIL_EXIST,422);
        }catch(Exception $e){
            $this->connection->rollback();
            if( $e->getCode() === 422 ){
                return $e->getMessage();
            }else{
                Log::error("MailResetDB::create user create Fail!!",$this->err_context($e));
            }
            return IMailResetBroker::INVALID_USER;
        }

        try{
            $this->deleteExisting($userId);
            $token = $this->createNewToken();

            $this->getTable()->insert($this->getPayload($userId, $newEmail, $token));
        }catch(Exception $e){
            $this->connection->rollback();
            Log::error("MailResetDB::create token insert Fail!!",$this->err_context($e));
            return IMailResetBroker::INVALID_CONFIRMATION;
        }

        $this->connection->commit();
        return $token;
    }

    /**
     * Does the specified ID, mail address, and token exist?
     * @param int    $userId
     * @param string $email mail address
     * @param string $token token
     * @return bool Returns true if it exists.
     */
    public function existenceMailAddress($userId, $email, $token)
    {
        $val = $this->getTable()->where('id',$userId)
            ->where('email',$email)
            ->where('token', $token)
            ->count();
        return $val === 1;
    }

    /**
     * Change the mail address from the specified user ID
     * @param int    $userId
     * @param string $email
     * @param string $token
     * @return string.
     */
    public function userChangeMailAddress($userId, $email, $token)
    {
        $oldEmail = "";
        try{
            $this->connection->beginTransaction();

            $user = ($this->model)::lockForUpdate()->find($userId);
            if( is_null($user) ) throw new \Exception('Nonexistent user.',422);

            if( !$this->existenceMailAddress($userId, $email, $token) )
                throw new \Exception(IMailResetBroker::INVALID_USER,422);
            $this->deleteExisting($userId);

            // change email
            $oldEmail = $user->email;
            $user->email = $email;
            $user->save();
        }
        catch (\Exception $e){
            $this->connection->rollback();
            Log::error("Email address change error:",$this->err_context($e));
            return $e->getMessage();
        }
        $this->connection->commit();

        event(new ChangedMailAddressEvent($user, $oldEmail, $email));
        return IMailResetBroker::CHANGE_EMAIL;
    }
    /**
     * Delete all existing reset tokens from the database.
     *
     * @param  integer  $userId
     * @return int
     */
    public function deleteExisting($userId)
    {
        return $this->getTable()->where('id', $userId)->delete();
    }

    /**
     * Build the record payload for the table.
     *
     * @param  integer $userId
     * @param  string  $email
     * @param  string  $token
     * @return array
     */
    protected function getPayload($userId, $email, $token)
    {
        return ['id' => $userId, 'email' => $email, 'token' => $token, 'created_at' => Carbon::now()];
    }

    /**
     * Delete expired tokens.
     * @note Let's add it to the kernel method with reference to the example below.
     *
     *  App\Console\Kernel::schedule(Schedule $schedule){
     *      $schedule->call(function(){
     *          MailReset::broker('users')->deleteUserAndToken();
     *      )->hourly();
     *  }
     *
     *
     * @return int Number of deleted records
     */
    public function deleteUserAndToken()
    {
        $ret = 0;
        try{
            $this->connection->beginTransaction();

            $expiredAt = Carbon::now()->subHour($this->expires);

            $this->getTable()
                ->lockForUpdate()
                ->where('created_at', '<', $expiredAt)
                ->delete();
        }catch(Exception $e){
            $this->connection->rollback();
            Log::error("MailResetDB::deleteUserAndToken Fail!!",$this->err_context($e));
            return $ret;
        }
        $this->connection->commit();
        return $ret;
    }

    /**
     * @param int $userId
     * @return null|Model
     */
    public function getUser($userId)
    {
        return ($this->model)::where('id',$userId)->first();
    }

    /**
     * Is there a mail address?
     * @param string $email
     * @return bool Returns true if it exists.
     */
    public function existenceEmail($email)
    {
        return $this->getTable()
            ->where('email', '=', $email)
            ->count() > 0;
    }
    /**
     * Contextification of error messages
     * @param Exception $e
     * @return array
     */
    public function err_context(\Exception $e)
    {
        return ["msg"=>$e->getMessage(),
            "file"=>$e->getFile(),
            "line"=>$e->getLine(),
            "code"=>$e->getCode(),
        ];
    }
    /**
     * Create a new token for the user.
     *
     * @return string
     */
    public function createNewToken()
    {
        return hash_hmac('sha256', Str::random(40), $this->hashKey);
    }

    /**
     * Get the database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Begin a new database query against the table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function getTable()
    {
        return $this->connection->table($this->table);
    }
}
