<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InstaTranslate Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .gradient-bg {
            background: linear-gradient(135deg, #f6f8fd 0%, #f1f6fd 100%);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased gradient-bg min-h-screen">
    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                        </svg>
                        <span class="ml-2 text-xl font-bold text-gray-900 tracking-tight">InstaTranslate</span>
                    </div>
                </div>
                <div class="flex items-center">
                    <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full font-medium">Mode: {{ strtoupper($mode) }}</span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="translationDashboard()">
        
        <div class="mb-8 border-b border-gray-200 pb-4">
            <div class="flex justify-between items-end mb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Review, generate, and edit translations across all locales.</p>
                </div>
                
                <div class="flex space-x-3" x-show="tab === 'missing'">
                    <button @click="generateAll()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        Generate All Missing
                    </button>
                </div>
            </div>

            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="tab = 'missing'" :class="tab === 'missing' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm">
                    Missing ({{ count($missingTranslations) }})
                </button>
                <button @click="tab = 'all'" :class="tab === 'all' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'" class="whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm">
                    All Translations ({{ count($allTranslations) }})
                </button>
            </nav>
        </div>

        <div x-show="tab === 'missing'" x-transition x-cloak>
            @if(empty($missingTranslations))
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">All caught up!</h3>
                <p class="mt-1 text-sm text-gray-500">No missing translations found across your locales.</p>
            </div>
            @else
            <div class="bg-white shadow-sm ring-1 ring-black ring-opacity-5 md:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 w-1/4">Key</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 w-1/3">Base Value ({{ $defaultLang }})</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Missing Locales & Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($missingTranslations as $key => $data)
                        <tr x-data="translationRow('{{ addslashes($key) }}', '{{ addslashes($data['base_value']) }}', {{ json_encode($data['missing_in']) }})" x-show="!isFullyResolved" class="hover:bg-gray-50 transition-colors">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 align-top">
                                <div class="font-mono text-xs text-indigo-600 break-all">{{ $key }}</div>
                            </td>
                            <td class="py-4 px-3 text-sm text-gray-500 align-top">
                                <div class="whitespace-pre-wrap">{{ $data['base_value'] }}</div>
                            </td>
                            <td class="py-4 px-3 text-sm text-gray-500 align-top">
                                <div class="space-y-4">
                                    <template x-for="locale in missingLocales" :key="locale">
                                        <div class="border rounded-md p-3 bg-gray-50 relative" :class="{'ring-2 ring-indigo-500 ring-offset-1': isGenerating(locale), 'bg-green-50 border-green-200': isResolved(locale)}">
                                            
                                            <!-- Overlay loader -->
                                            <div x-show="isGenerating(locale)" class="absolute inset-0 bg-white/70 flex items-center justify-center rounded-md z-10 backdrop-blur-sm">
                                                <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>

                                            <div class="flex justify-between items-center mb-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <span x-text="locale"></span>
                                                </span>
                                                
                                                <div class="flex space-x-2">
                                                    <button x-show="!hasDraft(locale)" @click="generate(locale)" class="text-indigo-600 hover:text-indigo-900 text-xs font-medium transition-colors">
                                                        Generate AI Translation
                                                    </button>
                                                    <div x-show="hasDraft(locale) && !isResolved(locale)" class="flex space-x-2" x-cloak>
                                                        <button @click="approve(locale)" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-green-600 hover:bg-green-700 transition-colors">
                                                            Approve & Save
                                                        </button>
                                                        <button @click="generate(locale)" class="text-gray-500 hover:text-gray-700 text-xs transition-colors">
                                                            Regenerate
                                                        </button>
                                                    </div>
                                                    <span x-show="isResolved(locale)" class="text-green-600 text-xs font-medium inline-flex items-center" x-cloak>
                                                        <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                        Saved
                                                    </span>
                                                </div>
                                            </div>

                                            <!-- Translation Editor -->
                                            <div x-show="hasDraft(locale) && !isResolved(locale)" x-cloak class="mt-2">
                                                <textarea x-model="drafts[locale]" rows="2" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border" placeholder="Review and edit translation..."></textarea>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        <div x-show="tab === 'all'" x-transition x-cloak>
            <div class="bg-white shadow-sm ring-1 ring-black ring-opacity-5 md:rounded-lg overflow-hidden overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 max-w-xs w-64">Key</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 min-w-[200px]">Base ({{ $defaultLang }})</th>
                            @foreach($locales as $locale)
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900 min-w-[250px]">{{ $locale }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($allTranslations as $key => $data)
                        <tr x-data="editRow('{{ addslashes($key) }}', {{ json_encode($data['translations'] ?? []) }})" class="hover:bg-gray-50 transition-colors">
                            <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 align-top max-w-xs break-words">
                                <div class="font-mono text-xs text-indigo-600 break-words">{{ $key }}</div>
                            </td>
                            <td class="py-4 px-3 text-sm text-gray-500 align-top break-words">
                                <div class="whitespace-pre-wrap">{{ $data['base_value'] }}</div>
                            </td>
                            @foreach($locales as $locale)
                            <td class="py-4 px-3 text-sm text-gray-500 align-top group break-words relative">
                                <div x-show="!editing['{{ $locale }}']">
                                    <div class="whitespace-pre-wrap pr-6" :class="{'opacity-50 italic': !hasTranslation('{{ $locale }}')}"><span x-text="getTranslation('{{ $locale }}') || 'Missing'"></span></div>
                                    <button @click="startEdit('{{ $locale }}')" class="absolute top-4 right-2 opacity-0 group-hover:opacity-100 text-indigo-600 hover:text-indigo-900 transition-opacity p-1 bg-white rounded shadow-sm border">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </button>
                                </div>
                                <div x-show="editing['{{ $locale }}']" x-cloak class="flex flex-col space-y-2">
                                    <textarea x-model="drafts['{{ $locale }}']" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border" rows="3"></textarea>
                                    <div class="flex space-x-2">
                                        <button @click="save('{{ $locale }}')" class="text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 px-3 py-1.5 rounded shadow-sm">Save</button>
                                        <button @click="cancelEdit('{{ $locale }}')" class="text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 px-3 py-1.5 rounded shadow-sm">Cancel</button>
                                    </div>
                                </div>
                            </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function translationDashboard() {
            return {
                tab: 'missing',
                generateAll() {
                    this.$event.target.dispatchEvent(new CustomEvent('generate-all', { bubbles: true }));
                }
            }
        }

        function editRow(key, translations) {
            return {
                key: key,
                translations: translations,
                drafts: {},
                editing: {},
                
                init() {
                    const locales = {!! json_encode($locales) !!};
                    locales.forEach(locale => {
                        this.editing[locale] = false;
                        this.drafts[locale] = '';
                    });
                },
                
                hasTranslation(locale) {
                    return this.translations[locale] !== undefined && this.translations[locale] !== null;
                },
                
                getTranslation(locale) {
                    return this.translations[locale];
                },
                
                startEdit(locale) {
                    this.drafts[locale] = this.translations[locale] || '';
                    this.editing[locale] = true;
                },
                
                cancelEdit(locale) {
                    this.editing[locale] = false;
                },
                
                async save(locale) {
                    const translation = this.drafts[locale];
                    try {
                        const response = await fetch('{{ route('insta-translate.api.save') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.key,
                                translation: translation,
                                target_locale: locale,
                                mode: '{{ $mode }}'
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.translations[locale] = translation;
                            this.editing[locale] = false;
                        } else {
                            alert('Error saving translation: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while saving translation.');
                    }
                }
            }
        }

        function translationRow(key, baseValue, locales) {
            return {
                key: key,
                baseValue: baseValue,
                missingLocales: locales,
                drafts: {},
                generating: {},
                resolved: {},
                
                init() {
                    locales.forEach(l => {
                        this.drafts[l] = '';
                        this.generating[l] = false;
                        this.resolved[l] = false;
                    });

                    window.addEventListener('generate-all', () => {
                        this.missingLocales.forEach(locale => {
                            if (!this.hasDraft(locale) && !this.isResolved(locale)) {
                                this.generate(locale);
                            }
                        });
                    });
                },

                isGenerating(locale) {
                    return this.generating[locale];
                },

                hasDraft(locale) {
                    return this.drafts[locale] !== '';
                },

                isResolved(locale) {
                    return this.resolved[locale];
                },

                get isFullyResolved() {
                    return this.missingLocales.every(l => this.resolved[l]);
                },

                async generate(locale) {
                    this.generating[locale] = true;
                    try {
                        const response = await fetch('{{ route('insta-translate.api.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.key,
                                base_value: this.baseValue,
                                target_locale: locale
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.drafts[locale] = data.translation;
                        } else {
                            alert('Error generating translation: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while generating translation.');
                    } finally {
                        this.generating[locale] = false;
                    }
                },

                async approve(locale) {
                    const translation = this.drafts[locale];
                    try {
                        const response = await fetch('{{ route('insta-translate.api.save') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.key,
                                translation: translation,
                                target_locale: locale,
                                mode: '{{ $mode }}'
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.resolved[locale] = true;
                        } else {
                            alert('Error saving translation: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while saving translation.');
                    }
                }
            }
        }
    </script>
</body>
</html>
