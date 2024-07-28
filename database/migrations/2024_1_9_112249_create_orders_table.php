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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_numbers')->unique(); // إضافة العمود الجديد
            $table->string('additional_info')->nullable();
            $table->text('delivery_notes')->nullable(); 
            $table->double('delivery_fee')->nullable();
            $table->double('discount')->nullable(); 
            $table->double('invoice_amount')->nullable();; 
            $table->enum('order_status', [
                'تم استلام الطلب',
                'الطلب قيد التجهيز',
                'الطلب جاهز للتوصيل',
                'الطلب في مرحلة التوصيل',
                'تم تسليم الطلب'
            ])->default('تم استلام الطلب');         
            $table->string('pay_way')->nullable();;
            $table->double('tax')->nullable();;
            $table->double('tip')->nullable();; 
            $table->foreignId('cart_id')->constrained('carts')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('store_id')->constrained('stores')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('address_id')->constrained('addresses')->onUpdate('cascade')->onDelete('cascade'); 
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
        Schema::dropIfExists('orders');
      
    }
};