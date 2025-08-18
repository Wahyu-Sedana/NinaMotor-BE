<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'sparepart_id' => 'required|string',
        ]);

        $user = Auth::user();

        $exists = Bookmark::where('user_id', $user->id)
            ->where('sparepart_id', $request->sparepart_id)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Sparepart already bookmarked'], 409);
        }

        Bookmark::create([
            'user_id' => $user->id,
            'sparepart_id' => $request->sparepart_id,
        ]);

        return response()->json([
            'status' => 200,
            'message' => 'Bookmark added successfully'
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'sparepart_id' => 'required|string',
        ]);

        $user = Auth::user();

        $deleted = Bookmark::where('user_id', $user->id)
            ->where('sparepart_id', $request->sparepart_id)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Bookmark removed successfully']);
        }

        return response()->json(['message' => 'Bookmark not found'], 404);
    }

    public function show()
    {
        $user = Auth::user();

        $bookmarks = Bookmark::with('sparepart')
            ->where('user_id', $user->id)
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Bookmark list fetched successfully',
            'data' => $bookmarks
        ]);
    }
}
