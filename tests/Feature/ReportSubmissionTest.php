<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Mail\ReportSubmittedMail;
use App\Models\Report;
use App\Models\ReportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReportSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_submit_a_report_when_recaptcha_passes(): void
    {
        Mail::fake();
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $reportType = ReportType::create([
            'name' => 'Illegal Dumping',
            'slug' => 'illegal-dumping',
        ]);

        $response = $this->postJson('/api/reports', [
            'report_type_id' => $reportType->id,
            'email' => 'reporter@example.com',
            'phone_number' => '08012345678',
            'address' => '1 Lightwork street, Ikeja',
            'city' => 'Ikeja',
            'state' => 'Lagos',
            'description' => 'Illegal dumping recently spotted just yesterday.',
            'recaptcha_token' => 'test-token',
        ]);

        $response->assertStatus(201)->assertJsonStructure(['reference', 'message']);

        $report = Report::where('reference', $response->json('reference'))->firstOrFail();

        $this->assertSame(ReportStatus::Submitted, $report->status);

        Mail::assertQueued(ReportSubmittedMail::class);
    }

    public function test_submission_is_rejected_when_recaptcha_fails(): void
    {
        Mail::fake();
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => false]),
        ]);

        $reportType = ReportType::create([
            'name' => 'Overflowing Bin',
            'slug' => 'overflowing-bin',
        ]);

        $response = $this->postJson('/api/reports', [
            'report_type_id' => $reportType->id,
            'email' => 'reporter2@example.com',
            'phone_number' => '08012345678',
            'address' => '2 Lightwork street, Ikeja',
            'city' => 'Ikeja',
            'state' => 'Lagos',
            'description' => 'Bin has been overflowing for a week.',
            'recaptcha_token' => 'bad-token',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingQueued();
    }
}
