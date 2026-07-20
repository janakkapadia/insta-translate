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
                        <tr x-data='translationRow("{{ addslashes($key) }}", "{{ addslashes($data['base_value']) }}", {!! json_encode($data['missing_in']) !!})' x-show="!isFullyResolved" class="hover:bg-gray-50 transition-colors">
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

        <div x-show="tab === 'all'" x-transition x-cloak class="flex flex-col h-[calc(100vh-12rem)]">
            <div class="mb-4">
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text" x-model="searchQuery" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 border" placeholder="Search keys or base values...">
                </div>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-black ring-opacity-5 md:rounded-lg overflow-hidden flex-1 overflow-y-auto mb-4">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 w-1/3">Key</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Base ({{ $defaultLang }})</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:pr-6 w-32">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template x-for="data in paginatedList" :key="data.key">
                            <tr class="hover:bg-gray-50 cursor-pointer transition-colors" @click="openSlideOver(data.key, data)">
                                <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 align-top break-words">
                                    <div class="font-mono text-xs text-indigo-600 break-all" x-text="data.key"></div>
                                </td>
                                <td class="py-4 px-3 text-sm text-gray-500 align-top break-words">
                                    <div class="whitespace-pre-wrap" x-text="data.base_value"></div>
                                </td>
                                <td class="py-4 px-3 sm:pr-6 text-sm text-gray-500 align-top text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                          :class="Object.keys(data.translations).length === {{ count($locales) }} ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                        <span x-text="Object.keys(data.translations).length"></span> / {{ count($locales) }}
                                    </span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
                
                <div x-show="!hasSearchResults()" class="p-12 text-center text-gray-500" x-cloak>
                    No translations found matching your search.
                </div>
            </div>

            <div class="px-4 py-3 sm:px-6 flex items-center justify-between mb-4" x-show="totalPages > 1" x-cloak>
                <div class="flex-1 flex justify-between sm:hidden">
                    <button @click="prevPage()" :disabled="page === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">Previous</button>
                    <button @click="nextPage()" :disabled="page === totalPages" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">Next</button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing <span class="font-medium" x-text="(page - 1) * perPage + 1"></span> to <span class="font-medium" x-text="Math.min(page * perPage, filteredList.length)"></span> of <span class="font-medium" x-text="filteredList.length"></span> results
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button @click="prevPage()" :disabled="page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                <span class="sr-only">Previous</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                            </button>
                            <button @click="nextPage()" :disabled="page === totalPages" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                                <span class="sr-only">Next</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide-over panel -->
        <div x-show="isSlideOverOpen" class="fixed inset-0 z-50 overflow-hidden" aria-labelledby="slide-over-title" role="dialog" aria-modal="true" x-cloak>
            <div class="absolute inset-0 overflow-hidden">
                <!-- Background overlay -->
                <div x-show="isSlideOverOpen" x-transition.opacity class="absolute inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeSlideOver()"></div>

                <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                    <div x-show="isSlideOverOpen" 
                         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-500" 
                         x-transition:enter-start="translate-x-full" 
                         x-transition:enter-end="translate-x-0" 
                         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-500" 
                         x-transition:leave-start="translate-x-0" 
                         x-transition:leave-end="translate-x-full" 
                         class="pointer-events-auto w-screen max-w-lg">
                        <div class="flex h-full flex-col overflow-y-scroll bg-white shadow-xl">
                            <div class="bg-indigo-700 px-4 py-6 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-medium text-white" id="slide-over-title">Edit Translations</h2>
                                    <div class="ml-3 flex h-7 items-center">
                                        <button type="button" @click="closeSlideOver()" class="rounded-md bg-indigo-700 text-indigo-200 hover:text-white focus:outline-none focus:ring-2 focus:ring-white">
                                            <span class="sr-only">Close panel</span>
                                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <p class="text-sm text-indigo-200 font-mono break-all" x-text="selectedKey"></p>
                                </div>
                            </div>
                            
                            <div class="relative flex-1 px-4 py-6 sm:px-6">
                                <div class="mb-6 pb-6 border-b border-gray-200">
                                    <h3 class="text-sm font-medium text-gray-900 mb-2">Base Value ({{ $defaultLang }})</h3>
                                    <div class="bg-gray-50 rounded-md p-3 text-sm text-gray-700 whitespace-pre-wrap border border-gray-200 mb-4" x-text="selectedData?.base_value"></div>
                                    
                                    <label for="translation_context" class="block text-sm font-medium text-gray-700 mb-1">Optional Context (helps AI translate better)</label>
                                    <input type="text" id="translation_context" x-model="translationContext" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="e.g. 'Button label on checkout page' or 'SaaS billing dashboard'">
                                </div>
                                
                                <div class="space-y-6">
                                    <template x-for='locale in {!! json_encode($locales) !!}' :key="locale">
                                        <div class="bg-white border rounded-lg overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                                            <div class="bg-gray-50 px-3 py-2 border-b flex justify-between items-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="locale"></span>
                                                <span x-show="slideOverSaved[locale]" class="text-green-600 text-xs font-medium flex items-center" x-cloak>
                                                    <svg class="mr-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Saved
                                                </span>
                                            </div>
                                            <div class="p-3">
                                                <textarea x-model="slideOverDrafts[locale]" rows="3" class="block w-full border-0 p-0 text-gray-900 placeholder-gray-500 focus:ring-0 sm:text-sm resize-y" placeholder="Translation missing..."></textarea>
                                                
                                                <div x-show="slideOverGeneratedDrafts[locale] !== null" class="mt-3 bg-indigo-50 border border-indigo-100 rounded-md p-3" x-cloak>
                                                    <h4 class="text-xs font-medium text-indigo-800 mb-1">AI Suggestion:</h4>
                                                    <p class="text-sm text-indigo-900 whitespace-pre-wrap mb-3" x-text="slideOverGeneratedDrafts[locale]"></p>
                                                    <div class="flex space-x-2">
                                                        <button @click="slideOverDrafts[locale] = slideOverGeneratedDrafts[locale]; slideOverGeneratedDrafts[locale] = null" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                            Accept & Update
                                                        </button>
                                                        <button @click="slideOverGeneratedDrafts[locale] = null" class="inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                            Discard
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 px-3 py-2 flex justify-end border-t space-x-2">
                                                <button @click="regenerateSlideOverTranslation(locale)" :disabled="slideOverGenerating[locale] || slideOverSaving[locale]" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                                    <svg x-show="slideOverGenerating[locale]" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    <svg x-show="!slideOverGenerating[locale]" class="-ml-1 mr-1.5 h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    Regenerate
                                                </button>
                                                <button @click="saveSlideOverTranslation(locale)" :disabled="slideOverSaving[locale] || slideOverGenerating[locale]" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                                    <svg x-show="slideOverSaving[locale]" class="animate-spin -ml-1 mr-1.5 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Save
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function translationDashboard() {
            return {
                tab: 'missing',
                searchQuery: '',
                page: 1,
                perPage: 50,
                
                isSlideOverOpen: false,
                selectedKey: '',
                selectedData: null,
                translationContext: '',
                slideOverDrafts: {},
                slideOverSaving: {},
                slideOverSaved: {},
                slideOverGenerating: {},
                slideOverGeneratedDrafts: {},
                
                allTranslationsList: {!! json_encode(collect($allTranslations)->map(function($data, $key) { $data['key'] = $key; return $data; })->values()) !!},
                
                get filteredList() {
                    if (this.searchQuery === '') {
                        return this.allTranslationsList;
                    }
                    const q = this.searchQuery.toLowerCase();
                    return this.allTranslationsList.filter(item => {
                        if (item.key.toLowerCase().includes(q)) return true;
                        if (item.base_value && item.base_value.toLowerCase().includes(q)) return true;
                        
                        for (const locale in item.translations) {
                            if (item.translations[locale] && item.translations[locale].toLowerCase().includes(q)) return true;
                        }
                        
                        return false;
                    });
                },
                
                get paginatedList() {
                    const start = (this.page - 1) * this.perPage;
                    const end = start + this.perPage;
                    return this.filteredList.slice(start, end);
                },
                
                get totalPages() {
                    return Math.max(1, Math.ceil(this.filteredList.length / this.perPage));
                },
                
                nextPage() {
                    if (this.page < this.totalPages) this.page++;
                },
                
                prevPage() {
                    if (this.page > 1) this.page--;
                },
                
                init() {
                    this.$watch('searchQuery', () => {
                        this.page = 1;
                    });
                },

                generateAll() {
                    this.$event.target.dispatchEvent(new CustomEvent('generate-all', { bubbles: true }));
                },
                hasSearchResults() {
                    return this.filteredList.length > 0;
                },
                openSlideOver(key, data) {
                    this.selectedKey = key;
                    this.selectedData = data;
                    this.translationContext = '';
                    
                    this.slideOverDrafts = {};
                    this.slideOverSaving = {};
                    this.slideOverSaved = {};
                    this.slideOverGenerating = {};
                    this.slideOverGeneratedDrafts = {};
                    
                    const locales = {!! json_encode($locales) !!};
                    locales.forEach(locale => {
                        this.slideOverDrafts[locale] = data.translations[locale] || '';
                        this.slideOverSaving[locale] = false;
                        this.slideOverSaved[locale] = false;
                        this.slideOverGenerating[locale] = false;
                        this.slideOverGeneratedDrafts[locale] = null;
                    });
                    
                    this.isSlideOverOpen = true;
                },
                closeSlideOver() {
                    this.isSlideOverOpen = false;
                    setTimeout(() => {
                        this.selectedKey = '';
                        this.selectedData = null;
                        this.translationContext = '';
                        this.slideOverDrafts = {};
                        this.slideOverSaved = {};
                        this.slideOverSaving = {};
                        this.slideOverGenerating = {};
                        this.slideOverGeneratedDrafts = {};
                    }, 300);
                },
                async saveSlideOverTranslation(locale) {
                    this.slideOverSaving[locale] = true;
                    this.slideOverSaved[locale] = false;
                    
                    const translation = this.slideOverDrafts[locale];
                    try {
                        const response = await fetch('{{ route('insta-translate.api.save') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.selectedKey,
                                translation: translation,
                                target_locale: locale,
                                mode: '{{ $mode }}'
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.slideOverSaved[locale] = true;
                            this.selectedData.translations[locale] = translation;
                            setTimeout(() => {
                                this.slideOverSaved[locale] = false;
                            }, 2000);
                        } else {
                            alert('Error saving translation: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while saving translation.');
                    } finally {
                        this.slideOverSaving[locale] = false;
                    }
                },
                async regenerateSlideOverTranslation(locale) {
                    this.slideOverGenerating[locale] = true;
                    this.slideOverSaved[locale] = false;
                    this.slideOverGeneratedDrafts[locale] = null;
                    
                    try {
                        const response = await fetch('{{ route('insta-translate.api.generate') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.selectedKey,
                                base_value: this.selectedData.base_value,
                                target_locale: locale,
                                context: this.translationContext
                            })
                        });
                        
                        let data;
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            console.error('Server returned non-JSON:', text);
                            alert('Server error while generating translation. Check console for details. Status: ' + response.status);
                            return;
                        }
                        
                        if (data.success) {
                            this.slideOverGeneratedDrafts[locale] = data.translation;
                        } else {
                            alert('Error generating translation: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        console.error('Network error:', e);
                        alert('Network error while generating translation: ' + e.message);
                    } finally {
                        this.slideOverGenerating[locale] = false;
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
