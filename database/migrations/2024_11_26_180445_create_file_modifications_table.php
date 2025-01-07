<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileModificationsTable extends Migration
{
    public function up()
    {
        Schema::create('file_modifications', function (Blueprint $table) {
            $table->id(); // AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('file_id'); // معرف الملف
            $table->unsignedBigInteger('user_id'); // معرف المستخدم
            $table->text('changes'); // وصف التعديلات
            $table->foreign('file_id')->references('id')->on('files')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps(); // created_at و updated_at
        });

        // إضافة قيود المفاتيح الخارجية إذا لزم الأمر

    }

    public function down()
    {
        Schema::dropIfExists('file_modifications');
    }
}
