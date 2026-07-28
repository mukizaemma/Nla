<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_feedback')) {
            return;
        }

        Schema::create('customer_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('full_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('message');
            $table->text('recommendations')->nullable();
            $table->unsignedTinyInteger('rating_out_of_10')->nullable();
            $table->string('rating_category')->nullable()->default('overall');
            $table->foreignId('clinical_department_id')->nullable()->constrained('clinical_departments')->nullOnDelete();
            $table->foreignId('clinical_service_id')->nullable()->constrained('clinical_services')->nullOnDelete();
            $table->string('rated_target_label')->nullable();
            $table->boolean('wants_response')->default(false);
            $table->string('preferred_contact_method')->nullable()->default('none');
            $table->date('feedback_date')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_feedback');
    }
};
