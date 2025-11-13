<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('horarios', 'estado')) {
            Schema::table('horarios', function (Blueprint $table) {
                $table->string('estado', 15)->default('PENDIENTE');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('horarios', 'estado')) {
            Schema::table('horarios', function (Blueprint $table) {
                $table->dropColumn('estado');
            });
        }
    }
};

