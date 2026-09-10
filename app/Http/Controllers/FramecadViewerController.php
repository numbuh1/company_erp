<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FramecadViewerController extends Controller
{
    public function index()
    {
        return view('framecad-viewer');
    }
}
