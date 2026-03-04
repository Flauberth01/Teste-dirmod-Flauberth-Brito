<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount_original', 15, 2);
            $table->char('currency', 3);
            $table->decimal('exchange_rate', 15, 6)->nullable();
            $table->decimal('amount_brl', 15, 2)->nullable();
            $table->string('status')->default('pending');
            $table->string('failure_reason')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
