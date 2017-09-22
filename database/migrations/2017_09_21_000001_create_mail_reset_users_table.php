<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailResetUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !Schema::hasTable('mail_reset_users') ) {
            Schema::create('mail_reset_users', function (Blueprint $table) {
                $table->unsignedInteger('id')->primary();
                $table->string('email')->unique();
                $table->string('token');
                $table->timestamp('created_at');

                // users
//                $table->foreign('id')
//                    ->references('id')->on('users')
//                    ->onDelete('cascade')
//                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_reset_users');
    }

}
