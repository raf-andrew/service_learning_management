<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Sniffing\SniffResultRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SniffingController extends Controller
{
    protected $sniffResultRepository;

    public function __construct(SniffResultRepository $sniffResultRepository)
    {
        $this->sniffResultRepository = $sniffResultRepository;
        $this->middleware('auth:api');
        $this->middleware('throttle:60,1');
    }

    /**
     * Run sniffing analysis on specified files
     */
    public function run(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array',
            'files.*' => 'required|string|exists:files,path',
            'report_format' => 'required|string|in:html,markdown,json',
            'severity' => 'required|string|in:error,warning,info',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $exitCode = Artisan::call('sniffing:run', [
                '--files' => implode(',', $request->files),
                '--report' => $request->report_format,
                '--severity' => $request->severity,
            ]);

            if ($exitCode !== 0) {
                return response()->json(['error' => 'Sniffing analysis failed'], 500);
            }

            return response()->json([
                'message' => 'Sniffing analysis completed successfully',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get sniffing results
     */
    public function results(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|string|exists:files,path',
            'days' => 'nullable|integer|min:1',
            'type' => 'nullable|string|in:error,warning,info',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $results = $this->sniffResultRepository->getAll();

            if ($request->has('file')) {
                $results = $results->where('file_path', $request->file);
            }

            if ($request->has('days')) {
                $results = $results->where('created_at', '>=', now()->subDays($request->days));
            }

            if ($request->has('type')) {
                $results = $results->where('type', $request->type);
            }

            return response()->json([
                'results' => $results,
                'total' => $results->count(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Generate analysis report
     */
    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'days' => 'required|integer|min:1',
            'format' => 'required|string|in:html,markdown,json',
            'output' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $exitCode = Artisan::call('sniffing:analyze', [
                '--days' => $request->days,
                '--format' => $request->format,
                '--output' => $request->output,
            ]);

            if ($exitCode !== 0) {
                return response()->json(['error' => 'Report generation failed'], 500);
            }

            $reportPath = storage_path('app/' . $request->output);
            if (!file_exists($reportPath)) {
                return response()->json(['error' => 'Report file not found'], 404);
            }

            return response()->json([
                'message' => 'Report generated successfully',
                'report_url' => Storage::url($request->output),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Manage sniffing rules
     */
    public function rules(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:list,add,remove,update',
            'type' => 'required_if:action,add,update|string|in:security,performance,documentation,architecture,testing',
            'name' => 'required_if:action,add,update|string',
            'description' => 'required_if:action,add,update|string',
            'code' => 'required_if:action,add,update|string',
            'severity' => 'required_if:action,add,update|string|in:error,warning,info',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $exitCode = Artisan::call('sniffing:rules', [
                'action' => $request->action,
                '--type' => $request->type,
                '--name' => $request->name,
                '--description' => $request->description,
                '--code' => $request->code,
                '--severity' => $request->severity,
            ]);

            if ($exitCode !== 0) {
                return response()->json(['error' => 'Rule management failed'], 500);
            }

            return response()->json([
                'message' => 'Rule management completed successfully',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Clear sniffing data
     */
    public function clear(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'nullable|string|exists:files,path',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $exitCode = Artisan::call('sniffing:clear', [
                '--file' => $request->file,
            ]);

            if ($exitCode !== 0) {
                return response()->json(['error' => 'Data clearing failed'], 500);
            }

            return response()->json([
                'message' => 'Data cleared successfully',
                'output' => Artisan::output(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
} 