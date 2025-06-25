<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Codespace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CodespaceController extends Controller
{
    public function index()
    {
        $codespaces = Auth::user()->codespaces()->latest()->paginate(10);
        return response()->json($codespaces);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'environment' => 'required|string|in:development,staging,production',
            'size' => 'required|string|in:Standard-2x4,Standard-4x8,Standard-8x16',
        ]);

        $codespace = Auth::user()->codespaces()->create($validated);

        return response()->json($codespace, 201);
    }

    public function show(Codespace $codespace)
    {
        $this->authorize('view', $codespace);
        return response()->json($codespace);
    }

    public function destroy(Codespace $codespace)
    {
        $this->authorize('delete', $codespace);
        $codespace->delete();

        return response()->json(['message' => 'Codespace deleted successfully']);
    }

    public function rebuild(Codespace $codespace)
    {
        $this->authorize('update', $codespace);
        // Implementation for rebuilding codespace
        return response()->json(['message' => 'Codespace rebuild initiated']);
    }

    public function status(Codespace $codespace)
    {
        $this->authorize('view', $codespace);
        return response()->json(['status' => $codespace->status]);
    }
} 