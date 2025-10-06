<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Sparepart;
use Illuminate\Http\Request;

class ReviewsController extends Controller
{
    public function index($sparepartId)
    {
        $sparepart = Sparepart::where('kode_sparepart', $sparepartId)->firstOrFail();

        $reviews = Review::with('user:id,nama,profile')
            ->where('sparepart_id', $sparepart->kode_sparepart)
            ->latest()
            ->get();

        $average = $reviews->avg('rating') ?? 0;

        return response()->json([
            'sparepart' => $sparepart->nama_sparepart,
            'average_rating' => round($average, 1),
            'total_reviews' => $reviews->count(),
            'reviews' => $reviews,
        ]);
    }

    /**
     * Store a new review
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sparepart_id' => 'required|exists:tb_sparepart,kode_sparepart',
            'rating' => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();

        $review = Review::create($validated);

        return response()->json([
            'message' => 'Review berhasil dikirim',
            'data' => $review,
        ]);
    }

    /**
     * Delete a review (hanya owner atau admin)
     */
    public function destroy(Review $review)
    {
        if (auth()->id() !== $review->user_id && !auth()->user()->is_admin) {
            return response()->json(['message' => 'Tidak diizinkan'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review berhasil dihapus']);
    }
}
