<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should not be sanitized (e.g., rich text editors)
     */
    protected array $except = [
        //
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();

        $request->merge($this->sanitize($input));

        return $next($request);
    }

    /**
     * Recursively sanitize input data.
     */
    protected function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->clean($value);
            }
        }

        return $data;
    }

    /**
     * Clean a string value by removing HTML tags and dangerous content.
     */
    protected function clean(string $value): string
    {
        // Strip all HTML tags
        $value = strip_tags($value);

        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Trim whitespace
        return trim($value);
    }
}
