<?php

namespace App\Http\Controllers\React;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class ReactAppController extends Controller
{
    public function __invoke(): Response
    {
        $reactIndex = public_path('app/index.html');

        abort_unless(
            File::isFile($reactIndex),
            503,
            'The React application bundle is unavailable.',
        );

        return response(File::get($reactIndex), 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
