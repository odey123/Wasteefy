<?php

namespace Database\Seeders;

use App\Models\ReportType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Every call here is idempotent (firstOrCreate) so this is safe to run
     * on every deploy without creating duplicates or erroring on a second run.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => config('wasteefy.admin_email')],
            [
                'name' => 'Admin',
                'password' => config('wasteefy.admin_password'),
            ]
        );

        foreach (['Illegal Dumping', 'Overflowing Bin', 'Missed Pickup'] as $name) {
            ReportType::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
