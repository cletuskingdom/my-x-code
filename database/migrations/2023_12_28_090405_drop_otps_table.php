<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('otps', function (Blueprint $table) {
            Schema::dropIfExists('otps');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('otps', function (Blueprint $table) {
            $table->id();
            $table->string('otp');
            $table->unsignedBigInteger('otpable_id');
            $table->string('otpable_type');
            $table->text('purpose')->default('email_verification');
            $table->boolean('valid')->default(true);
            $table->timestamps();
        });
    }
};
