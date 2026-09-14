import { useAuth } from '@/hooks/use-auth';
import liveClass from '@/routes/live-class';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Props {
   live_class: CourseLiveClass;
   watchHistory: WatchHistory;
   is_host?: boolean;
   zoom_sdk_enabled?: boolean;
   zoom_sdk_client_id?: string;
   zoom_sdk_client_secret?: string;
}

interface MeetingConfig {
   role: number;
   password: string;
   signature: string;
   meetingNumber: string;
}

// Zoom Client View SDK type declarations
declare global {
   interface Window {
      ZoomMtg: {
         setZoomJSLib: (path: string, dir: string) => void;
         preLoadWasm: () => void;
         prepareWebSDK: () => void;
         checkSystemRequirements: () => any;
         i18n: {
            load: (lang: string) => void;
            onLoad: (callback: () => void) => void;
         };
         init: (config: {
            leaveUrl: string;
            disableCORP?: boolean;
            success: () => void;
            error: (error: any) => void;
         }) => void;
         join: (config: {
            meetingNumber: string;
            userName: string;
            signature: string;
            userEmail?: string;
            passWord?: string;
            success: (res: any) => void;
            error: (res: any) => void;
         }) => void;
         leave: () => void;
         inMeetingServiceListener: (
            event: string,
            callback: (data: any) => void,
         ) => void;
         getAttendeeslist: (config: any) => void;
         getCurrentUser: (config: any) => void;
      };
   }
}

