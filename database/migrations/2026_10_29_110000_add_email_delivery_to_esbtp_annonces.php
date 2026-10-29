<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_annonces', function (Blueprint $table): void {
            $table->boolean('email_notification_enabled')->default(false);
        });

        Schema::create('esbtp_annonce_email_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('annonce_id')->constrained('esbtp_annonces')->cascadeOnDelete();
            $table->string('email', 254);
            $table->string('status', 16)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['annonce_id', 'email']);
            $table->index(['annonce_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esbtp_annonce_email_deliveries');
        Schema::table('esbtp_annonces', fn (Blueprint $table) => $table->dropColumn('email_notification_enabled'));
    }
};
