# laravel-email-reset
Authユーザーのメールアドレスを変更依頼をし、確認メールの変更先URLへ移動後変更する。

[![TeamCity (simple build status)](https://img.shields.io/teamcity/http/teamcity.jetbrains.com/s/bt345.svg)]()
[![composer version](https://img.shields.io/badge/version-0.0.0-blue.svg)](https://github.com/kaoken/laravel-email-reset)
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

または、`composer.json`へ追加

```json 
  "require": {
    ...
    "kaoken/laravel-email-reset":"^1.0"
  }
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
  
または、`composer.json`へ追加  
  
```js
{
    ...
    "extra": {
        "laravel": {
            "dont-discover": [
            ],
            "providers": [
                "Kaoken\LaravelMailReset\MailResetServiceProvider",
            ],
            "aliases": {
                "MailReset": "Kaoken\LaravelMailReset\Facades\MailReset"
            }
        }
    },
    ...
}
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

Authユーザーが`users`の場合(**必ず名前は、テーブル名にすること**)


- `model`は、ユーザーモデルクラス
- `path`は、トークを使用した登録時に使用するURLの途中パス(例：`http(s):://hoge.com/{path}/{email}/{token}`)
- `email_reset`は、[Mailable](https://readouble.com/laravel/5.5/ja/mail)で派生したクラスを必要に応じて変更すること。
確認メールを送るときに使用する。  
- `table`は、このサービスで使用するテーブル名
- `expire`は、登録後にX時間操作しない場合、仮登録したユーザーが削除される時間

```php
    'email_resets' => [
        'users' => [
            'model' => App\User::class,
            'path' => 'user/mail_reset/',
            'email_reset' => Kaoken\LaravelMailReset\Mail\MailResetMailToUser::class,
            'table' => 'mail_reset_users',
            'expire' => 24,
        ]
    ],
```

### コマンドの実行
```bash
php artisan vendor:publish --tag=confirmation
```
実行後、以下のディレクトリやファイルが追加される。   

* **`database`**
  * **`migrations`**
    * `2017_09_14_000001_create_confirmation_users_table.php`
* **`resources`**
  * **`lang`**
    * **`en`**
      * `confirmation.php`
    * **`ja`**
      * `confirmation.php`
  * **`views`**
    * **`vendor`**
      * **`confirmation`**
        * **`mail`**
          * `confirmation.blade.php`
          * `registration.blade.php`
  * `registration.blade.php`
     
### マイグレーション
マイグレーションファイル`2017_09_14_000001_create_confirmation_users_table.php`は、必要に応じて
追加修正すること。

```bash
php artisan migrate
```

### カーネルへ追加
`app\Console\Kernel.php`の`schedule`メソッドへ追加する。  
これは、仮登録後24時間過ぎたユーザーを削除するために使用する。
```php
    protected function schedule(Schedule $schedule)
    {
        ...
        App\Console\Kernel::schedule(Schedule $schedule){
            $schedule->call(function(){
                MailReset::broker('user')->deleteUserAndToken();
            )->hourly();
        }
    }
```

### `.env`
* `CONFIRMATION_FROM_EMAIL` は、返信先のメールアドレス。デフォルトで、デフォルトのメールアドレスになる。
* `CONFIRMATION_FROM_NAME` は、返信先の名前。デフォルトで`webmaster`になる。


### メール
上記設定のコンフィグ`config\auth.php`の場合、
`email_confirmation`の`Kaoken\LaravelMailReset\Mail\MailResetMailToUser`は、
仮登録時に確認メールとして使用する。テンプレートは、`views\vendor\confirmation\confirmation.blade.php`
を使用している。アプリの仕様に合わせて変更すること。
  
`email_registration`の`Kaoken\LaravelMailReset\Mail\RegistrationMailToUser`は、
本登録をしたことを知らせるメールとして使用する。テンプレートは、`views\vendor\confirmation\registration.blade.php`
を使用している。アプリの仕様に合わせて変更すること。




### コントローラー
仮登録、本登録、ログインの例
```php
<?php
namespace App\Http\Controllers;
use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Kaoken\LaravelMailReset\Controllers\MailResetUser;

class MailResetController extends Controller
{
    use AuthenticatesUsers, MailResetUser;
    
    /**
     * AuthenticatesUsers トレイトで使用する 
     * @var string
     */
    protected $broker = 'users';

    /**
     * 仮登録画面
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getFirstRegister()
    {
        // 各自で用意する
        return view('first_step_register');
    }
    
    /**
     * ユーザーの仮登録をする
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function postFirstRegister(Request $request)
    {
        $all = $request->only(['name', 'email', 'password']);
        $validator = Validator::make($all,[
            'name' => 'required|max:24',
            'email' => 'required|unique:users,email|max:255|email',
            'password' => 'required|between:6,32'
        ]);

        if ($validator->fails()) {
            return redirect('first_register')
                ->withErrors($validator)
                ->withInput();
        }
        $all['password'] = bcrypt($all['password']);

        // 仮登録をする
        if ( !$this->createUserAndSendMailResetLink($all) ) {
            return redirect('first_register')
                            ->withErrors(['confirmation'=>'仮登録に失敗しました。']);
        }
        // 仮登録を知らせるページへ移動
        return redirect('first_register_ok');
    }
}
```
`$broker`は、必ず記述すること。

### ルート
上記コントローラより

```php
Route::get('user/mail_reset', 'AuthController@getFirstRegister');
Route::post('register', 'AuthController@postFirstRegister');
Route::get('register/{email}/{token}', 'AuthController@getCompleteRegistration');
```
### Auth Model
Authユーザーモデルの例  
`Kaoken\LaravelMailReset\HasMailReset;`を追加する。
```php
<?php

namespace App;
use Kaoken\LaravelMailReset\HasMailReset;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasMailReset;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

}
```

`confirmed()`メソッドを使用することにより、本登録済みのユーザーかを判定する。  
`confirmed()`で、ソーシャルログインなどで、追加修正してログイン判定できるようにすると良い。

## イベント
`vendor\kaoken\laravel-confirmation-email\src\Events`ディレクトリ内を参照!  

#### `BeforeCreateUserEvent`
ユーザーが作成される前に呼び出される。  
**注意**： このイベントが呼び出されると、Authユーザー作成に関連するDBトランザクションが進行中。  
リスナーで例外を作成すると、ターゲットのAuthユーザー作成が直ちにロールバックされる。  

#### `BeforeDeleteUsersEvent`
期限切れのユーザーを削除する前に呼び出される。  
これは、`MailReset::broker('hoge')->deleteUserAndToken();`のメソッドの引数を`true`に`deleteUserAndToken(true)`した
場合のみ呼び出される。  
**注意**： このイベントが呼び出されると、期限切れAuthユーザー削除に関連するDBトランザクションが進行中。  
リスナーで例外を作成すると、ターゲットのAuthユーザー削除が直ちにロールバックされる。  


#### `CreatedUserEvent`
Authユーザーが作成された後に呼び出される。  

#### `MailResetEvent`
確認メールを送信した後、認証ユーザーが作成されて呼び出される。  

#### `RegistrationEvent`
Authユーザーが本登録した後に呼び出される。  



## ライセンス

[MIT](https://github.com/kaoken/laravel-email-reset/blob/master/LICENSE.txt)