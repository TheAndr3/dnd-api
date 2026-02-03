<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>D&D Character Manager</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Instrument+Sans:ital,wght@0,400..700;1,400..700&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body {
            font-family: 'Instrument Sans', sans-serif;
        }

        h1,
        h2,
        h3,
        .font-cinzel {
            font-family: 'Cinzel', serif;
        }

        [x-cloak] {
            display: none !important;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('characterApp', () => ({
                characters: [],
                openModal: false,
                isLoading: false,
                editingId: null, // Track if we are editing
                newCharacter: {
                    name: '', race: 'Human', class: 'Fighter', level: 1,
                    strength: 10, dexterity: 10, constitution: 10,
                    intelligence: 10, wisdom: 10, charisma: 10,
                    hit_points: 10, armor_class: 10, speed: 30,
                    initiative: 0, mana_points: 0
                },

                async init() {
                    this.fetchCharacters();
                },

                async fetchCharacters() {
                    this.isLoading = true;
                    try {
                        const response = await fetch('/api/characters');
                        const data = await response.json();
                        this.characters = data.data;
                    } catch (error) {
                        console.error('Error fetching characters:', error);
                    } finally {
                        this.isLoading = false;
                    }
                },

                // Unified save function
                async saveCharacter() {
                    if (this.editingId) {
                        await this.updateCharacter();
                    } else {
                        await this.createCharacter();
                    }
                },

                async createCharacter() {
                    try {
                        const response = await fetch('/api/characters', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.newCharacter)
                        });

                        if (response.ok) {
                            this.openModal = false;
                            this.fetchCharacters();
                            this.resetForm();
                        } else {
                            const errorData = await response.json();
                            alert('Error creating character: ' + JSON.stringify(errorData.message));
                        }
                    } catch (error) {
                        console.error('Error creating character:', error);
                    }
                },

                async updateCharacter() {
                    try {
                        const response = await fetch(`/api/characters/${this.editingId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.newCharacter)
                        });

                        if (response.ok) {
                            this.openModal = false;
                            this.fetchCharacters();
                            this.resetForm();
                        } else {
                            const errorData = await response.json();
                            alert('Error updating character: ' + JSON.stringify(errorData.message));
                        }
                    } catch (error) {
                        console.error('Error updating character:', error);
                    }
                },

                async deleteCharacter(id) {
                    if (!confirm('Are you sure you want to banish this soul?')) return;
                    try {
                        await fetch(`/api/characters/${id}`, { method: 'DELETE' });
                        this.fetchCharacters();
                    } catch (error) {
                        console.error('Error deleting character:', error);
                    }
                },

                prepareEdit(char) {
                    this.editingId = char.id;
                    // Clone data to avoid reactive binding issues before saving
                    this.newCharacter = { ...char };
                    this.openModal = true;
                },

                resetForm() {
                    this.editingId = null;
                    this.newCharacter = {
                        name: '', race: 'Human', class: 'Fighter', level: 1,
                        strength: 10, dexterity: 10, constitution: 10,
                        intelligence: 10, wisdom: 10, charisma: 10,
                        hit_points: 10, armor_class: 10, speed: 30,
                        initiative: 0, mana_points: 0
                    };
                }
            }))
        })
    </script>
</head>

<body class="bg-gray-900 text-white min-h-screen selection:bg-red-500 selection:text-white" x-data="characterApp">
    <!-- Background Gradients -->
    <div
        class="fixed inset-0 -z-10 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-purple-900 via-gray-900 to-black">
    </div>
    <div
        class="fixed inset-0 -z-10 bg-[url('https://www.transparenttextures.com/patterns/dark-matter.png')] opacity-30">
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-12 animate-fade-in-down">
            <div class="flex justify-center mb-4">
                <svg class="w-16 h-16 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
            </div>
            <h1
                class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-red-600 drop-shadow-lg mb-2">
                D&D Character Manager
            </h1>
            <p class="text-gray-400 text-lg font-light">Manage your adventurers and their epic journeys</p>

            <button @click="resetForm(); openModal = true"
                class="mt-8 px-8 py-3 bg-gradient-to-r from-orange-600 to-red-700 hover:from-orange-500 hover:to-red-600 text-white font-bold rounded-lg shadow-lg transform transition hover:-translate-y-1 hover:shadow-red-900/50 flex items-center gap-2 mx-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create New Character
            </button>
        </div>

        <!-- Empty State -->
        <div x-show="characters.length === 0 && !isLoading" x-cloak
            class="max-w-md mx-auto bg-gray-800/50 backdrop-blur-sm border border-gray-700/50 rounded-2xl p-12 text-center shadow-2xl">
            <div class="text-gray-500 mb-6">
                <svg class="w-24 h-24 mx-auto opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
            </div>
            <h3 class="text-2xl font-cinzel text-gray-300 mb-2">No Characters Yet</h3>
            <p class="text-gray-500 mb-8">Start your adventure by creating your first character!</p>
            <button @click="resetForm(); openModal = true"
                class="px-6 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-md mx-auto flex items-center gap-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Create Character
            </button>
        </div>

        <!-- List Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-show="characters.length > 0" x-cloak>
            <template x-for="char in characters" :key="char.id">
                <div
                    class="group relative bg-gray-800 border-l-4 border-red-600 rounded-lg p-6 hover:bg-gray-750 transition-all hover:shadow-lg hover:shadow-red-900/20 overflow-hidden">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-gradient-to-bl from-red-600/10 to-transparent -mr-4 -mt-4 rounded-full">
                    </div>

                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-2xl font-cinzel text-white font-bold" x-text="char.name"></h3>
                            <div class="text-sm text-yellow-500 font-medium">
                                Lv. <span x-text="char.level"></span> <span x-text="char.race"></span> <span
                                    x-text="char.class"></span>
                            </div>
                        </div>
                        <!-- Actions -->
                        <div class="flex gap-2">
                            <button type="button" @click="prepareEdit(char)"
                                class="text-gray-500 hover:text-white transition p-1 rounded hover:bg-gray-700"
                                title="Edit">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </button>
                            <button type="button" @click="deleteCharacter(char.id)"
                                class="text-gray-500 hover:text-red-500 transition p-1 rounded hover:bg-gray-700"
                                title="Delete">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex gap-4 mb-4">
                        <div class="flex-1 bg-gray-900/50 rounded p-2 text-center border border-gray-700">
                            <div class="text-xs text-gray-400 uppercase tracking-wider">HP</div>
                            <div class="text-red-400 font-bold text-lg" x-text="char.hit_points"></div>
                        </div>
                        <div class="flex-1 bg-gray-900/50 rounded p-2 text-center border border-gray-700">
                            <div class="text-xs text-gray-400 uppercase tracking-wider">AC</div>
                            <div class="text-blue-400 font-bold text-lg" x-text="char.armor_class"></div>
                        </div>
                        <div class="flex-1 bg-gray-900/50 rounded p-2 text-center border border-gray-700">
                            <div class="text-xs text-gray-400 uppercase tracking-wider">Init</div>
                            <div class="text-green-400 font-bold text-lg" x-text="char.initiative"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-6 gap-1 text-center text-xs text-gray-400">
                        <div>STR<br><span class="text-white font-bold" x-text="char.strength"></span></div>
                        <div>DEX<br><span class="text-white font-bold" x-text="char.dexterity"></span></div>
                        <div>CON<br><span class="text-white font-bold" x-text="char.constitution"></span></div>
                        <div>INT<br><span class="text-white font-bold" x-text="char.intelligence"></span></div>
                        <div>WIS<br><span class="text-white font-bold" x-text="char.wisdom"></span></div>
                        <div>CHA<br><span class="text-white font-bold" x-text="char.charisma"></span></div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true"
        x-show="openModal" x-cloak>
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" x-show="openModal"
                x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click="openModal = false">
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-700"
                x-show="openModal" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                <div class="bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 relative">
                    <button @click="openModal = false" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>

                    <h3 class="text-2xl leading-6 font-cinzel font-bold text-white mb-6" id="modal-title"
                        x-text="editingId ? 'Edit Character' : 'Create New Character'"></h3>

                    <div class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-400">Name</label>
                                <input type="text" x-model="newCharacter.name"
                                    class="mt-1 block w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Race</label>
                                <input type="text" x-model="newCharacter.race"
                                    class="mt-1 block w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400">Class</label>
                                <input type="text" x-model="newCharacter.class"
                                    class="mt-1 block w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-2 px-3 text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <h4
                                class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2 border-b border-gray-700 pb-1">
                                Ability Scores</h4>
                            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                                <template
                                    x-for="stat in ['strength', 'dexterity', 'constitution', 'intelligence', 'wisdom', 'charisma']">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-400 uppercase"
                                            x-text="stat.substring(0,3)"></label>
                                        <input type="number" x-model="newCharacter[stat]"
                                            class="mt-1 block w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-1 px-2 text-center text-white focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm">
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <h4
                                class="text-sm font-bold text-gray-500 uppercase tracking-wider mb-2 border-b border-gray-700 pb-1">
                                Combat Vitals</h4>
                            <div class="grid grid-cols-4 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-400">HP</label>
                                    <input type="number" x-model="newCharacter.hit_points"
                                        class="mt-1 w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-1 px-2 text-white text-center">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400">AC</label>
                                    <input type="number" x-model="newCharacter.armor_class"
                                        class="mt-1 w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-1 px-2 text-white text-center">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400">Speed</label>
                                    <input type="number" x-model="newCharacter.speed"
                                        class="mt-1 w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-1 px-2 text-white text-center">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-400">Init</label>
                                    <input type="number" x-model="newCharacter.initiative"
                                        class="mt-1 w-full bg-gray-900 border border-gray-600 rounded-md shadow-sm py-1 px-2 text-white text-center">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" @click="saveCharacter()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm"
                        x-text="editingId ? 'Save Changes' : 'Summon Character'">
                    </button>
                    <button type="button" @click="openModal = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-600 shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>