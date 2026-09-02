<?php

namespace App\Http\Controllers;

use App\Models\Advertisement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AdvertisementRedirectController extends Controller
{
    public function click(Advertisement $advertisement): RedirectResponse
    {
        abort_unless($advertisement->isCurrentlyActive(), 404);
        $advertisement->increment('clicks_count');

        return redirect()->away($advertisement->destination_url);
    }

    public function image(Advertisement $advertisement): Response
    {
        abort_unless($advertisement->isCurrentlyActive(), 404);
        $advertisement->increment('impressions_count');
        $path = str($advertisement->image_url)->after('/storage/')->toString();
        abort_unless(Storage::disk('public')->exists($path), 404);

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => Storage::disk('public')->mimeType($path) ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
