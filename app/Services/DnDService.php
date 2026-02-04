<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DnDService
{
    protected string $baseUrl = 'https://www.dnd5eapi.co/api';

    public function getClassInfo(string $className): array
    {
        // Convert to lowercase for the API
        $index = Str::lower($className);

        try {
            $response = Http::timeout(5)->get("{$this->baseUrl}/classes/{$index}");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // Log the error but don't block character creation
            Log::warning("Failed to fetch D&D class info for {$className}: " . $e->getMessage());
        }

        return [];
    }
}
