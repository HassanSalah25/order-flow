<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            // restrictOnDelete enforces "orders with payments cannot be deleted" at the DB level too.
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('method')->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 12, 2);
            $table->string('transaction_reference')->nullable()->unique();
            $table->string('failure_reason')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
