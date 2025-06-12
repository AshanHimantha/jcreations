<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cod_limits', function (Blueprint $table) {
            $table->id();
            $table->decimal('limit_amount', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });


    }

    public function down()
    {
        Schema::dropIfExists('cod_limits');
    }
};