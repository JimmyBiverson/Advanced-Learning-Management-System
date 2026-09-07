<?php

namespace Modules\Course\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Course\Models\Course;
use Modules\Course\Models\CourseEnrollment;
use Modules\Course\Models\WatchHistory;
use Symfony\Component\HttpFoundation\Response;

class CourseEnrollmentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user->role == 'admin') {
            return $next($request);
        }

        $watchHistory = $request->route('watch_history');
        if (!WatchHistory::findOrFail($watchHistory->id)) {
            return back()->with('error', 'Invalid watch history');
        }

        $course = Course::findOrFail($watchHistory->course_id);

        if ($user->role == 'instructor' && $user->instructor_id == $course->instructor_id) {
            return $next($request);
        }

        $enrollment = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $watchHistory->course_id)
            ->first();

        if ($enrollment) {
            return $next($request);
        }

        return back()->with('error', 'You are not enrolled in this course');
    }
}
