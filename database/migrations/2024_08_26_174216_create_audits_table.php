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
            $table->longText('aud_fascilit_notes')->nullable();
            $table->string('aud_attachmment')->nullable();
            $table->string('aud_status')->nullable();
            $table->longText('aud_associate_feedback')->nullable();
            $table->string('aud_associate_screenshot')->nullable();
            $table->timestamp('aud_dispute_timestamp')->nullable();
            $table->timestamp('aud_acknowledge_timestamp')->nullable();

            // Cintas AR specific fields
            $table->string('eo_number')->nullable();
            $table->string('ar_name')->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('document_number')->nullable();
            $table->string('country')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('reference')->nullable();
            $table->string('pass_fail')->nullable();
            $table->string('type_of_error')->nullable();
            $table->string('description_of_error')->nullable();
            $table->text('comments')->nullable();

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
