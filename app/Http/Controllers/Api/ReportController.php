<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;

class ReportController extends Controller
{
    public function index(ReportService $reports)
    {
        return $reports->dashboard();
    }
}
