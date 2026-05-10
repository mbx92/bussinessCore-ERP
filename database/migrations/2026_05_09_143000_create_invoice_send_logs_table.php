<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_send_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('project_id')->nullable()->index();
            $table->string('invoice_number', 120);
            $table->string('recipient_email', 190);
            $table->string('status', 30)->default('sent');
            $table->text('message')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable()->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_send_logs');
    }
};
