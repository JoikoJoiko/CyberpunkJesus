<?php

namespace App\Http\Controllers;

use App\Models\ChangeLog;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(): View
    {
        return view('history', ['entries' => ChangeLog::latest('id')->paginate(20)]);
    }
}
