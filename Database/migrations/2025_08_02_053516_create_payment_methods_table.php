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
            'payment_methods',
            function (Blueprint $table) {
                $table->id();
                $table->string('driver', 50)->index();
                $table->json('config')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            }
        );

        Schema::create(
            'payment_method_translations',
            function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('payment_method_id')->index();
                $table->string('locale', 5)->index();
                $table->timestamps();

                $table->unique(['payment_method_id', 'locale'], 'payment_method_locale_unique');
                $table->foreign('payment_method_id')
                    ->references('id')
                    ->on('payment_methods')
                    ->onDelete('cascade');
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
        Schema::dropIfExists('payment_method_translations');
        Schema::dropIfExists('payment_methods');
    }
};
