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
        Schema::create('phone_q_c_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('pqc_lob')->nullable();
            $table->string('pqc_case_number')->nullable();
            $table->string('pqc_auditor')->nullable();
            $table->date('pqc_audit_date')->nullable();
            $table->date('pqc_date_processed')->nullable();
            $table->string('pqc_time_processed')->nullable();
            $table->string('pqc_type_of_call')->nullable();
            $table->longText('pqc_call_summary')->nullable();
            $table->longText('pqc_strengths')->nullable();
            $table->longText('pqc_opportunities')->nullable();
            $table->string('pqc_call_recording')->nullable();
            $table->json('pqc_scorecard')->nullable();
            $table->string('pqc_score')->nullable();
            $table->string('pqc_status')->nullable();
            $table->longText('pqc_associate_feedback')->nullable();
            $table->string('pqc_associate_screenshot')->nullable();
            $table->timestamp('pqc_dispute_timestamp')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_q_c_s');
    }
};
