<?php

namespace App\Services;

use App\Mail\ReportSubmittedMail;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ReportService
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $photos
     */
    public function create(array $data, array $photos = []): Report
    {
        return DB::transaction(function () use ($data, $photos) {
            $report = Report::create($data);

            foreach ($photos as $photo) {
                $path = $photo->store('reports', 'public');

                $report->photos()->create(['path' => $path]);
            }

            Mail::to($report->email)->queue(new ReportSubmittedMail($report));

            return $report;
        });
    }
}
