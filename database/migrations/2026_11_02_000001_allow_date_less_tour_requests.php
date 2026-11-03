<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->unsignedInteger('adults')->nullable()->change();
            $table->unsignedInteger('children')->nullable()->default(0)->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')->where('source', 'tour_request')->update([
            'start_date' => today()->toDateString(),
            'end_date' => today()->toDateString(),
            'adults' => 1,
            'children' => 0,
        ]);

        Schema::table('bookings', function (Blueprint $table) {
            $table->date('start_date')->nullable(false)->change();
            $table->date('end_date')->nullable(false)->change();
            $table->unsignedInteger('adults')->nullable(false)->change();
            $table->unsignedInteger('children')->nullable(false)->default(0)->change();
        });
    }
};
