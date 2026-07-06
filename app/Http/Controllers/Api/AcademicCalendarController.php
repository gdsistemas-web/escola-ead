<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AcademicCalendarService;
use Illuminate\Http\Request;

class AcademicCalendarController extends Controller
{
    public function __invoke(Request $request, AcademicCalendarService $calendar)
    {
        return ['events' => $calendar->events($request->user())];
    }
}
