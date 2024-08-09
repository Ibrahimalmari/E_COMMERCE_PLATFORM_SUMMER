<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID كمعرف رئيسي
            $table->string('type'); // نوع الإشعار
            $table->unsignedBigInteger('notifiable_id'); // معرّف النموذج القابل للإشعار
            $table->string('notifiable_type'); // نوع النموذج القابل للإشعار
            $table->text('data'); // البيانات المخزنة بتنسيق JSON
            $table->timestamp('read_at')->nullable(); // تاريخ قراءة الإشعار (يمكن أن يكون NULL)
            $table->timestamps(); // تواريخ الإنشاء والتحديث
        });
    }

    public function down()
    {
        Schema::dropIfExists('notifications');
    }
}
