<?php

namespace App\Livewire\Hooks;

use App\Support\UserFacingError;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Livewire\ComponentHook;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class FriendlyFormErrors extends ComponentHook
{
    public function exception($e, $stopPropagation): void
    {
        if (! $e instanceof Throwable || ! $this->shouldHandle($e) || $this->thrownWhileRendering()) {
            return;
        }

        report($e);

        $message = UserFacingError::message($e);
        session()->now('error', $message);

        try {
            $this->component->dispatch('swal', [
                'icon' => 'error',
                'title' => $message === UserFacingError::GENERIC ? 'Something went wrong' : 'Could not save',
                'text' => $message,
                'toast' => false,
            ]);
        } catch (Throwable) {
            // The form alert still shows the same message.
        }

        $stopPropagation();
    }

    private function shouldHandle(Throwable $e): bool
    {
        if ($e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException
            || $e instanceof ModelNotFoundException) {
            return false;
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return false;
        }

        return true;
    }

    private function thrownWhileRendering(): bool
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30) as $frame) {
            $class = $frame['class'] ?? '';
            $function = $frame['function'] ?? '';

            if (str_ends_with($class, 'HandleComponents') && in_array($function, ['render', 'getView'], true)) {
                return true;
            }
        }

        return false;
    }
}
