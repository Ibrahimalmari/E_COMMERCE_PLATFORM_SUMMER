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
        Schema::create('delivery_men_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_men_id')->constrained('delivery_men')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('status', ['مقبول', 'مرفوض', 'ملغى'])->default('مقبول');
            $table->timestamps();// حالة الطلب بالنسبة لعامل التوصيل
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_men_orders');
    }
};
