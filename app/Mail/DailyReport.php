<?php

namespace App\Mail;

use Spatie\MailTemplates\TemplateMailable;

class DailyReport extends TemplateMailable
{
    public $date;
    public $totalEmails;
    public $totalMessages;
    public $activeDomains;
    public $mostUsedDomain;

    public function __construct(array $reportData)
    {
        $this->date = $reportData['date'] ?? now()->format('Y-m-d');
        $this->totalEmails = $reportData['total_emails'] ?? 0;
        $this->totalMessages = $reportData['total_messages'] ?? 0;
        $this->activeDomains = $reportData['active_domains'] ?? 0;
        $this->mostUsedDomain = $reportData['most_used_domain'] ?? 'N/A';
    }

    public function getHtmlLayout(): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
                    .report { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
                    .content { padding: 30px; }
                    .stats { display: table; width: 100%; margin: 20px 0; }
                    .stat-item { display: table-cell; text-align: center; padding: 20px; background: #f9f9f9; margin: 5px; }
                </style>
            </head>
            <body>
                <div class="report">
                    <div class="header">
                        <h1>📊 Daily Report</h1>
                        <p>TempEmail System</p>
                    </div>
                    <div class="content">
                        {{{ body }}}
                    </div>
                </div>
            </body>
            </html>
        ';
    }
}
