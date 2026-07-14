<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __invoke(): View
    {
        $settings = SiteSetting::current();

        $galleryUrls = [];
        foreach ($settings->aboutGalleryImagePaths() as $path) {
            $galleryUrls[] = Storage::disk('public')->url($path);
        }

        return view('pages.about', [
            'about' => $settings,
            'galleryUrls' => $galleryUrls,
        ]);
    }
}
