<?php

namespace Modules\Maintenance\Database\Seeders;

use App\Models\Instructor;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Modules\Language\Models\LanguageProperty;

class SettingsDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // New Settings Data
        $settings = [
            [
                'type' => 'payment',
                'sub_type' => 'sslcommerz',
                'title' => 'SSLCommerz Settings',
                'fields' => [
                    'active' => false,
                    'test_mode' => true,
                    'currency' => 'BDT',
                    'store_id' => '',
                    'store_password' => '',
                ],
            ],
            [
                'type' => 'payment',
                'sub_type' => 'razorpay',
                'title' => 'Razorpay Settings',
                'fields' => [
                    'active' => false,
                    'test_mode' => true,
                    'currency' => 'INR',
                    'api_key' => '',
                    'api_secret' => '',
                ],
            ],
            [
                'type' => 'payment',
                'sub_type' => 'offline',
                'title' => 'Offline Payment Settings',
                'fields' => [
                    'active' => false,
                    'payment_instructions' => 'Please complete your payment using one of the following payment details below. After making the payment, please submit your transaction details on the next page.',
                    'payment_details' => 'Please put your offline payment/bank information here',
                ],
            ],
            [
                'type' => 'auth',
                'sub_type' => 'recaptcha',
                'title' => 'Google Recaptcha',
                'fields' => [
                    'active' => false,
                    'site_key' => '',
                    'secret_key' => '',
                ],
            ],
            [
                'type' => 'payment',
                'sub_type' => 'flutterwave',
                'title' => 'Flutterwave Settings',
                'fields' => [
                    'active' => false,
                    'test_mode' => true,
                    'currency' => 'NGN',
                    'test_public_key' => '',
                    'test_secret_key' => '',
                    'test_encryption_key' => '',
                    'live_public_key' => '',
                    'live_secret_key' => '',
                    'live_encryption_key' => '',
                ],
            ],
            [
                'type' => 'payment',
                'sub_type' => 'xendit',
                'title' => 'Xendit Settings',
                'fields' => [
                    'active' => false,
                    'test_mode' => true,
                    'currency' => 'IDR',
                    'test_secret_key' => '',
                    'live_secret_key' => '',
                ],
            ],
            [
                'type' => 'meta_pixel',
                'sub_type' => null,
                'title' => 'Meta Pixel Settings',
                'fields' => [
                    'pixel_enabled' => false,
                    'pixel_id' => '',
                    'capi_enabled' => false,
                    'access_token' => '',
                    'test_event_code' => '',
                ],
            ],
            [
                'type' => 'google_analytics',
                'sub_type' => null,
                'title' => 'Google Analytics Settings',
                'fields' => [
                    'analytics_enabled' => false,
                    'measurement_id' => '',
                    'mp_enabled' => false,
                    'api_secret' => '',
                    'debug_mode' => false,
                ],
            ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'bkash',
            //     'title' => 'bKash Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'BDT',
            //         'test_app_key' => '',
            //         'test_app_secret' => '',
            //         'test_username' => '',
            //         'test_password' => '',
            //         'live_app_key' => '',
            //         'live_app_secret' => '',
            //         'live_username' => '',
            //         'live_password' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'jazzcash',
            //     'title' => 'JazzCash Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'PKR',
            //         'test_merchant_id' => '',
            //         'test_password' => '',
            //         'test_integrity_salt' => '',
            //         'live_merchant_id' => '',
            //         'live_password' => '',
            //         'live_integrity_salt' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'payhere',
            //     'title' => 'Payhere Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'LKR',
            //         'test_merchant_id' => '',
            //         'test_secret' => '',
            //         'live_merchant_id' => '',
            //         'live_secret' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'toyyibpay',
            //     'title' => 'Toyyibpay Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'MYR',
            //         'test_user_secret_key' => '',
            //         'test_category_code' => '',
            //         'live_user_secret_key' => '',
            //         'live_category_code' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'paytabs',
            //     'title' => 'Paytabs Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'AED',
            //         'region' => 'UAE',
            //         'profile_id' => '',
            //         'test_server_key' => '',
            //         'live_server_key' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'mercadopago',
            //     'title' => 'Mercado Pago Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'BRL',
            //         'test_access_token' => '',
            //         'test_public_key' => '',
            //         'live_access_token' => '',
            //         'live_public_key' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'payu',
            //     'title' => 'PayU Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'PLN',
            //         'test_merchant_pos_id' => '',
            //         'test_signature_key' => '',
            //         'live_merchant_pos_id' => '',
            //         'live_signature_key' => '',
            //     ],
            // ],
            // [
            //     'type' => 'payment',
            //     'sub_type' => 'braintree',
            //     'title' => 'Braintree Settings',
            //     'fields' => [
            //         'active' => false,
            //         'test_mode' => true,
            //         'currency' => 'USD',
            //         'test_merchant_id' => '',
            //         'test_public_key' => '',
            //         'test_private_key' => '',
            //         'live_merchant_id' => '',
            //         'live_public_key' => '',
            //         'live_private_key' => '',
            //     ],
            // ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['type' => $setting['type'], 'sub_type' => $setting['sub_type']], // Search by sub_type
                $setting                              // Find or insert
            );
        }

        $system = Setting::where('type', 'system')->first();

        if ($system && !array_key_exists('selling_currency', $system->fields)) {
            $system->fields = array_merge($system->fields, ['selling_currency' => 'USD']);
            $system->save();
        }

        if ($system && !array_key_exists('global_style', $system->fields)) {
            $system->fields = array_merge($system->fields, ['global_style' => '']);
            $system->save();
        }

        if ($system && !array_key_exists('direction', $system->fields)) {
            $system->fields = array_merge($system->fields, ['direction' => 'none']);
            $system->save();
        }

        if ($system && !array_key_exists('theme', $system->fields)) {
            $system->fields = array_merge($system->fields, ['theme' => 'system']);
            $system->save();
        }

        if ($system && !array_key_exists('language_selector', $system->fields)) {
            $system->fields = array_merge($system->fields, ['language_selector' => true]);
            $system->save();
        }

        if ($system && !array_key_exists('frontend', $system->fields)) {
            $system->fields = array_merge($system->fields, ['frontend' => false]);
            $system->save();
        }

        if ($system && !array_key_exists('auth_banner', $system->fields)) {
            $system->fields = array_merge($system->fields, ['auth_banner' => '/assets/auth/lms-illustration.png']);
            $system->save();
        }

        // Add new payout methods for instructors
        $instructors = Instructor::all();
        $newMethods = [
            [
                'type' => 'payout',
                'sub_type' => 'sslcommerz',
                'title' => 'SSLCommerz Settings',
                'fields' => [
                    'active' => false,
                    'test_mode' => true,
                    'currency' => 'BDT',
                    'store_id' => '',
                    'store_password' => '',
                ],
            ],
            [
                'type' => 'payout',
                'sub_type' => 'razorpay',
                'title' => 'Razorpay Settings',
                'fields' => [
                    'active' => false,
                    'test_mode' => true,
                    'currency' => 'INR',
                    'api_key' => '',
                    'api_secret' => '',
                ],
            ],
        ];

        foreach ($instructors as $instructor) {
            $currentMethods = $instructor->payout_methods ?? [];

            // Ensure payout_methods is an array
            if (!is_array($currentMethods)) {
                $currentMethods = (array) $currentMethods;
            }

            $existingSubTypes = array_map(
                fn($method) => $method['sub_type'] ?? null,
                $currentMethods
            );

            $hasChanges = false;

            foreach ($newMethods as $newMethod) {
                if (!in_array($newMethod['sub_type'], $existingSubTypes, true)) {
                    $currentMethods[] = $newMethod;
                    $existingSubTypes[] = $newMethod['sub_type'];
                    $hasChanges = true;
                }
            }

            if ($hasChanges) {
                $instructor->payout_methods = $currentMethods;
                $instructor->save();
            }
        }

        // 'Summery' Spelling issue
        $properties = LanguageProperty::all();
        foreach ($properties as $property) {
            if (
                $property->properties && is_array($property->properties) &&
                array_key_exists('summery', $property->properties) &&
                $property->properties['summery'] == 'Summery'
            ) {
                $property->properties = [...$property->properties, 'summery' => 'Summary'];
                $property->save();
            }
        }
    }
}
