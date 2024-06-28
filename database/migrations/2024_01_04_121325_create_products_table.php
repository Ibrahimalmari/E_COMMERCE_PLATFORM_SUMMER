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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->text('name')->nullable();
            $table->string('description')->nullable();
            $table->double('price')->nullable();
            $table->text('images')->nullable();
            $table->integer('quantity')->nullable();    
            $table->foreignId('category_id')->nullable()->constrained("categories")->onUpdate('cascade')->onDelete('set null'); 
            $table->foreignId('branch_id')->nullable()->constrained("branches")->onUpdate('cascade')->onDelete('set null'); 
            $table->foreignId('store_id')->constrained('stores')->onUpdate('cascade')->onDelete('cascade');
            $table->string('status')->nullable();
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
        Schema::dropIfExists('products');
    }
};