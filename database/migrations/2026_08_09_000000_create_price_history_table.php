<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('priceable');
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('old_price', 12, 4)->nullable();
            $table->decimal('new_price', 12, 4);
            $table->string('reason')->nullable();
            $table->dateTime('changed_at');
            $table->timestamps();

            $table->index(['priceable_type', 'priceable_id'], 'ph_priceable_idx');
            $table->index(['user_id', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
