<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'payment_histories',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('module', 50)->index();
                $table->string('paymentable_type');
                $table->string('paymentable_id');
                $table->uuidMorphs('payer');
                $table->string('payment_id', 150)->nullable();
                $table->string('payment_method', 50)->nullable();
                $table->string('status', 50)->default('processing');
                $table->json('data')->nullable();
                $table->timestamps();

                $table->index(['paymentable_type', 'paymentable_id']);
                $table->foreign('payment_method')
                    ->references('driver')
                    ->on('payment_methods')
                    ->onDelete('set null');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_histories');
    }
};
