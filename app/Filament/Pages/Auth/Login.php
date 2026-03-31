<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    /** Fehlversuche pro IP; Sperre 30 Minuten (admin/login). */
    private const LOGIN_ATTEMPTS = 3;

    private const LOGIN_DECAY_SECONDS = 1800;

    public function authenticate(): ?LoginResponse
    {
        if ($this->isAdminLoginIpBlocked()) {
            return null;
        }

        $data = $this->form->getState();

        /** @var \Illuminate\Auth\SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider(); /** @phpstan-ignore-line */
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            $this->userUndertakingMultiFactorAuthentication = null;

            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->registerAdminLoginIpFailure();
            $this->throwFailureValidationException();
        }

        if (
            filled($this->userUndertakingMultiFactorAuthentication) &&
            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
        ) {
            if ($this->isMultiFactorChallengeRateLimited($user)) {
                return null;
            }

            try {
                $this->multiFactorChallengeForm->validate();
            } catch (ValidationException $exception) {
                $this->registerAdminLoginIpFailure();
                throw $exception;
            }
        } else {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($user);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
        }

        if (! $authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
            if (! ($user instanceof FilamentUser)) {
                return true;
            }

            return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
        }, $data['remember'] ?? false)) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->registerAdminLoginIpFailure();
            $this->throwFailureValidationException();
        }

        session()->regenerate();

        RateLimiter::clear($this->adminLoginIpLimiterKey());

        return app(LoginResponse::class);
    }

    protected function adminLoginIpLimiterKey(): string
    {
        return 'filament-admin-login:'.sha1((string) (request()->ip() ?? 'unknown'));
    }

    protected function isAdminLoginIpBlocked(): bool
    {
        $key = $this->adminLoginIpLimiterKey();

        if (RateLimiter::tooManyAttempts($key, maxAttempts: self::LOGIN_ATTEMPTS)) {
            $this->notifyAdminLoginIpBlocked(RateLimiter::availableIn($key));

            return true;
        }

        return false;
    }

    protected function registerAdminLoginIpFailure(): void
    {
        RateLimiter::hit($this->adminLoginIpLimiterKey(), self::LOGIN_DECAY_SECONDS);
    }

    protected function notifyAdminLoginIpBlocked(int $secondsRemaining): void
    {
        Notification::make()
            ->title('Zu viele Fehlversuche')
            ->body('Diese IP ist für '.max(1, $secondsRemaining).' Sekunden gesperrt.')
            ->danger()
            ->send();
    }

    protected function isMultiFactorChallengeRateLimited(Authenticatable $user): bool
    {
        $rateLimitingKey = $this->adminLoginIpLimiterKey();

        if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: self::LOGIN_ATTEMPTS)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'authenticate',
                request()->ip(),
                RateLimiter::availableIn($rateLimitingKey),
            ))?->send();

            return true;
        }

        return false;
    }
}
