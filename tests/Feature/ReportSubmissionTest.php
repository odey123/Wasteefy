<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Mail\ReportSubmittedMail;
use App\Models\Report;
use App\Models\ReportType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'photos' => [UploadedFile::fake()->image('dumping.jpg')],
        ]);

        $response->assertStatus(201)->assertJsonStructure(['reference', 'message']);

        $report = Report::where('reference', $response->json('reference'))->firstOrFail();

        $this->assertSame(ReportStatus::Submitted, $report->status);
        $this->assertCount(1, $report->photos);
        $this->assertEquals(6.5244, (float) $report->latitude);
        $this->assertEquals(3.3792, (float) $report->longitude);

        Mail::assertQueued(ReportSubmittedMail::class);
    }

    public function test_submission_is_rejected_without_a_photo(): void
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
            'email' => 'reporter5@example.com',
            'phone_number' => '08012345678',
            'address' => '5 Lightwork street, Ikeja',
            'city' => 'Ikeja',
            'state' => 'Lagos',
            'description' => 'No photo attached to this submission.',
            'recaptcha_token' => 'test-token',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['photos']);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingQueued();
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
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingQueued();
    }

    public function test_submission_is_rejected_without_gps_coordinates(): void
    {
        Mail::fake();

        $reportType = ReportType::create([
            'name' => 'Overflowing Bin',
            'slug' => 'overflowing-bin',
        ]);

        $response = $this->postJson('/api/reports', [
            'report_type_id' => $reportType->id,
            'email' => 'reporter3@example.com',
            'phone_number' => '08012345678',
            'address' => '3 Lightwork street, Ikeja',
            'city' => 'Ikeja',
            'state' => 'Lagos',
            'description' => 'No GPS supplied for this submission.',
            'recaptcha_token' => 'test-token',
        ]);

        // The lagos.only middleware runs before form validation, so a
        // missing GPS coordinate is caught there first (403), not as a
        // validation error (422).
        $response->assertStatus(403);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingQueued();
    }

    public function test_submission_is_rejected_when_gps_is_outside_lagos(): void
    {
        Mail::fake();
        Http::fake([
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $reportType = ReportType::create([
            'name' => 'Overflowing Bin',
            'slug' => 'overflowing-bin',
        ]);

        // Abuja coordinates - well outside the Lagos bounding box.
        $response = $this->postJson('/api/reports', [
            'report_type_id' => $reportType->id,
            'email' => 'reporter4@example.com',
            'phone_number' => '08012345678',
            'address' => 'Somewhere in Abuja',
            'city' => 'Abuja',
            'state' => 'FCT',
            'description' => 'Submitted from outside Lagos.',
            'recaptcha_token' => 'test-token',
            'gps_latitude' => 9.0765,
            'gps_longitude' => 7.3986,
        ]);

        $response->assertStatus(403);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingQueued();
    }

    public function test_ip_eligibility_passes_for_a_nigerian_ip(): void
    {
        Http::fake([
            'http://ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'NG']),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '105.112.0.1'])
            ->getJson('/api/reports/ip-eligibility');

        $response->assertOk()->assertJson(['eligible' => true, 'message' => null]);
    }

    public function test_ip_eligibility_fails_for_a_non_nigerian_ip(): void
    {
        Http::fake([
            'http://ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'US']),
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->getJson('/api/reports/ip-eligibility');

        $response->assertOk()->assertJson([
            'eligible' => false,
            'message' => 'Reporting is only available to users within Nigeria.',
        ]);
    }

    public function test_submission_is_rejected_when_ip_is_outside_nigeria_even_with_valid_lagos_gps(): void
    {
        Mail::fake();
        Http::fake([
            'http://ip-api.com/*' => Http::response(['status' => 'success', 'countryCode' => 'US']),
            'https://www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true]),
        ]);

        $reportType = ReportType::create([
            'name' => 'Overflowing Bin',
            'slug' => 'overflowing-bin',
        ]);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->postJson('/api/reports', [
                'report_type_id' => $reportType->id,
                'email' => 'reporter6@example.com',
                'phone_number' => '08012345678',
                'address' => '6 Lightwork street, Ikeja',
                'city' => 'Ikeja',
                'state' => 'Lagos',
                'description' => 'Foreign IP but spoofed Lagos GPS.',
                'recaptcha_token' => 'test-token',
                'gps_latitude' => 6.5244,
                'gps_longitude' => 3.3792,
                'photos' => [UploadedFile::fake()->image('dumping.jpg')],
            ]);

        // The IP check runs before the GPS check, so this is blocked even
        // though the GPS coordinates alone would have passed.
        $response->assertStatus(403)->assertJson([
            'message' => 'Reporting is only available to users within Nigeria.',
        ]);

        $this->assertDatabaseCount('reports', 0);
        Mail::assertNothingQueued();
    }
}
