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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('store_id')->nullable(); // معرف صاحب المتجر
            $table->unsignedBigInteger('delivery_worker_id')->nullable(); // معرف عامل التوصيل
            $table->unsignedBigInteger('order_id')->nullable(); // إضافة معرف الطلب بعد حقل delivery_worker_id
            $table->decimal('total_amount', 10, 2); // المبلغ الإجمالي
            $table->decimal('delivery_fee', 10, 2)->nullable(); 
            $table->decimal('company_percentage', 5, 2); // نسبة الشركة
            $table->decimal('amount_paid', 10, 2); // المبلغ المدفوع
            $table->enum('transaction_type', ['delivery', 'store']); // نوع المعاملة: توصيل أو متجر
            $table->timestamp('transaction_date')->useCurrent(); // تاريخ المعاملة
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
            $table->foreign('delivery_worker_id')->references('id')->on('delivery_men')->onDelete('set null');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
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
        Schema::dropIfExists('transactions');
    }
};
