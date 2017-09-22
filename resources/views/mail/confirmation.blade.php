{{__('mail_reset.msg_change_email')}}

{{__('mail_reset.msg_move_url')}}
{{ secure_url('user/email/reset/'.$user->id.'/'.$email.'/'.$token.'/') }}

・{{__('mail_reset.msg_move_url')}}
・{{__('mail_reset.msg_user_delete')}}


※ {{__('mail_reset.msg_note')}}

──────────────────────────────────　
　Hoge
──────────────────────────────────　
web  : {{url('/')}}
email: hoge@hoge.com
──────────────────────────────────