# laravel-email-reset
Request to change the e-mail address of the Auth user, change it after moving to the specified URL of the confirmation e-mail.

[![Travis branch](https://img.shields.io/travis/rust-lang/rust/master.svg)](https://github.com/kaoken/laravel-email-reset)
[![composer version](https://img.shields.io/badge/version-1.0.2-blue.svg)](https://github.com/kaoken/laravel-email-reset)
[![licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://github.com/kaoken/laravel-email-reset)
[![laravel version](https://img.shields.io/badge/Laravel%20version-≧5.5-red.svg)](https://github.com/kaoken/laravel-email-reset)

__Table of content__

- [Install](#install)
- [Setting](#setting)
- [Event](#event)
- [License](#license)

## Install

**composer**:


```bash
composer require kaoken/laravel-email-reset
```



## Setting

### Add to **`config\app.php`** as follows:

```php
    'providers' => [
        ...
        // add
        Kaoken\LaravelMailReset\MailResetServiceProvider::class
    ],

    'aliases' => [
        ...
        // add
        'MailReset' => Kaoken\LaravelMailReset\Facades\MailReset::class
    ],
```

  
### Example of adding to **`config\auth.php`**
add `'email_reset' => 'users',`.
```php
[
    ...
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
        // 追加
        'email_reset' => 'users',
    ],
    ...
]
```  

- `model` is a user model class
- `email_confirmation` should modify the class derived from[Mailable](https://laravel.com/docs/5.5/mail) as necessary.
Used to send confirmation mail.
- `table` is the name of the table used for this service
- If `expire` does not manipulate X hours after registration, the 1st change email is deleted.

```php
    'email_resets' => [
        'users' => [
            'model' => App\User::class,
            'email_reset' => Kaoken\LaravelMailReset\Mail\MailResetConfirmationToUser::class,
            'table' => 'mail_reset_users',
            'expire' => 1,
        ]
    ],
```


### Command
```bash
php artisan vendor:publish --tag=mail-reset
```
After execution, the following directories and files are added.

* **`database`**
  * **`migrations`**
    * `2017_09_21_000001_create_mail_reset_users_table.php`
* **`resources`**
  * **`lang`**
    * **`en`**
      * `mail_reset.php`
    * **`ja`**
      * `mail_reset.php`
  * **`views`**
    * **`vendor`**
      * **`confirmation`**
        * **`mail`**
          * `confirmation.blade.php`
  * `complete.blade.blade.php`
  
  
       
### Migration
Migration file `2017_09_21_000001_create_mail_reset_users_table.php` should be modified as necessary.

```bash
php artisan migrate
```

### Add to kernel
Add it to the `schedule` method of `app\Console\Kernel.php`.  
This is used to delete users who passed 1 hour after 1st registration.

```php
    protected function schedule(Schedule $schedule)
    {
        ...
        $schedule->call(function(){
            MailReset::broker('users')->deleteUserAndToken();
        )->hourly();
    }
```

### E-Mail
In the configuration `config\auth.php` with the above setting,
`Kaoken\LaravelMailReset\Mail\MailResetConfirmationToUser::class` of `email_reset`
 is used as a confirmation email when changing mail.
The template is `views\vendor\mail_reset\mail\confirmation.blade.php`
Is used. Change according to the specifications of the application.
  
 
### controller
Example of changing e-mail address

 ```php
<?php
 namespace App\Http\Controllers;
 use Auth;
 use MailReset;
 use App\User;
 use App\Http\Controllers\Controller;
 use Illuminate\Support\Facades\Validator;
 use Kaoken\LaravelMailReset\Controllers\MailResetUsers;
 
 class MailResetController extends Controller
 {  
     use MailResetUsers;
     /**
      * use trit MailResetUsers
      * @var string
      */
     protected $broker = 'users';
 
     /**
      * Mail address change view
      * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
      */
     public function getChangeMail()
     {
         // 各自で用意する
         return view('change_email');
     }
     
     /**
      * Change user's email address
      * @param Request $request
      * @return \Illuminate\Http\JsonResponse|mixed
      */
     public function postChangeMail(Request $request)
     {
         $all = $request->only(['email']);
         $validator = Validator::make($all,[
             'email' => 'required|unique:users,email|max:255|email',
         ]);
 
         if ($validator->fails()) {
             return redirect('change_email')
                 ->withErrors($validator)
                 ->withInput();
         }
 
         switch ( $this->sendMailAddressChangeLink(Auth::guard('customer')->user()->id, $all['email']) ) {
             case MailReset::INVALID_USER:
                 redirect('first_register')
                     ->withErrors(['mail_reset'=>'Invalid user.']);
                 break;
             case MailReset::SAME_EMAIL_EXIST:
                 redirect('first_register')
                     ->withErrors(['mail_reset'=>'The same mail address already exists.']);
                 break;
             case MailReset::INVALID_CONFIRMATION:
             default:
                 redirect('first_register')
                     ->withErrors(['mail_reset'=>'An unexpected error occurred.']);
         }
         return redirect('change_email_ok');
     }
 }
 ```
Be sure to write `use MailResetUsers` and `$broker` in the class.  

### Route
From the above controller!

```php
Route::group([
        'middleware' => ['auth:user'],
    ],
    function(){
        Route::get('user/mail/reset', 'MailResetController@getChangeMail');
        Route::post('user/mail/reset', 'MailResetController@postChangeMail');
    }
);
Route::get('user/mail/reset/{id}/{email}/{token}', 'MailResetController@getChangeMailAddress');
```

## Events
See inside the `vendor\kaoken\laravel-email-reset\src\Events` directory!

#### `ChangedMailAddressEvent`
Called after the mail address has been completely changed.

#### `MailResetConfirmationEvent`
It is called after saving the change candidate of the mail address.




## License

[MIT](https://github.com/kaoken/laravel-email-reset/blob/master/LICENSE.txt)