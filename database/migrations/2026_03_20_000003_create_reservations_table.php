<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_id')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->dateTime('reserved_at');
            $table->string('staff')->nullable();
            $table->string('status')->default('reserved'); // reserved / completed / cancelled
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
