
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
        Schema::create('seller_men', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique(); 
            $table->timestamp('email_verified_at')->nullable(); 
            $table->string('password');
            $table->string('NationalNumber'); 
            $table->string('address'); 
            $table->string('gender')->nullable();
            $table->string('phone'); 
            $table->string('PhotoOfPersonalID')->nullable(); 
            $table->date('DateOfBirth')->nullable(); 
            $table->string('status')->nullable();
            $table->foreignId('role_id')->constrained("roles")->onUpdate('cascade')->onDelete('cascade');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seller_men');
    }
};