# Rate Limiting Strategy

## Overview
Rate limiting is a critical security and stability feature that restricts the number of requests a user can make within a specific timeframe. This document outlines the recommended rate limiting strategy for the Sales Management application.

## Why We Need It

1.  **Security (Brute-Force Protection)**
    *   **Risk:** Attackers trying thousands of password combinations to crack admin accounts.
    *   **Solution:** Limit login attempts to 3-5 tries per minute.

2.  **Server Stability (Resource Protection)**
    *   **Risk:** Users spamming "Export" buttons or heavy report generation, causing high CPU/Memory usage and potential server crashes.
    *   **Solution:** Limit heavy actions (Exports, Reports) to 1 request every 10-20 seconds.

3.  **Data Integrity (Duplicate Prevention)**
    *   **Risk:** Cashiers double-clicking "Submit" on a slow connection, creating duplicate orders and inventory deductions.
    *   **Solution:** Throttle submission actions to ensure only the first click is processed.

## Implementation Plan

### 1. Authentication Throttling (Login)
**Target:** `App\Livewire\Auth\Login`
**Rule:** 5 attempts per minute.

```php
// In Login.php
use Illuminate\Support\Facades\RateLimiter;

public function login()
{
    $key = 'login|' . $this->email . '|' . request()->ip();

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        $this->addError('email', "Too many login attempts. Try again in $seconds seconds.");
        return;
    }

    if (!Auth::attempt(...)) {
        RateLimiter::hit($key, 60); // decay seconds
        // ... handle failure
    }

    RateLimiter::clear($key);
}
```

### 2. Heavy Reports & Exports
**Target:** `App\Livewire\Reports\ReportIndex`
**Rule:** 1 export every 15 seconds per user.

```php
// In ReportIndex.php
public function exportReport()
{
    $key = 'export-report|' . auth()->id();

    if (RateLimiter::tooManyAttempts($key, 1)) {
        session()->flash('error', 'Please wait before exporting again.');
        return;
    }

    RateLimiter::hit($key, 15); // Lock for 15 seconds

    // ... generate download
}
```

### 3. POS Order Submission
**Target:** `App\Livewire\Sales\CreateSale`
**Rule:** Basic throttle to prevent double-submission.

```php
// In CreateSale.php
public function submitOrder()
{
    $key = 'submit-order|' . auth()->id();
    
    if (RateLimiter::tooManyAttempts($key, 1)) {
        return; // Silently ignore double-clicks
    }
    
    RateLimiter::hit($key, 5); // 5 second cool-down

    // ... process order
}
```

### 4. API / Global Middleware
If the application exposes an API in the future, apply the `throttle:api` middleware in `routes/api.php` (default is 60 requests/minute).

## Next Steps
When ready to implement:
1.  Start with **Authentication** (Login) as it is the highest security risk.
2.  Add throttling to **Report Exports** to protect server resources.
3.  Add throttling to **POS Submit** to prevent data errors.
