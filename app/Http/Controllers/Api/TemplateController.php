<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Spatie\MailTemplates\Models\MailTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TemplateController extends Controller
{
    /**
     * Get all mail templates
     */
    public function index(): JsonResponse
    {
        $templates = MailTemplate::all();

        return response()->json([
            'success' => true,
            'data' => $templates->map(function ($template) {
                return [
                    'id' => $template->id,
                    'mailable' => $template->mailable,
                    'subject' => $template->subject,
                    'variables' => $template->variables,
                    'created_at' => $template->created_at,
                    'updated_at' => $template->updated_at,
                ];
            }),
        ]);
    }

    /**
     * Get a specific template
     */
    public function show(int $id): JsonResponse
    {
        $template = MailTemplate::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $template->id,
                'mailable' => $template->mailable,
                'subject' => $template->subject,
                'html_template' => $template->html_template,
                'text_template' => $template->text_template,
                'variables' => $template->variables,
                'created_at' => $template->created_at,
                'updated_at' => $template->updated_at,
            ],
        ]);
    }

    /**
     * Create a new template
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'mailable' => 'required|string',
            'subject' => 'required|string',
            'html_template' => 'required|string',
            'text_template' => 'nullable|string',
        ]);

        $template = MailTemplate::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Update a template
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $template = MailTemplate::findOrFail($id);

        $request->validate([
            'subject' => 'string',
            'html_template' => 'string',
            'text_template' => 'nullable|string',
        ]);

        $template->update($request->only(['subject', 'html_template', 'text_template']));

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template,
        ]);
    }

    /**
     * Delete a template
     */
    public function destroy(int $id): JsonResponse
    {
        $template = MailTemplate::findOrFail($id);
        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
        ]);
    }

    /**
     * Preview a template
     */
    public function preview(Request $request, int $id): JsonResponse
    {
        $template = MailTemplate::findOrFail($id);
        
        $sampleData = $request->input('data', []);

        $engine = new \Mustache_Engine();
        
        $renderedHtml = $engine->render($template->html_template, $sampleData);
        $renderedText = $template->text_template 
            ? $engine->render($template->text_template, $sampleData)
            : null;
        $renderedSubject = $engine->render($template->subject, $sampleData);

        return response()->json([
            'success' => true,
            'data' => [
                'subject' => $renderedSubject,
                'html' => $renderedHtml,
                'text' => $renderedText,
            ],
        ]);
    }
}
