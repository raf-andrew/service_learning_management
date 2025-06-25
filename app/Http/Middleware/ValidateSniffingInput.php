<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ValidateSniffingInput
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $rules = [
            'files' => 'required|array',
            'files.*' => 'required|string|exists:files,path',
            'report_format' => 'required|string|in:html,markdown,json',
            'severity' => 'required|string|in:error,warning,info',
            'type' => 'nullable|string|in:security,performance,documentation,architecture,testing',
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'code' => 'nullable|string',
            'days' => 'nullable|integer|min:1',
            'output' => 'nullable|string',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Sanitize inputs
        $request->merge([
            'files' => array_map('trim', $request->files),
            'report_format' => strtolower(trim($request->report_format)),
            'severity' => strtolower(trim($request->severity)),
            'type' => $request->type ? strtolower(trim($request->type)) : null,
            'name' => $request->name ? trim($request->name) : null,
            'description' => $request->description ? trim($request->description) : null,
            'code' => $request->code ? trim($request->code) : null,
            'output' => $request->output ? trim($request->output) : null,
        ]);

        return $next($request);
    }
} 