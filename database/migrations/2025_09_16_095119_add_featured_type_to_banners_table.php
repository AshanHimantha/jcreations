<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Modify the enum column to include 'featured'
            DB::statement("ALTER TABLE banners MODIFY COLUMN type ENUM('mobile', 'desktop', 'featured') NOT NULL");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            // Revert back to original enum values
            DB::statement("ALTER TABLE banners MODIFY COLUMN type ENUM('mobile', 'desktop') NOT NULL");
        });
    }
};
