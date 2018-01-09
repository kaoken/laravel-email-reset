# laravel-email-reset
Authユーザーのメールアドレスを変更依頼をし、確認メールの変更先URLへ移動後変更する。

[![Travis branch](https://img.shields.io/travis/rust-lang/rust/master.svg)](https://github.com/kaoken/laravel-email-reset)
[![composer version](https://img.shields.io/badge/version-1.0.1-blue.svg)](https://github.com/kaoken/laravel-email-reset)
[![licence](https://img.shields.io/badge/licence-MIT-blue.svg)](https://github.com/kaoken/laravel-email-reset)
[![laravel version](https://img.shields.io/badge/Laravel%20version-≧5.5-red.svg)](https://github.com/kaoken/laravel-email-reset)


__コンテンツの一覧__

- [インストール](#インストール)
- [設定](#設定)
- [イベント](#イベント)
- [ライセンス](#ライセンス)

## インストール

**composer**:

```bash
composer install kaoken/laravel-email-reset
```


## 設定

### **`config\app.php`** に以下のように追加：

```php
    'providers' => [
        ...
        // 追加
        Kaoken\LaravelMailReset\MailResetServiceProvider::class
    ],

    'aliases' => [
        ...
        // 追加
        'MailReset' => Kaoken\LaravelMailReset\Facades\MailReset::class
    ],
```

  
### **`config\auth.php`**へ追加する例
`'email_reset' => 'users',`を追加する。
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

- `model`は、ユーザーモデルクラス
- `email_reset`は、[Mailable](https://readouble.com/laravel/5.5/ja/mail)で派生したクラスを必要に応じて変更すること。
確認メールを送るときに使用する。  
- `table`は、このサービスで使用するテーブル名
- `expire`は、登録後にX時間操作しない場合、変更メールアドレス候補が削除される時間

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

### コマンドの実行
```bash
php artisan vendor:publish --tag=mail-reset
```
実行後、以下のディレクトリやファイルが追加される。   

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
     
### マイグレーション
マイグレーションファイル`2017_09_21_000001_create_mail_reset_users_table.php`は、必要に応じて
追加修正すること。

```bash
php artisan migrate
```

### カーネルへ追加
`app\Console\Kernel.php`の`schedule`メソッドへ追加する。  
これは、メール変更後24時間過ぎたユーザーを削除するために使用する。
```php
    protected function schedule(Schedule $schedule)
    {
        ...
        App\Console\Kernel::schedule(Schedule $schedule){
            $schedule->call(function(){
                MailReset::broker('users')->deleteUserAndToken();
            )->hourly();
        }
    }
```

### メール
上記設定のコンフィグ`config\auth.php`の場合、
`email_reset`の`Kaoken\LaravelMailReset\Mail\MailResetConfirmationToUser::class`は、
メール変更時に確認メールとして使用する。テンプレートは、`views\vendor\mail_reset\mail\confirmation.blade.php`
を使用している。アプリの仕様に合わせて変更すること。




### コントローラー
メールアドレス変更の例
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
     * MailResetUsers トレイトで使用する 
     * @var string
     */
    protected $broker = 'users';

    /**
     * メールアドレス変更画面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getChangeMail()
    {
        // 各自で用意する
        return view('change_email');
    }
    
    /**
     * ユーザーのメールアドレスを変更する
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
                    ->withErrors(['mail_reset'=>'無効なユーザーです。']);
                break;
            case MailReset::SAME_EMAIL_EXIST:
                redirect('first_register')
                    ->withErrors(['mail_reset'=>'既に同じメールアドレスが存在します。']);
                break;
            case MailReset::INVALID_CONFIRMATION:
            default:
                redirect('first_register')
                    ->withErrors(['mail_reset'=>'予期せぬエラーが発生しました。']);
        }
        return redirect('change_email_ok');
    }
}
```
クラス内に`use MailResetUsers`と`$broker`は、必ず記述すること。

### ルート
上記コントローラより

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

## イベント
`vendor\kaoken\laravel-email-reset\src\Events`ディレクトリ内を参照!  

#### `ChangedMailAddressEvent`
メールアドレスが完全に変更された後に呼び出される。  

#### `MailResetConfirmationEvent`
メールアドレスの変更候補を保存後呼び出される。  




## ライセンス

[MIT](https://github.com/kaoken/laravel-email-reset/blob/master/LICENSE.txt)