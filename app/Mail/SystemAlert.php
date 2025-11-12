<?php

namespace App\Mail;

use Spatie\MailTemplates\TemplateMailable;

class SystemAlert extends TemplateMailable
{
    public $alertType;
    public $message;
    public $timestamp;
    public $severity;

    public function __construct(string $alertType, string $message, string $severity = 'warning')
    {
        $this->alertType = $alertType;
        $this->message = $message;
        $this->severity = $severity;
        $this->timestamp = now()->format('Y-m-d H:i:s');
    }

    public function getHtmlLayout(): string
    {
        $colors = [
            'info' => '#2196F3',
            'warning' => '#FF9800',
            'error' => '#F44336',
            'success' => '#4CAF50',
        ];

        $bgColor = $colors[$this->severity] ?? $colors['info'];

        return '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                    .alert-box { 
                        background: ' . $bgColor . '; 
                        color: white; 
                        padding: 30px; 
                        margin: 20px;
                        border-radius: 8px;
                    }
                    .alert-content { background: white; color: #333; padding: 20px; margin-top: 20px; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class="alert-box">
                    <h1>⚠️ System Alert</h1>
                    <div class="alert-content">
                        {{{ body }}}
                    </div>
                </div>
            </body>
            </html>
        ';
    }
}
