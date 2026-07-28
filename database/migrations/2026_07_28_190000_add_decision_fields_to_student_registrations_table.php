<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            if (! Schema::hasColumn('student_registrations', 'status')) {
                $table->string('status', 20)->default('pending')->after('submission_channel');
            }
            if (! Schema::hasColumn('student_registrations', 'admin_response_message')) {
                $table->text('admin_response_message')->nullable()->after('status');
            }
            if (! Schema::hasColumn('student_registrations', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('admin_response_message');
            }
            if (! Schema::hasColumn('student_registrations', 'responded_by')) {
                $table->foreignId('responded_by')->nullable()->after('responded_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_registrations', function (Blueprint $table) {
            if (Schema::hasColumn('student_registrations', 'responded_by')) {
                $table->dropConstrainedForeignId('responded_by');
            }
            foreach (['status', 'admin_response_message', 'responded_at'] as $column) {
                if (Schema::hasColumn('student_registrations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
