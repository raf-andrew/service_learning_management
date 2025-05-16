<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\TestMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CodespacesTestTrait;

class MailTest extends TestCase
{
    use CodespacesTestTrait, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCodespacesTest();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        $this->tearDownCodespacesTest();
        parent::tearDown();
    }

    /**
     * Test that emails can be sent
     *
     * @return void
     */
    public function test_email_can_be_sent()
    {
        $this->addTestStep('mail_send', 'running');
        $user = User::factory()->create();
        
        Mail::to($user->email)->send(new TestMail());

        Mail::assertSent(TestMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
        $this->addTestStep('mail_send', 'completed');
        $this->linkTestToChecklist('mail-send');
    }

    /**
     * Test that email content is correct
     *
     * @return void
     */
    public function test_email_content_is_correct()
    {
        $this->addTestStep('mail_content', 'running');
        $user = User::factory()->create();
        
        Mail::to($user->email)->send(new TestMail());

        Mail::assertSent(TestMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->subject === 'Test Email' &&
                   $mail->view === 'emails.test';
        });
        $this->addTestStep('mail_content', 'completed');
        $this->linkTestToChecklist('mail-content');
    }

    /**
     * Test that email attachments work
     *
     * @return void
     */
    public function test_email_attachments_work()
    {
        $this->addTestStep('mail_attachment', 'running');
        $user = User::factory()->create();
        $attachment = storage_path('app/test.txt');
        file_put_contents($attachment, 'test content');
        
        Mail::to($user->email)->send(new TestMail());

        Mail::assertSent(TestMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                   $mail->hasAttachment($attachment);
        });

        unlink($attachment);
        $this->addTestStep('mail_attachment', 'completed');
        $this->linkTestToChecklist('mail-attachment');
    }

    /**
     * Test that email queue works
     *
     * @return void
     */
    public function test_email_queue_works()
    {
        $user = User::factory()->create();
        
        Mail::to($user->email)->queue(new TestMail());

        Mail::assertQueued(TestMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    /**
     * Test that email batching works
     *
     * @return void
     */
    public function test_email_batching_works()
    {
        $users = User::factory()->count(3)->create();
        
        Mail::to($users)->send(new TestMail());

        Mail::assertSent(TestMail::class, 3);
    }
} 