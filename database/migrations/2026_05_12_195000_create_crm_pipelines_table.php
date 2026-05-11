<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('title');
            $table->foreignId('crm_customer_id')->nullable()->constrained('crm_customers')->nullOnDelete();
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->string('stage', 30)->default('prospecting');
            $table->decimal('deal_value', 15, 2)->default(0);
            $table->unsignedTinyInteger('win_probability')->default(0);
            $table->date('expected_close_date')->nullable();
            $table->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30);
            $table->string('subject');
            $table->text('description')->nullable();
            $table->dateTime('activity_date');
            $table->dateTime('next_action_date')->nullable();
            $table->string('next_action_note')->nullable();
            $table->string('status', 20)->default('planned');
            $table->foreignId('crm_lead_id')->nullable()->constrained('crm_leads')->nullOnDelete();
            $table->foreignId('crm_customer_id')->nullable()->constrained('crm_customers')->nullOnDelete();
            $table->foreignId('crm_pipeline_id')->nullable()->constrained('crm_pipelines')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
        Schema::dropIfExists('crm_pipelines');
    }
};
