<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esbtp_annonces', function (Blueprint $table): void {
            // Anciennes annonces: 'all' conserve STRICTEMENT le public deja photographie.
            $table->string('email_audience_scope', 24)->default('all');
            // L'annee est figee au moment de la premiere publication.
            $table->unsignedBigInteger('email_target_year_id')->nullable();
        });

        Schema::table('esbtp_annonce_email_deliveries', function (Blueprint $table): void {
            // Les nouveaux etats (awaiting_dispatch) depassent 16 caracteres.
            $table->string('status', 32)->default('pending')->change();
            // ID MailPulse, pas un ID Resend (le transport est MailPulse exclusivement).
            $table->string('mailpulse_message_id', 191)->nullable();
            $table->string('mailpulse_request_id', 191)->nullable();
            $table->string('dispatch_state', 40)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamp('provider_delivered_at')->nullable();
            $table->timestamp('provider_delayed_at')->nullable();
            $table->timestamp('provider_failed_at')->nullable();
            $table->index(['annonce_id', 'provider_delivered_at'], 'ann_mail_delivered_idx');
        });
    }

    public function down(): void
    {
        Schema::table('esbtp_annonce_email_deliveries', function (Blueprint $table): void {
            $table->string('status', 16)->default('pending')->change();
            $table->dropIndex('ann_mail_delivered_idx');
            $table->dropColumn([
                'mailpulse_message_id', 'mailpulse_request_id', 'dispatch_state',
                'error_code', 'provider_delivered_at', 'provider_delayed_at', 'provider_failed_at',
            ]);
        });
        Schema::table('esbtp_annonces', fn (Blueprint $table) => $table->dropColumn([
            'email_audience_scope', 'email_target_year_id',
        ]));
    }
};
