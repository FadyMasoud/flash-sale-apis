<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('holds', function (Blueprint $table) {
        $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
    });
}

public function down()
{
    Schema::table('holds', function (Blueprint $table) {
        $table->dropForeign(['order_id']);
        $table->dropColumn('order_id');
    });
}
};
