<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VikingerMappingController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('admin.design.vikinger-mapping', [
            'mapping' => config('vikinger'),
        ]);
    }
}
