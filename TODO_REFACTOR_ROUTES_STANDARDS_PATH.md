## TODO: Refactor Routes to Adhere to Naming and Path Standards

This document outlines the plan to refactor existing routes and their corresponding controllers to ensure they follow a consistent and intuitive naming and path convention. The goal is to make it easy to infer the controller location and name directly from the route URI.

### Convention to Enforce:

*   **Route URI to Controller Path:** A route like `/prefix/sub-prefix/action` should ideally map to a controller located at `app/Http/Controllers/Prefix/SubPrefix/ActionController.php`.
*   **Controller Naming:** Controller names should clearly reflect their primary action (e.g., `IndexController`, `CreateController`, `StoreController`, `ShowController`, `EditController`, `UpdateController`, `DestroyController`).
*   **Route Naming:** Route names should also be consistent, typically following a `prefix.sub-prefix.action` pattern (e.g., `users.index`, `products.create`).

---

### Analysis of Non-Compliant Routes and Proposed Correction Plan

Here is a detailed breakdown of routes that currently deviate from the established convention, along with the proposed steps for correction.

#### 1. `routes/api.php`

*   **Route:** `Route::post('payment/mycoolpay/webhook', App\Http\Controllers\Api\MyCoolPayWebhookController::class);`
    *   **Current Route URI:** `/api/payment/mycoolpay/webhook`
    *   **Current Controller:** `App\Http\Controllers\Api\MyCoolPayWebhookController.php`
    *   **Problem Identification:** The controller is located in a generic `Api` directory, which does not reflect the specific domain (`Payment`) or sub-domain (`MyCoolPay`) of the route. This makes it less intuitive to locate the controller based on the URI.
    *   **Proposed Solution:**
        1.  **Move Controller:** Move `app/Http/Controllers/Api/MyCoolPayWebhookController.php` to `app/Http/Controllers/Payment/MyCoolPay/WebhookController.php`.
        2.  **Update Namespace:** Update the namespace declaration within the `WebhookController.php` file to `App\Http\Controllers\Payment\MyCoolPay`.
        3.  **Update Route Definition:** Modify the route definition in `routes/api.php` to reference the new fully qualified class name:
            ```php
            Route::post('payment/mycoolpay/webhook', App\Http\Controllers\Payment\MyCoolPay\WebhookController::class);
            ```

#### 2. `routes/web.php`

*   **Route:** `Route::get('/push/diagnostic', App\Http\Controllers\PushNotificationDiagnosticController::class)`
    *   **Current Route URI:** `/push/diagnostic`
    *   **Current Controller:** `App\Http\Controllers\PushNotificationDiagnosticController.php`
    *   **Problem Identification:** The controller name `PushNotificationDiagnosticController` is overly verbose and does not fit the `Prefix/ActionController` pattern. The `PushNotification` part is redundant given the `Push` context.
    *   **Proposed Solution:**
        1.  **Rename and Move Controller:** Rename `app/Http/Controllers/PushNotificationDiagnosticController.php` to `DiagnosticController.php` and move it to `app/Http/Controllers/Push/DiagnosticController.php`.
        2.  **Update Namespace:** Update the namespace declaration within `DiagnosticController.php` to `App\Http\Controllers\Push`.
        3.  **Update Route Definition:** Modify the route definition in `routes/web.php` to reference the new fully qualified class name:
            ```php
            Route::get('/push/diagnostic', App\Http\Controllers\Push\DiagnosticController::class)
                ->middleware('auth')
                ->name('push.diagnostic');
            ```

#### 3. `routes/web/payment.php`

*   **Routes:** `Route::get('success', ...);`, `Route::get('error', ...);`, `Route::get('cancel', ...);` (under `Route::prefix('my-coolpay')->name('my-coolpay.')->group(...)`)
    *   **Current Route URIs:** `/payment/my-coolpay/success`, `/payment/my-coolpay/error`, `/payment/my-coolpay/cancel`
    *   **Current Controllers:** `App\Http\Controllers\Payment\Callback\SuccessController.php`, `App\Http\Controllers\Payment\Callback\ErrorController.php`, `App\Http\Controllers\Payment\Callback\CancelController.php`
    *   **Problem Identification:** The controllers are located in a generic `Payment/Callback` directory. While `Callback` is relevant, the `MyCoolPay` context from the route prefix is missing in the controller's path, making the mapping less direct.
    *   **Proposed Solution:**
        1.  **Create New Directory and Move Controllers:** Create a new directory `app/Http/Controllers/Payment/MyCoolPay/Callback`. Move `SuccessController.php`, `ErrorController.php`, and `CancelController.php` into this new directory.
        2.  **Update Namespaces:** Update the namespace declarations within these controller files to `App\Http\Controllers\Payment\MyCoolPay\Callback`.
        3.  **Update Route Definitions:** The route definitions in `routes/web/payment.php` will automatically pick up the new controller locations due to the `use` statements at the top of the file. Ensure these `use` statements are updated to reflect the new namespaces:
            ```php
            use App\Http\Controllers\Payment\MyCoolPay\Callback\CancelController;
            use App\Http\Controllers\Payment\MyCoolPay\Callback\ErrorController;
            use App\Http\Controllers\Payment\MyCoolPay\Callback\SuccessController;
            ```

