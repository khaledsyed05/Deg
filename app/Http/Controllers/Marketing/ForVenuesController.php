<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;

class ForVenuesController extends Controller
{
    public function index()
    {
        return view('marketing.for-venues');
    }
}
