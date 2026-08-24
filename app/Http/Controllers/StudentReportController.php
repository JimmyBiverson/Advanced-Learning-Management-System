<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StudentReportController extends Controller
{
    public function index(Request $request)
    {
        $studentsQuery = User::query()->students();

        $students = (clone $studentsQuery)
            ->select(['id', 'name', 'email', 'status', 'created_at'])
            ->withCount('enrollments')
            ->withCount('examAttempts')
            ->withCount([
                'examAttempts as passed_attempts_count' => fn ($query) => $query->where('is_passed', true),
            ])
            ->when($request->string('students_search')->trim()->value(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($request->input('status'), ['active', 'inactive'], true), function ($query) use ($request): void {
                $query->where('status', $request->input('status') === 'active' ? 1 : 0);
            })
            ->orderBy('name')
            ->paginate(15, ['*'], 'students')
            ->withQueryString();

        return Inertia::render('dashboard/student-reports/index', [
            'students' => $students,
            'summary' => [
                'students' => (clone $studentsQuery)->count(),
                'activeStudents' => (clone $studentsQuery)->where('status', 1)->count(),
                'enrollments' => (clone $studentsQuery)->withCount('enrollments')->get()->sum('enrollments_count'),
                'passedAttempts' => (clone $studentsQuery)->withCount([
                    'examAttempts as passed_attempts_count' => fn ($query) => $query->where('is_passed', true),
                ])->get()->sum('passed_attempts_count'),
            ],
            'filters' => [
                'search' => $request->input('students_search', ''),
                'status' => $request->input('status', 'all'),
            ],
        ]);
    }
}