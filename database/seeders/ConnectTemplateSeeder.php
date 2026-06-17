<?php

namespace Database\Seeders;

use App\Models\Connect\ConnectMessageTemplate;
use App\Services\Connect\ConnectTemplateService;
use Illuminate\Database\Seeder;

class ConnectTemplateSeeder extends Seeder
{
    /**
     * Seed default templates for a given business_id.
     *
     * Usage:
     *   (new \Database\Seeders\ConnectTemplateSeeder())->forBusiness(123);
     */
    public function forBusiness(int $businessId): void
    {
        foreach (ConnectTemplateService::DEFAULTS as $type => $body) {
            ConnectMessageTemplate::firstOrCreate(
                ['business_id' => $businessId, 'template_type' => $type],
                ['body' => $body, 'active' => true]
            );
        }
    }

    public function run(): void
    {
        // No-op by default. Call forBusiness() from your own setup flow,
        // or it is invoked automatically when an admin creates Connect settings.
    }
}