#### 4. `routes/web/user.php`

*   **Routes:** `Route::post('/user/heartbeat', ...);`, `Route::post('/user/offline', ...);`, `Route::get('/user/status', ...);`
    *   **Current Route URIs:** `/user/heartbeat`, `/user/offline`, `/user/status`
    *   **Current Controllers:** `App\Http\Controllers\UserPresence\HeartbeatController.php`, `App\Http\Controllers\UserPresence\MarkUserOfflineController.php`, `App\Http\Controllers\UserPresence\UserStatusController.php`
    *   **Problem Identification:** The controllers are located in a `UserPresence` directory, which is not directly reflected in the `/user` segment of the URI. A more direct mapping would be `User/HeartbeatController`.
    *   **Proposed Solution:**
        1.  **Create New Directory and Move Controllers:** Create a new directory `app/Http/Controllers/User`. Move `HeartbeatController.php`, `MarkUserOfflineController.php`, and `UserStatusController.php` into this new directory.
        2.  **Update Namespaces:** Update the namespace declarations within these controller files to `App\Http\Controllers\User`.
        3.  **Update Route Definitions:** Modify the route definitions in `routes/web/user.php` to reference the new fully qualified class names:
            ```php
            Route::post('/user/heartbeat', App\Http\Controllers\User\HeartbeatController::class)->name('user.heartbeat');
            // ... similar updates for other routes
            ```

*   **Routes:** `Route::post('/push/subscribe', ...);`, `Route::delete('/push/unsubscribe', ...);`
    *   **Current Route URIs:** `/push/subscribe`, `/push/unsubscribe`
    *   **Current Controllers:** `App\Http\Controllers\PushSubscription\StorePushSubscriptionController.php`, `App\Http\Controllers\PushSubscription\DestroyPushSubscriptionController.php`
    *   **Problem Identification:** The controllers are located in a `PushSubscription` directory. The route URI uses `/push`, suggesting a `Push` directory for controllers. Also, the controller names `StorePushSubscriptionController` and `DestroyPushSubscriptionController` are verbose and could be simplified to `SubscribeController` and `UnsubscribeController` respectively, aligning with the action.
    *   **Proposed Solution:**
        1.  **Create New Directory and Move/Rename Controllers:** Create a new directory `app/Http/Controllers/Push`. Move `StorePushSubscriptionController.php` to `app/Http/Controllers/Push/SubscribeController.php` and `DestroyPushSubscriptionController.php` to `app/Http/Controllers/Push/UnsubscribeController.php`.
        2.  **Update Namespaces:** Update the namespace declarations within these controller files to `App\Http\Controllers\Push`.
        3.  **Update Route Definitions:** Modify the route definitions in `routes/web/user.php` to reference the new fully qualified class names:
            ```php
            Route::post('/push/subscribe', App\Http\Controllers\Push\SubscribeController::class)
                ->name('push.subscribe');
            Route::delete('/push/unsubscribe', App\Http\Controllers\Push\UnsubscribeController::class)
                ->name('push.unsubscribe');
            ```

#### 5. `routes/web/test.php`

*   **Routes:** `Route::get('{action}/{amount}', [App\Http\Controllers\Test\PaymentTestController::class, 'test'])`, etc.
    *   **Current Route URIs:** `/test/payment/...`
    *   **Current Controller:** `App\Http\Controllers\Test\PaymentTestController.php`
    *   **Problem Identification:** This file contains test routes and uses a multi-action controller (`PaymentTestController` with `test`, `checkBalance`, `checkStatus` methods). While the `Test/PaymentTestController` structure is acceptable for a test controller, the use of multi-action controllers deviates from the single-action controller standard enforced elsewhere.
    *   **Proposed Solution:** (This is a lower priority as it's a test file, but for full consistency):
        1.  **Refactor to Single-Action Controllers:** Create separate single-action controllers for each test action, e.g., `app/Http/Controllers/Test/Payment/RechargeController.php`, `app/Http/Controllers/Test/Payment/BalanceController.php`, `app/Http/Controllers/Test/Payment/StatusController.php`.
        2.  **Update Route Definitions:** Update the route definitions in `routes/web/test.php` to point to these new single-action controllers.

---

This plan provides a clear roadmap for refactoring the identified routes and controllers to improve consistency, maintainability, and adherence to the defined standards. Each step details the problem, the proposed solution, and the specific changes required.