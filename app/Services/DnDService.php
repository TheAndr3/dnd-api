<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DnDService
{
    protected string $baseUrl = 'https://www.dnd5eapi.co/api';

    public function getClassInfo(string $className): array
    {
        // Convert 'Barbarian' to 'barbarian' for the API index
        $index = Str::lower($className);

        $response = Http::get("{$this->baseUrl}/classes/{$index}");

        if ($response->successful()) {
            return $response->json();
        }

        return [];
    }
}
