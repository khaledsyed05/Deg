<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->integer('balance')->default(0); // SYP — signed INT, cached sum
            $table->string('currency', 3)->default('SYP');
            $table->timestamps();
        });

        // DB-level constraint: balance must never go negative (MySQL 8.0+ only)
        if (\DB::getDriverName() === 'mysql') {
            \DB::statement('ALTER TABLE wallets ADD CONSTRAINT chk_wallet_balance_non_negative CHECK (balance >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};
