<?php

namespace Tests\Unit\Services\MailPulse;

use App\Models\ESBTPParent;
use App\Services\MailPulse\MailPulseWorkflowPolicy;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MailPulseWorkflowPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite', [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => false,
        ]);
        config()->set('services.mailpulse.enabled', true);
        config()->set('services.mailpulse.real_workflows_enabled', true);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        DB::reconnect('sqlite');

        Schema::create('esbtp_parents', function (Blueprint $table): void {
            $table->id();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('parent_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->boolean('notify_paiements')->default(true);
            $table->json('preferred_channels')->nullable();
            $table->timestamps();
        });
        Schema::create('parent_chatbot_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->string('status');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_stop_blocks_phone_channels_but_not_an_allowed_email_channel(): void
    {
        DB::table('esbtp_parents')->insert(['id' => 15, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('parent_notification_preferences')->insert([
            'parent_id' => 15,
            'preferred_channels' => json_encode(['email', 'sms', 'whatsapp']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('parent_chatbot_links')->insert([
            'parent_id' => 15, 'status' => 'stopped', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $parent = ESBTPParent::findOrFail(15);
        $policy = app(MailPulseWorkflowPolicy::class);

        $this->assertFalse($policy->parentAllows($parent, 'payment_received', 'sms'));
        $this->assertFalse($policy->parentAllows($parent, 'payment_received', 'whatsapp'));
        $this->assertTrue($policy->parentAllows($parent, 'payment_received', 'email'));
    }
}
