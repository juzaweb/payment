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
            'orders',
            function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('code', 15)->index();
                $table->text('address')->nullable();
                $table->integer('quantity');
                $table->decimal('total_price', 20, 2);
                $table->decimal('total', 20, 2);
                $table->uuid('payment_method_id')->nullable()->index();
                $table->string('payment_method_name', 250);
                $table->text('note')->nullable();
                $table->string('payment_status', 10)->index()->default('pending')->comment('pending');
                $table->string('delivery_status', 10)->index()->default('pending')->comment('pending');
                $table->datetimes();
                $table->creator();

                $table->unique(['code']);
                $table->foreign('payment_method_id')
                    ->references('id')
                    ->on('payment_methods')
                    ->onDelete('set null');
            }
        );

        Schema::create(
            'order_items',
            function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->decimal('price', 15);
                $table->decimal('line_price', 15);
                $table->integer('quantity');
                $table->decimal('compare_price', 15)->nullable();
                $table->string('sku_code', 100)->nullable()->index();
                $table->string('barcode', 100)->nullable()->index();
                $table->uuid('order_id')->index();
                $table->uuid('orderable_id')->index();
                $table->string('orderable_type', 150)->index();
                $table->datetimes();

                $table->index(['orderable_id', 'orderable_type']);
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
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
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
