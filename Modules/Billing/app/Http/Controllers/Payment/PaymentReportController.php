<?php

namespace Modules\Billing\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Billing\Models\PaymentHistory;
use Modules\Billing\Notifications\OfflinePaymentNotification;
use Modules\Billing\Services\PaymentReportService;
use Modules\Course\Models\Course;
use Modules\Course\Services\CourseEnrollmentService;
use Modules\Exam\Models\Exam;
use Modules\Exam\Models\ExamEnrollment;
use Modules\Exam\Services\ExamEnrollmentService;

class PaymentReportController extends Controller
{
    public function __construct(
        private PaymentReportService $paymentReportService,
        private CourseEnrollmentService $courseEnrollment,
        private ExamEnrollmentService $examEnrollment,
    ) {}

    /**
     * Display online payments
     */
    public function online_index(Request $request)
    {
        $payments = $this->paymentReportService->getPaymentReport(array_merge($request->all(), [
            'type' => 'online',
            'select' => ['id', 'amount', 'payment_type', 'transaction_id', 'created_at', 'user_id', 'purchase_id', 'purchase_type'],
            'relations' => ['user:id,name,email', 'purchase'],
            'paginate' => true,
        ]));

        return Inertia::render('Billing/reports/online', compact('payments'));
    }

    /**
     * Display offline payments
     */
    public function offline_index(Request $request)
    {
        $payments = $this->paymentReportService->getPaymentReport(array_merge($request->all(), [
            'type' => 'offline',
            'select' => ['id', 'amount', 'meta', 'transaction_id', 'created_at', 'user_id', 'purchase_id', 'purchase_type'],
            'relations' => ['user:id,name,email', 'purchase', 'media'],
            'paginate' => true,
        ]));

        return Inertia::render('Billing/reports/offline', compact('payments'));
    }

    /**
     * Verify offline payment and enroll user
     */
    public function verify(Request $request, int $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $payment = PaymentHistory::findOrFail($id);

        // Verify the payment
        $this->paymentReportService->verifyOfflinePayment($id, $request->all());

        // Enroll user based on purchase type (skip if already enrolled, e.g. re-verifying)
        if ($payment->purchase_type === Course::class
            && ! $this->courseEnrollment->getEnrollmentByCourseId($payment->purchase_id, $payment->user_id)) {
            $this->courseEnrollment->createCourseEnroll([
                'user_id' => $payment->user_id,
                'course_id' => $payment->purchase_id,
                'enrollment_type' => 'paid',
            ]);
        } elseif ($payment->purchase_type === Exam::class
            && ! ExamEnrollment::query()
                ->where('exam_id', $payment->purchase_id)
                ->where('user_id', $payment->user_id)
                ->exists()) {
            $this->examEnrollment->createExamEnroll([
                'user_id' => $payment->user_id,
                'exam_id' => $payment->purchase_id,
                'enrollment_type' => 'paid',
            ]);
        }

        $notificationData = [
            'title' => 'Offline payment verified for '.$this->getPurchaseTypeLabel($payment->purchase_type),
            'url' => route(
                'student.index',
                ['tab' => $payment->purchase_type === Exam::class ? 'exams' : 'courses'],
                false
            ),
        ];

        if ($request->filled('admin_notes')) {
            $notificationData['description'] = $request->admin_notes;
        }

        $payment->user->notify(new OfflinePaymentNotification($notificationData));

        return redirect()->back()->with('success', 'Payment verified and user enrolled successfully.');
    }

    /**
     * Reject offline payment
     */
    public function reject(Request $request, int $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:500',
        ]);

        $payment = PaymentHistory::findOrFail($id);
        $this->paymentReportService->rejectOfflinePayment($id, $request->admin_notes);

        $notificationData = [
            'title' => 'Offline payment rejected for '.$this->getPurchaseTypeLabel($payment->purchase_type),
        ];

        if ($request->filled('admin_notes')) {
            $notificationData['description'] = $request->admin_notes;
        }

        $payment->user->notify(new OfflinePaymentNotification($notificationData));

        return redirect()->back()->with('success', 'Payment rejected successfully.');
    }

    private function getPurchaseTypeLabel(string $purchaseType): string
    {
        return $purchaseType === Exam::class ? 'Exam' : 'Course';
    }
}
