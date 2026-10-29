<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if (! $this->shouldHideTechnicalDetails($e)) {
                return null;
            }

            if ($request->expectsJson() && ! $request->header('X-Livewire')) {
                return response()->json([
                    'message' => 'Something went wrong. Please try again.',
                ], 500);
            }

            return response()->view('errors.friendly', [], 500);
        });
    }

    private function shouldHideTechnicalDetails(Throwable $e): bool
    {
        if ($e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof AuthorizationException) {
            return false;
        }

        if ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500) {
            return false;
        }

        return true;
    }
}
