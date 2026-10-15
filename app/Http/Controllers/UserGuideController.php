<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;

class UserGuideController extends Controller
{
    public function index()
    {
        return view('backend.user_guide.index');
    }

    /**
     * PDF/Print-friendly version — superadmin can open this and print as booklet.
     */
    public function print()
    {
        return view('backend.user_guide.print');
    }
}
