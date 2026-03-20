<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatment_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('treated_at');
            $table->string('treatment_type');            // 治療内容: 虫歯治療、根管治療 等
            $table->string('treatment_area')->nullable(); // 治療部位: 右上7番、全顎 等（部位不要な治療はNULL）
            $table->string('staff')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'treated_at']);
            $table->index('treatment_type');
            $table->index('treatment_area');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatment_histories');
    }
};
