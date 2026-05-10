<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('legal_contracts');
    }

    public function down(): void
    {
        // Intentionally empty: table removed from codebase; do not recreate.
    }
};
