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
        Schema::create('slipping_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subject_type'); // entry, project
            $table->unsignedBigInteger('subject_id');
            $table->string('rule'); // topic, project-inactive, course-slipping...
            $table->timestamp('slipping_since');
            $table->unsignedSmallInteger('severity')->default(1);
            $table->timestamp('snoozed_until')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('computed_at')->useCurrent();
            $table->timestamps();

            // Index optimized for active alerts dashboard scans
            $table->index(['user_id', 'resolved_at', 'snoozed_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slipping_snapshots');
    }
};
