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

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role == 'admin') {
            return $next($request);
        }

        $watchHistory = $request->route('watch_history');
        $watchHistoryModel = is_object($watchHistory) 
            ? $watchHistory 
            : WatchHistory::find($watchHistory);

        if (!$watchHistoryModel) {
            return redirect()->route('category.courses', ['category' => 'all'])->with('error', 'Invalid watch history');
        }

        $course = Course::find($watchHistoryModel->course_id);
        if (!$course) {
            return redirect()->route('category.courses', ['category' => 'all'])->with('error', 'Course not found');
        }

        if ($user->role == 'instructor' && $user->instructor_id == $course->instructor_id) {
            return $next($request);
        }

        $enrollment = CourseEnrollment::where('user_id', $user->id)
            ->where('course_id', $watchHistoryModel->course_id)
            ->first();

        if ($enrollment) {
            return $next($request);
        }

        // Check if student has watch history record belonging to them
        if ($watchHistoryModel->user_id == $user->id) {
            // Ensure enrollment record exists so watch history isn't orphaned
            CourseEnrollment::firstOrCreate([
                'user_id' => $user->id,
                'course_id' => $watchHistoryModel->course_id,
            ]);
            return $next($request);
        }

        return redirect()->route('course.details', ['slug' => $course->slug, 'id' => $course->id])->with('error', 'You are not enrolled in this course');
    }
}
