# Laravel Fraud Checker Integration Guide

To integrate the FraudBD API into your Laravel project, follow these steps:

### 1. Environment Setup
Add your API key to your `.env` file:
```env
FRAUDBD_API_KEY=your_api_key_here
```

### 2. Configuration (Optional but Recommended)
Add the service configuration in `config/services.php`:
```php
'fraudbd' => [
    'key' => env('FRAUDBD_API_KEY'),
],
```

### 3. Controller
Place `FraudCheckerController.php` in `app/Http/Controllers/`.

### 4. Routing
Add the route in `routes/web.php` or `routes/api.php`:
```php
use App\Http\Controllers\FraudCheckerController;

Route::post('/check-fraud', [FraudCheckerController::class, 'checkCustomer'])->name('fraud.check');
```

### 5. View (Blade)
Create a view at `resources/views/fraud/report.blade.php` to display the data. You can use the logic from the Core PHP `index.php` example.

Example Blade snippet:
```blade
@if($report)
    <div class="summary">
        <p>Total: {{ $report['totalSummary']['total'] }}</p>
        <p>Success: {{ $report['totalSummary']['success'] }}</p>
        <p>Cancel: {{ $report['totalSummary']['cancel'] }}</p>
        <p>Rate: {{ $report['totalSummary']['successRate'] }}%</p>
    </div>
@endif
```
