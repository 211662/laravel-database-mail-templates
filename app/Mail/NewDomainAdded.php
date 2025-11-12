<?php

namespace App\Mail;

use App\Models\Domain;
use App\Models\User;
use Spatie\MailTemplates\TemplateMailable;

class NewDomainAdded extends TemplateMailable
{
    public $domainName;
    public $addedBy;
    public $addedAt;
    public $isCustom;

    public function __construct(Domain $domain, ?User $user = null)
    {
        $this->domainName = $domain->domain;
        $this->addedBy = $user ? $user->name : 'System';
        $this->addedAt = now()->format('Y-m-d H:i:s');
        $this->isCustom = $domain->is_custom ? 'Yes' : 'No';
    }

    public function getHtmlLayout(): string
    {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #4CAF50; color: white; padding: 20px; text-align: center; }
                    .content { padding: 20px; background: #f9f9f9; }
                    .footer { padding: 10px; text-align: center; font-size: 12px; color: #777; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h1>TempEmail System</h1>
                    </div>
                    <div class="content">
                        {{{ body }}}
                    </div>
                    <div class="footer">
                        <p>© ' . date('Y') . ' TempEmail Service. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ';
    }
}
