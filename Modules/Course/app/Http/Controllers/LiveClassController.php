<?php

namespace Modules\Course\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\Course\Http\Requests\StoreLiveClassRequest;
use Modules\Course\Http\Requests\UpdateLiveClassRequest;
use Modules\Course\Models\CourseEnrollment;
use Modules\Course\Models\CourseLiveClass;
use Modules\Course\Notifications\LiveClassScheduledNotification;
use Modules\Course\Services\CoursePlayerService;
use Modules\Course\Services\ZoomLiveService;

class LiveClassController extends Controller
{
    public function __construct(
        private ZoomLiveService $zoomLiveService,
        private CoursePlayerService $coursePlayerService
    ) {}

    public function index($id)
    {
        $user = Auth::user();
        $live_class = CourseLiveClass::with('course.instructor.user')->find($id);

        if (! $live_class) {
            abort(404, 'Live class not found');
        }

        $watchHistory = $this->coursePlayerService->getWatchHistory(['course_id' => $live_class->course_id]);

        $zoomSdkEnabled = (bool) ($this->zoomLiveService->zoomConfig['zoom_web_sdk'] ?? false)
            && ! empty($this->zoomLiveService->zoomConfig['zoom_sdk_client_id'] ?? null)
            && ! empty($this->zoomLiveService->zoomConfig['zoom_sdk_client_secret'] ?? null);

        $isHost = $this->isHostFor($live_class, $user);

        if ($isHost && $live_class->provider === 'zoom') {
            $this->refreshZoomStartUrl($live_class);
        }

        return Inertia::render('Course/course-player/live-class/zoom-live-class', [
            'live_class' => $live_class,
            'watchHistory' => $watchHistory,
            'is_host' => $isHost,
            'zoom_sdk_enabled' => $zoomSdkEnabled,
            'zoom_sdk_client_id' => $this->zoomLiveService->zoomConfig['zoom_sdk_client_id'] ?? null,
        ]);
    }

    public function store(StoreLiveClassRequest $request)
    {
        $data = $request->validated();
        $data['provider'] = 'zoom';
        $data['class_date_and_time'] = date('Y-m-d\TH:i:s', strtotime($data['class_date_and_time']));

        if ($data['provider'] == 'zoom') {
            $meeting_info = $this->zoomLiveService->createZoomLive($data);
            $meeting_info_arr = json_decode($meeting_info, true);

            if (array_key_exists('code', $meeting_info_arr) && $meeting_info_arr['code']) {
                return back()->with('error', $meeting_info_arr['message'] ?? 'Failed to create Zoom meeting');
            }

            // Store the array, not the JSON string (model has array cast)
            $data['additional_info'] = $meeting_info_arr;
        }

        $liveClass = CourseLiveClass::create($data);

        $liveClass->load('course');
        $recipients = User::query()
            ->whereIn('id', CourseEnrollment::ofCourse($liveClass->course_id)->pluck('user_id'))
            ->get();

        if ($liveClass->course->instructor?->user) {
            $recipients->push($liveClass->course->instructor->user);
        }

        $recipients = $recipients->merge(User::admins()->get());

        $recipients->unique('id')->each->notify(new LiveClassScheduledNotification($liveClass));

        return back()->with('success', 'Live class added successfully');
    }

    public function update(UpdateLiveClassRequest $request, $id)
    {
        $data = $request->validated();
        $data['class_date_and_time'] = date('Y-m-d\TH:i:s', strtotime($data['class_date_and_time']));
        $prevLive = CourseLiveClass::find($id);

        if ($prevLive->provider == 'zoom') {
            $previous_meeting_info = $prevLive->getAdditionalInfoArray();
            $this->zoomLiveService->updateZoomLive($data, $previous_meeting_info['id']);
            $previous_meeting_info['start_time'] = $data['class_date_and_time'];
            $previous_meeting_info['topic'] = $data['class_topic'];
            $data['additional_info'] = $previous_meeting_info;
        }

        CourseLiveClass::find($id)->update($data);

        return back()->with('success', 'Live class updated successfully');
    }

    public function destroy($id)
    {
        $previous_meeting_data = CourseLiveClass::find($id);

        if ($previous_meeting_data->provider == 'zoom') {
            $meetingInfo = $previous_meeting_data->getAdditionalInfoArray();
            if (isset($meetingInfo['id'])) {
                $this->zoomLiveService->deleteZoomLive($meetingInfo['id']);
            }
        }

        CourseLiveClass::find($id)->delete();

        return back()->with('success', 'Live class deleted successfully');
    }

    /**
     * Generate Zoom SDK signature for frontend
     */
    public function signature($meetingId)
    {
        try {
            $liveClass = CourseLiveClass::with('course.instructor.user')->find($meetingId);

            if (! $liveClass) {
                return response()->json(['error' => 'Live class not found'], 404);
            }

            $isHost = $this->isHostFor($liveClass, Auth::user()) ? 1 : 0;

            // Get meeting info using the helper method
            $meetingInfo = $liveClass->getAdditionalInfoArray();

            if (! $meetingInfo || ! isset($meetingInfo['id'])) {
                return response()->json(['error' => 'Meeting info not found'], 404);
            }

            $signature = $this->zoomLiveService->generateSignature(
                (string) $meetingInfo['id'],
                $isHost
            );

            return response()->json([
                'signature' => $signature,
                'meetingNumber' => (string) $meetingInfo['id'], // Ensure string format
                'password' => $meetingInfo['password'] ?? '',
                'role' => $isHost,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Determine whether the given user is the host of the live class.
     */
    private function isHostFor(CourseLiveClass $liveClass, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $course = $liveClass->course;

        if (! $course) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ($user->instructor_id && $course->instructor_id && (int) $user->instructor_id === (int) $course->instructor_id) {
            return true;
        }

        return (int) $course->instructor?->user_id === (int) $user->id;
    }

    /**
     * Refresh a stale Zoom start_url with a fresh (unexpired) authorization token.
     */
    private function refreshZoomStartUrl(CourseLiveClass $liveClass): void
    {
        $meetingInfo = $liveClass->getAdditionalInfoArray();

        if (! $meetingInfo || ! isset($meetingInfo['id'])) {
            return;
        }

        try {
            $response = json_decode($this->zoomLiveService->getZoomLive((string) $meetingInfo['id']), true);

            if (! is_array($response) || ! isset($response['start_url'])) {
                return;
            }

            $meetingInfo['start_url'] = $response['start_url'];
            $meetingInfo['join_url'] = $response['join_url'] ?? $meetingInfo['join_url'] ?? null;
            $liveClass->additional_info = $meetingInfo;
        } catch (\Exception $e) {
            // Keep the stored URLs when the refresh fails (offline / auth error).
        }
    }
}
