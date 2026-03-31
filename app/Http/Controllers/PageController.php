<?php

namespace App\Http\Controllers;

use App\Models\CmsPage;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(CmsPage $page): View
    {
        return view('pages.show', [
            'page' => $page,
        ]);
    }
}
