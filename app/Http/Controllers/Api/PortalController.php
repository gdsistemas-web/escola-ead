<?php

namespace App\Http\Controllers\Api;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Course;
use App\Models\Faq;
use App\Models\News;
use App\Http\Controllers\Controller;

class PortalController extends Controller
{
    public function index()
    {
        return [
            'banners' => Banner::where('is_active', true)->orderBy('position')->get(),
            'categories' => Category::where('is_active', true)->get(),
            'featured_courses' => Course::with('category', 'teacher')->where('status', 'published')->where('is_featured', true)->limit(6)->get(),
            'news' => News::whereNotNull('published_at')->latest('published_at')->limit(3)->get(),
            'faq' => Faq::where('is_active', true)->orderBy('position')->limit(8)->get(),
        ];
    }

    public function courses()
    {
        return Course::with('category', 'teacher')->where('status', 'published')->latest()->paginate(12);
    }

    public function course(Course $course)
    {
        abort_unless($course->status === 'published', 404);

        return $course->load('category', 'teacher.profile', 'modules.lessons.materials', 'exams');
    }
}
