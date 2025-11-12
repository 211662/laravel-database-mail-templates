<?php

namespace Database\Seeders;

use Spatie\MailTemplates\Models\MailTemplate;
use App\Mail\NewDomainAdded;
use App\Mail\SystemAlert;
use App\Mail\DailyReport;
use Illuminate\Database\Seeder;

class MailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Template for New Domain Added notification
        MailTemplate::firstOrCreate(
            ['mailable' => NewDomainAdded::class],
            [
                'subject' => 'New Domain Added: {{ domainName }}',
                'html_template' => '
                    <h2>✅ New Domain Added to TempEmail System</h2>
                    <p>A new domain has been successfully added to the system.</p>
                    
                    <table style="width: 100%; margin: 20px 0; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 10px; background: #f5f5f5; font-weight: bold;">Domain:</td>
                            <td style="padding: 10px;">{{ domainName }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; background: #f5f5f5; font-weight: bold;">Added By:</td>
                            <td style="padding: 10px;">{{ addedBy }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; background: #f5f5f5; font-weight: bold;">Custom Domain:</td>
                            <td style="padding: 10px;">{{ isCustom }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px; background: #f5f5f5; font-weight: bold;">Date & Time:</td>
                            <td style="padding: 10px;">{{ addedAt }}</td>
                        </tr>
                    </table>
                    
                    <p style="color: #666; font-size: 14px;">
                        This domain is now available for generating temporary email addresses.
                    </p>
                ',
                'text_template' => '
New Domain Added to TempEmail System
=====================================

Domain: {{ domainName }}
Added By: {{ addedBy }}
Custom Domain: {{ isCustom }}
Date & Time: {{ addedAt }}

This domain is now available for generating temporary email addresses.
                ',
            ]
        );

        // Template for System Alerts
        MailTemplate::firstOrCreate(
            ['mailable' => SystemAlert::class],
            [
                'subject' => '⚠️ System Alert: {{ alertType }}',
                'html_template' => '
                    <h2 style="color: #333;">{{ alertType }}</h2>
                    <p style="font-size: 16px; line-height: 1.6;">{{ message }}</p>
                    
                    <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107;">
                        <strong>Severity:</strong> {{ severity }}<br>
                        <strong>Timestamp:</strong> {{ timestamp }}
                    </div>
                    
                    <p style="margin-top: 20px; color: #666; font-size: 14px;">
                        Please review this alert and take appropriate action if necessary.
                    </p>
                ',
                'text_template' => '
System Alert: {{ alertType }}
=============================

{{ message }}

Severity: {{ severity }}
Timestamp: {{ timestamp }}

Please review this alert and take appropriate action if necessary.
                ',
            ]
        );

        // Template for Daily Report
        MailTemplate::firstOrCreate(
            ['mailable' => DailyReport::class],
            [
                'subject' => '📊 Daily Report - {{ date }}',
                'html_template' => '
                    <h2>Daily System Report</h2>
                    <p style="color: #666;">{{ date }}</p>
                    
                    <div class="stats">
                        <div class="stat-item">
                            <h3 style="color: #667eea; font-size: 32px; margin: 0;">{{ totalEmails }}</h3>
                            <p style="color: #666; margin: 5px 0;">Total Emails Generated</p>
                        </div>
                        <div class="stat-item">
                            <h3 style="color: #764ba2; font-size: 32px; margin: 0;">{{ totalMessages }}</h3>
                            <p style="color: #666; margin: 5px 0;">Messages Received</p>
                        </div>
                    </div>
                    
                    <table style="width: 100%; margin: 20px 0; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 15px; background: #f9f9f9; border-bottom: 1px solid #ddd;">
                                <strong>Active Domains:</strong>
                            </td>
                            <td style="padding: 15px; background: #f9f9f9; border-bottom: 1px solid #ddd;">
                                {{ activeDomains }}
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 15px; background: #fff; border-bottom: 1px solid #ddd;">
                                <strong>Most Used Domain:</strong>
                            </td>
                            <td style="padding: 15px; background: #fff; border-bottom: 1px solid #ddd;">
                                {{ mostUsedDomain }}
                            </td>
                        </tr>
                    </table>
                    
                    <p style="color: #666; font-size: 14px; margin-top: 30px;">
                        This is an automated daily report from your TempEmail system.
                    </p>
                ',
                'text_template' => '
Daily System Report - {{ date }}
=================================

STATISTICS:
-----------
Total Emails Generated: {{ totalEmails }}
Messages Received: {{ totalMessages }}
Active Domains: {{ activeDomains }}
Most Used Domain: {{ mostUsedDomain }}

This is an automated daily report from your TempEmail system.
                ',
            ]
        );

        $this->command->info('Seeded 3 mail templates');
    }
}