const ZoomLiveClass = ({
   live_class,
   watchHistory,
   is_host,
   zoom_sdk_enabled,
   zoom_sdk_client_id,
}: Props) => {
   const { props } = usePage();
   const { auth, translate } = props as any;
   const { frontend } = translate;
   const { isAdmin } = useAuth();
   const [loading, setLoading] = useState(true);
   const [error, setError] = useState<string | null>(null);
   const [sdkLoaded, setSdkLoaded] = useState(false);
   const [sdkModeActive, setSdkModeActive] = useState(false);
   const initializationRef = useRef(false);

   const redirectUrl = (() => {
      if (isAdmin || !watchHistory?.id) {
         return `/dashboard/courses`;
      }
      return `/play-course/${live_class.course?.slug ?? ''}/${watchHistory.id}/${watchHistory.current_watching_id ?? 0}`;
   })();

   // Get meeting info
   const meetingInfo = (() => {
      if (!live_class.additional_info) {
         return null;
      }

      try {
         if (typeof live_class.additional_info === 'object') {
            return live_class.additional_info;
         }

         if (typeof live_class.additional_info === 'string') {
            return JSON.parse(live_class.additional_info);
         }

         return null;
      } catch (error) {
         // Error parsing meeting info
         return null;
      }
   })();

   const targetUrl = is_host
      ? meetingInfo?.start_url
      : meetingInfo?.join_url;

   const zoomAppUrl = meetingInfo?.id
      ? `zoommtg://zoom.us/${is_host ? 'start' : 'join'}?confno=${meetingInfo.id}&pwd=${meetingInfo.password ?? ''}`
      : null;

   // Load Zoom Client View SDK scripts
   const loadZoomSDK = async () => {
      // Zoom SDK already loaded
      if (window.ZoomMtg) {
         setSdkLoaded(true);

         return Promise.resolve();
      }

      // Loading Zoom Client View SDK scripts
      // Script loading order is important for Client View SDK
      const scripts = [
         'https://source.zoom.us/4.0.0/lib/vendor/react.min.js',
         'https://source.zoom.us/4.0.0/lib/vendor/react-dom.min.js',
         'https://source.zoom.us/4.0.0/lib/vendor/redux.min.js',
         'https://source.zoom.us/4.0.0/lib/vendor/redux-thunk.min.js',
         'https://source.zoom.us/4.0.0/zoom-meeting-4.0.0.min.js', // Client View SDK
      ];

      for (const scriptSrc of scripts) {
         await new Promise<void>((resolve, reject) => {
            const script = document.createElement('script');
            script.src = scriptSrc;
            script.async = false; // Load in order
            script.onload = () => {
               resolve();
            };
            script.onerror = () => {
               reject(new Error(`Failed to load script: ${scriptSrc}`));
            };
            document.head.appendChild(script);

            // Add timeout
            setTimeout(
               () => reject(new Error(`Script load timeout: ${scriptSrc}`)),
               10000,
            );
         });
      }

      // All Zoom Client View SDK scripts loaded successfully
      setSdkLoaded(true);
   };

   // Fetch signature and meeting config from backend
   const fetchMeetingConfig = async () => {
      try {
         setLoading(true);

         const response = await fetch(liveClass.signature.url(live_class.id), {
            method: 'GET',
            headers: {
               Accept: 'application/json',
               'X-Requested-With': 'XMLHttpRequest',
            },
         });

         if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
         }

         const data = await response.json();

         if (data.error) {
            throw new Error(data.error);
         }

         return data;
      } catch (error) {
         setError(
            error instanceof Error
               ? error.message
               : frontend.failed_to_get_meeting_configuration,
         );

         return null;
      }
   };

   // Initialize Zoom Client View SDK
   const initializeZoomSDK = async (config: MeetingConfig) => {
      try {
         if (!window.ZoomMtg) {
            throw new Error(frontend.zoom_sdk_not_loaded);
         }

         // Check system requirements
         window.ZoomMtg.checkSystemRequirements();

         // Clear the React root to avoid conflicts with Zoom's DOM manipulation
         const appElement = document.getElementById('app') || document.body;
         appElement.innerHTML = '';

         // Prepare body for Zoom to take over
         document.body.style.setProperty('margin', '0');
         document.body.style.setProperty('padding', '0');
         document.body.style.setProperty('overflow', 'hidden');
         document.body.style.setProperty('height', '100vh');
         document.body.style.setProperty('width', '100vw');

         // Pre-load WASM and prepare SDK
         window.ZoomMtg.preLoadWasm();
         window.ZoomMtg.prepareWebSDK();

         // Load language (default to English)
         window.ZoomMtg.i18n.load('en-US');

         // Initialize SDK when language is loaded
         window.ZoomMtg.i18n.onLoad(() => {
            window.ZoomMtg.init({
               leaveUrl: window.location.origin + redirectUrl, // Redirect after leaving
               disableCORP: !window.crossOriginIsolated, // Required for security
               success: () => {
                  joinMeeting(config);
               },
               error: (error: any) => {
                  setError(
                     frontend.failed_to_initialize_meeting +
                        ': ' +
                        (error.message || error),
                  );
                  setLoading(false);
                  setSdkModeActive(false);
               },
            });
         });

         // Set up meeting event listeners
         setupMeetingListeners();
      } catch (error) {
         setError(
            error instanceof Error
               ? error.message
               : frontend.failed_to_initialize_meeting,
         );
         setLoading(false);
         setSdkModeActive(false);
      }
   };

   // Join the meeting
   const joinMeeting = (config: MeetingConfig) => {
      window.ZoomMtg.join({
         meetingNumber: config.meetingNumber,
         userName: auth.user.name,
         signature: config.signature,
         userEmail: auth.user.email || '',
         passWord: config.password,
         success: (res: any) => {
            // At this point, Zoom has taken over the page completely
            // Our React component should step aside
            setLoading(false);

            // Get current user info
            window.ZoomMtg.getCurrentUser({
               success: (res: any) => {
                  // Current user info
               },
            });

            // Get attendees list
            window.ZoomMtg.getAttendeeslist({});
         },
         error: (error: any) => {
            setError(
               frontend.failed_to_join_meeting +
                  ': ' +
                  (error.message || error),
            );
            setLoading(false);
            setSdkModeActive(false);
         },
      });
   };

   // Setup meeting event listeners
   const setupMeetingListeners = () => {
      // User join event
      window.ZoomMtg.inMeetingServiceListener('onUserJoin', (data: any) => {
         // User joined
      });

      // User leave event
      window.ZoomMtg.inMeetingServiceListener('onUserLeave', (data: any) => {
         // User left
      });

      // Waiting room event
      window.ZoomMtg.inMeetingServiceListener(
         'onUserIsInWaitingRoom',
         (data: any) => {
            // User in waiting room
         },
      );

      // Meeting status change
      window.ZoomMtg.inMeetingServiceListener(
         'onMeetingStatus',
         (data: any) => {
            // Meeting status changed
         },
      );
   };

   // Cleanup function
   const cleanup = () => {
      if (window.ZoomMtg) {
         try {
            window.ZoomMtg.leave();
         } catch (error) {
            // Error leaving meeting during cleanup
         }
      }
   };

   // Join via Zoom web SDK (in-page)
   const joinViaSDK = async () => {
      setSdkModeActive(true);
      setLoading(true);

      try {
         // Step 1: Load SDK scripts
         await loadZoomSDK();

         // Step 2: Fetch meeting config
         const config = await fetchMeetingConfig();

         if (!config) {
            setSdkModeActive(false);

            return;
         }

         // Step 3: Initialize and join meeting
         await initializeZoomSDK(config);
      } catch (error) {
         setError(
            error instanceof Error
               ? error.message
               : frontend.failed_to_initialize_meeting,
         );
         setLoading(false);
         setSdkModeActive(false);
      }
   };

   const primaryAction = is_host
      ? (frontend.start_meeting ?? 'Start Meeting')
      : (frontend.join_class ?? 'Join Class');

   // Main initialization useEffect (one-shot, decides which path to use)
   useEffect(() => {
      if (initializationRef.current) {
         return;
      }

      initializationRef.current = true;

      // Only use the Web SDK when the admin explicitly enabled it and the credentials exist.
      if (!zoom_sdk_enabled || !zoom_sdk_client_id) {
         // Non-SDK mode: show a real anchor link so the browser opens the Zoom
         // launch page in a new tab (auto-launches the desktop app when installed).
         setLoading(false);

         return;
      }

      // SDK mode: check meeting info exists before prompting to join.
      if (!meetingInfo) {
         setError(frontend.meeting_information_not_found);
         setLoading(false);

         return;
      }

      setLoading(false);

      // Cleanup on unmount (only relevant if SDK was actually initialized)
      return cleanup;
   }, []); // eslint-disable-line react-hooks/exhaustive-deps -- Mount-only decisions

   // Render loading state
   if (loading) {
      return (
         <div className="flex min-h-screen items-center justify-center bg-gray-900">
            <div className="text-center">
               <div className="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-b-2 border-white"></div>
               <h1 className="text-xl font-semibold text-white">
                  {live_class.class_topic}
               </h1>
               <p className="text-gray-300">
                  {!sdkLoaded
                     ? frontend.loading_zoom_sdk
                     : frontend.joining_meeting}
               </p>
            </div>
         </div>
      );
   }

   // Render error state
   if (error) {
      return (
         <div className="flex min-h-screen items-center justify-center bg-gray-100">
            <div className="max-w-md rounded-lg bg-white p-8 text-center shadow-lg">
               <h1 className="mb-4 text-2xl font-bold text-red-600">
                  {frontend.unable_to_join_meeting}
               </h1>
               <p className="mb-4 text-muted-foreground">{error}</p>

               {/* Fallback: Show direct Zoom link if available */}
               {targetUrl && (
                  <div className="mt-4">
                     <p className="mb-2 text-sm text-gray-500">
                        {frontend.you_can_join_directly}
                     </p>
                     <a
                        href={targetUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-block rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
                     >
                        {frontend.open_in_zoom_app}
                     </a>
                  </div>
               )}

               <button
                  onClick={() => window.location.reload()}
                  className="mt-4 rounded bg-gray-600 px-4 py-2 text-white hover:bg-gray-700"
               >
                  {frontend.try_again}
               </button>
            </div>
         </div>
      );
   }

   // Render ready-to-join state (user gesture required to open Zoom)
   return (
      <div className="flex min-h-screen items-center justify-center bg-gray-900">
         <div className="mx-auto w-full max-w-md rounded-xl bg-white p-8 text-center shadow-2xl">
            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-blue-100">
               <svg
                  className="h-8 w-8 text-blue-600"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
               >
                  <path
                     strokeLinecap="round"
                     strokeLinejoin="round"
                     strokeWidth={2}
                     d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                  />
               </svg>
            </div>

            <h1 className="mb-2 text-2xl font-bold text-gray-900">
               {live_class.class_topic}
            </h1>
            <p className="mb-2 text-sm text-gray-500">
               {live_class.class_date_and_time
                  ? new Date(live_class.class_date_and_time).toLocaleString()
                  : ''}
            </p>
            <p className="mb-6 text-sm text-gray-600">
               {is_host
                  ? (frontend.host_meeting_descriptor ??
                    'You are the host. Start the meeting to let your students join.')
                  : (frontend.attendee_meeting_descriptor ??
                    'Join the live class through Zoom. The host will let you in.' )}
            </p>

            {targetUrl ? (
               <>
                  <a
                     href={targetUrl}
                     target="_blank"
                     rel="noopener noreferrer"
                     className="mb-4 inline-flex w-full items-center justify-center rounded-lg bg-blue-600 px-6 py-3 text-base font-semibold text-white shadow-lg transition hover:bg-blue-700"
                  >
                     <svg
                        className="mr-2 h-5 w-5"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                     >
                        <path
                           strokeLinecap="round"
                           strokeLinejoin="round"
                           strokeWidth={2}
                           d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
                        />
                     </svg>
                     {primaryAction}
                  </a>

                  {zoomAppUrl && (
                     <a
                        href={zoomAppUrl}
                        className="mb-4 inline-flex w-full items-center justify-center rounded-lg border border-blue-600 bg-white px-6 py-3 text-base font-semibold text-blue-600 transition hover:bg-blue-50"
                     >
                        {frontend.open_in_zoom_app ?? 'Open in Zoom App'}
                     </a>
                  )}

                  {zoom_sdk_enabled && zoom_sdk_client_id && (
                     <button
                        onClick={joinViaSDK}
                        className="mb-4 inline-flex w-full items-center justify-center rounded-lg border border-gray-300 bg-white px-6 py-3 text-base font-semibold text-gray-700 transition hover:bg-gray-50"
                     >
                        {frontend.join_in_browser ?? 'Join in browser'}
                     </button>
                  )}
               </>
            ) : (
               <p className="mb-4 rounded bg-red-50 px-4 py-3 text-sm text-red-600">
                  {frontend.meeting_information_not_found}
               </p>
            )}

            <div>
               <Link
                  href={redirectUrl}
                  className="mt-2 inline-block text-sm text-blue-600 hover:underline"
               >
                  {frontend.return_to_course ?? 'Return to course'}
               </Link>
            </div>
         </div>
      </div>
   );
};

export default ZoomLiveClass;