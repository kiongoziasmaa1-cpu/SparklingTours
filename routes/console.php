<?php

use App\Models\OutboxMessage;
use App\Models\User;
use App\Services\Audit;
use App\Services\OutboxService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('staff:create {email?}', function () {
    $email = $this->argument('email') ?? $this->ask('Staff email');
    $name = $this->ask('Full name');
    $password = $this->secret('Password (12+ characters)');
    $confirm = $this->secret('Confirm password');
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 12 || $password !== $confirm || ! $name) {
        $this->error('Invalid details or password confirmation.');

        return 1;
    }
    if (User::where('email', $email)->exists()) {
        $this->error('An account already exists.');

        return 1;
    }
    $u = User::create(['email' => $email, 'name' => $name, 'password' => $password, 'role' => 'admin', 'active' => true]);
    Audit::record('staff.created', $u);
    $this->info('Administrator created. Sign in to open the admin dashboard.');
})->purpose('Create an administrator securely; password is prompted, never passed as an argument');
Artisan::command('outbox:dispatch', function () {
    OutboxMessage::where('status', 'processing')->where('processing_at', '<', now()->subMinutes(15))->update(['status' => 'pending', 'processing_at' => null]);
    app(OutboxService::class)->dispatchPending();
    $this->info('Pending notification dispatch attempted.');
});
Artisan::command('app:doctor', function () {
    $this->table(['Check', 'Result'], [['Laravel', app()->version()], ['PHP', PHP_VERSION], ['Database', config('database.default')], ['Environment', app()->environment()], ['CAPTCHA', config('tourism.captcha.enabled') ? 'enabled' : 'LOCAL/TEST bypass'], ['Mail', config('mail.default')], ['Queue', config('queue.default')]]);
    if (app()->environment('production') && (! str_starts_with(config('app.url'), 'https://') || in_array(config('mail.default'), ['log', 'array'], true))) {
        $this->error('Configure HTTPS APP_URL and real mail transport before launch.');

        return 1;
    }
    $this->info('Verify SMTP delivery, Turnstile hostname and supplier/payment details on the target host.');
});
Schedule::command('outbox:dispatch')->everyMinute()->withoutOverlapping();
