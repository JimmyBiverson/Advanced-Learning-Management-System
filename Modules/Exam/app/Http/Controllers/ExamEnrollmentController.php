<?php

namespace Modules\Exam\Http\Controllers;

use App\Enums\PricingType;
use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Exam\Http\Requests\ExamEnrollmentRequest;
use Modules\Exam\Services\ExamEnrollmentService;
use Modules\Exam\Services\ExamService;

class ExamEnrollmentController extends Controller
{
    public function __construct(
        private UserService $user,
        private ExamService $exam,
        private ExamEnrollmentService $examEnrollments,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $extraParams = [];
        if (! isAdmin()) {
            if ($user->instructor) {
                /** @var Instructor $instructor */
                $instructor = $user->instructor;
                $extraParams = ['instructor_id' => $instructor->id];
            } else {
                $extraParams = ['user_id' => $user->id];
            }
        }

        $data = array_merge($request->all(), [
            'pagination' => true,
            'relations' => ['exam:id,title', 'user:id,name,email,photo'],
        ], $extraParams);

        $prices = PricingType::cases();
        $users = $this->user->getUsers(['select' => 'id,name']);
        $exams = $this->exam->getAllExams(['status' => 'published', 'select' => 'id,title']);
        $enrollments = $this->examEnrollments->getEnrollments($data);

        return Inertia::render('Exam/dashboard/enrollments/exams', compact('prices', 'users', 'exams', 'enrollments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExamEnrollmentRequest $request)
    {
        $this->examEnrollments->createExamEnroll($request->validated());

        return back()->with('success', 'Enrollment is successfully done in this exam');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->examEnrollments->deleteEnrollment($id);

        return back()->with('success', 'Enrollment is successfully deleted');
    }

    /**
     * Update governance, payment status, access permissions, and offline marks.
     */
    public function updateGovernance(Request $request, string $id)
    {
        $enrollment = \Modules\Exam\Models\ExamEnrollment::findOrFail($id);

        $data = $request->validate([
            'payment_status' => 'nullable|string',
            'amount_paid' => 'nullable|numeric',
            'access_granted' => 'nullable|boolean',
            'results_locked' => 'nullable|boolean',
            'offline_marks' => 'nullable|numeric',
            'offline_remarks' => 'nullable|string',
        ]);

        $enrollment->update(array_filter($data, fn ($val) => $val !== null));

        return back()->with('success', 'Student exam governance & report updated successfully.');
    }

    /**
     * Display comprehensive Admin Exam Reports & Governance Dashboard.
     */
    public function reports(Request $request)
    {
        $search = $request->input('search');
        $examId = $request->input('exam_id');
        $paymentStatus = $request->input('payment_status');
        $resultsLocked = $request->input('results_locked');

        $query = \Modules\Exam\Models\ExamEnrollment::with(['exam', 'user']);

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($examId) {
            $query->where('exam_id', $examId);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($resultsLocked !== null && $resultsLocked !== '') {
            $query->where('results_locked', (bool) $resultsLocked);
        }

        $enrollments = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_enrollments' => \Modules\Exam\Models\ExamEnrollment::count(),
            'paid_full' => \Modules\Exam\Models\ExamEnrollment::where('payment_status', 'paid')->count(),
            'pending_payment' => \Modules\Exam\Models\ExamEnrollment::where('payment_status', 'pending')->count(),
            'access_revoked' => \Modules\Exam\Models\ExamEnrollment::where('access_granted', false)->count(),
            'results_locked' => \Modules\Exam\Models\ExamEnrollment::where('results_locked', true)->count(),
            'offline_graded' => \Modules\Exam\Models\ExamEnrollment::whereNotNull('offline_marks')->where('offline_marks', '>', 0)->count(),
        ];

        $exams = $this->exam->getAllExams(['select' => 'id,title,exam_mode,total_marks']);

        return Inertia::render('Exam/dashboard/reports/index', [
            'enrollments' => $enrollments,
            'stats' => $stats,
            'exams' => $exams,
            'filters' => [
                'search' => $search,
                'exam_id' => $examId,
                'payment_status' => $paymentStatus,
                'results_locked' => $resultsLocked,
            ],
        ]);
    }
}
