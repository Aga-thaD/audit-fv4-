<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('lob')->nullable();
            $table->string('aud_auditor')->nullable();
            $table->date('aud_date')->nullable();
            $table->date('aud_date_processed')->nullable();
            $table->string('aud_time_processed')->nullable();
            $table->string('aud_case_number')->nullable();
            $table->string('aud_audit_type')->nullable();
            $table->string('aud_customer')->nullable();
            $table->string('aud_area_hit')->nullable();
            $table->string('aud_error_category')->nullable();
            $table->string('aud_type_of_error')->nullable();
            $table->string('aud_source_type')->nullable();
            $table->longText('aud_feedback')->nullable();
            $table->string('aud_screenshot')->nullable();
            $table->string('aud_status')->nullable();
            $table->longText('aud_associate_feedback')->nullable();
            $table->string('aud_associate_screenshot')->nullable();
            $table->timestamp('aud_dispute_timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
