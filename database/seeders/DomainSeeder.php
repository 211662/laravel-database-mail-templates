<?php

namespace Database\Seeders;

use App\Models\Domain;
use Illuminate\Database\Seeder;

class DomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domains = [
            ['domain' => 'tempmail.xyz', 'priority' => 100],
            ['domain' => 'quickmail.io', 'priority' => 90],
            ['domain' => 'disposable.email', 'priority' => 80],
            ['domain' => 'throwaway.email', 'priority' => 70],
            ['domain' => 'temp-inbox.com', 'priority' => 60],
            ['domain' => '10minutemail.net', 'priority' => 50],
            ['domain' => 'guerrillamail.info', 'priority' => 40],
            ['domain' => 'mailinator.net', 'priority' => 30],
        ];

        foreach ($domains as $domain) {
            Domain::firstOrCreate(
                ['domain' => $domain['domain']],
                [
                    'is_active' => true,
                    'is_custom' => false,
                    'priority' => $domain['priority'],
                ]
            );
        }

        $this->command->info('Seeded ' . count($domains) . ' domains');
    }
}
