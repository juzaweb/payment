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
            'carts',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->creator();
                $table->datetimes();
            }
        );

        Schema::create(
            'cart_items',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('cart_id');
                $table->uuid('orderable_id')->index();
                $table->string('orderable_type', 150)->index();
                $table->unsignedInteger('quantity')->default(1);
                $table->datetimes();

                $table->foreign('cart_id')
                    ->references('id')
                    ->on('carts')
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
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
    }
};
