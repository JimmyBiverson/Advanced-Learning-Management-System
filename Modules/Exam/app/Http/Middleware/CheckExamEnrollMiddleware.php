<?php

namespace Modules\Exam\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Exam\Models\Exam;
use Modules\Exam\Models\ExamAttempt;
use Modules\Exam\Models\ExamEnrollment;

class CheckExamEnrollMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user->role == 'admin') {
            return $next($request);
        }

        $exam = $request->route('exam');

        if (! $exam instanceof Exam) {
            $attempt = $request->route('attempt');
            $exam = $attempt instanceof ExamAttempt ? $attempt->exam : null;
        }

        if (! $exam instanceof Exam) {
            $exam = Exam::findOrFail($request->route('exam') ?? $request->route('exam_id'));
        }

        if ($user->role == 'instructor' && $user->instructor_id == $exam->instructor_id) {
            return $next($request);
        }

        $enrollment = ExamEnrollment::where('user_id', $user->id)
            ->where('exam_id', $exam->id)
            ->first();

        if ($enrollment && $enrollment->access_granted && $enrollment->payment_status !== 'blocked') {
            return $next($request);
        }

        return back()->with('error', 'Exam access is currently restricted by the administrator.');
    }
}
