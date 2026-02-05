<?php

namespace App\Services;

use App\Models\Character;
use App\Models\User;

class CharacterService
{
    public function __construct(
        private DnDService $dndService
    ) {}

    /**
     * Get user's characters paginated.
     */
    public function getUserCharacters(User $user)
    {
        return $user->characters()->latest()->paginate(10);
    }

    /**
     * Create a character and fetch external D&D API data.
     */
    public function createCharacter(User $user, array $data): array
    {
        $character = $user->characters()->create($data);

        $classInfo = $this->dndService->getClassInfo($character->class->value);

        return [
            'character' => $character,
            'external_data' => [
                'class_info' => [
                    'hit_die' => $classInfo['hit_die'] ?? null,
                    'proficiencies' => $classInfo['proficiencies'] ?? [],
                    'saving_throws' => $classInfo['saving_throws'] ?? [],
                ]
            ]
        ];
    }

    /**
     * Update a character.
     */
    public function updateCharacter(Character $character, array $data): Character
    {
        $character->update($data);
        return $character;
    }

    /**
     * Delete a character.
     */
    public function deleteCharacter(Character $character): void
    {
        $character->delete();
    }
}
