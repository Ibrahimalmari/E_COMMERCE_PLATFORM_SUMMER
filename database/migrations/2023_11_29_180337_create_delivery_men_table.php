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
        Schema::create('delivery_men', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->date('joining_date');
            $table->enum('status', [
                'متصل',
                'غير متصل'
            ])->default('غير متصل');          
            $table->string('PhotoOfPersonalID')->nullable(); 
            $table->string('vehicle_image')->nullable();
            $table->string('license_image')->nullable(); 
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_number')->nullable();    
            $table->string('NationalNumber')->nullable(); 
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
        Schema::dropIfExists('delivery_men');
    }
};
