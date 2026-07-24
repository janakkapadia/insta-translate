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
                
                <div class="flex space-x-3 items-center">
                    <div class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white">
                        <svg class="-ml-0.5 mr-2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="text-xs text-gray-500 mr-2 font-normal">Batch Size:</span>
                        <select x-model.number="batchSize" class="text-xs font-semibold text-gray-800 border-0 p-0 focus:ring-0 bg-transparent cursor-pointer">
                            <option :value="25">25 / batch</option>
                            <option :value="50">50 / batch</option>
                            <option :value="100">100 / batch (Default)</option>
                            <option :value="200">200 / batch</option>
                            <option :value="500">500 / batch</option>
                        </select>
                    </div>
                    <button @click="showAddLanguageModal = true" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Language
                    </button>
                    <button x-show="tab === 'missing'" @click="generateAll()" :disabled="isGeneratingAll" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-75 disabled:cursor-not-allowed">
                        <svg x-show="!isGeneratingAll" class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        <svg x-show="isGeneratingAll" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-show="!isGeneratingAll">Generate All Missing</span>
                        <span x-show="isGeneratingAll" x-text="`Generating (${batchProgress}/${batchTotal})...`" x-cloak></span>
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
                        <tr x-data='translationRow("{{ addslashes($key) }}", "{{ is_array($data['base_value']) ? addslashes(json_encode($data['base_value'])) : addslashes($data['base_value']) }}", {!! json_encode($data['missing_in']) !!})' x-show="!isFullyResolved" class="hover:bg-gray-50 transition-colors">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 align-top">
                                <div class="font-mono text-xs text-indigo-600 break-all">{{ $key }}</div>
                            </td>
                            <td class="py-4 px-3 text-sm text-gray-500 align-top">
                                <div class="whitespace-pre-wrap">{{ is_array($data['base_value']) ? json_encode($data['base_value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $data['base_value'] }}</div>
                            </td>
                            <td class="py-4 px-3 text-sm text-gray-500 align-top">
                                <div class="space-y-4">
                                    <div class="flex space-x-2 pb-2 border-b border-gray-100" x-show="missingLocalesList.length > 1">
                                        <button @click="generateAllLocales()" class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <svg class="-ml-0.5 mr-2 h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                            Generate All Missing Locales (<span x-text="missingLocalesList.length"></span>)
                                        </button>
                                        
                                        <button x-show="hasAnyDraft" @click="approveAllLocales()" x-cloak class="inline-flex items-center px-3 py-1.5 border border-transparent shadow-sm text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                            <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Approve All
                                        </button>
                                    </div>
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
                                                    <span x-text="langName(locale)"></span>
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
            <div class="mb-4 flex flex-col sm:flex-row gap-4">
                <div class="relative rounded-md shadow-sm flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input type="text" x-model="searchQuery" class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-10 sm:text-sm border-gray-300 rounded-md py-2 border" placeholder="Search keys or base values...">
                </div>
                <div class="sm:w-48">
                    <select x-model="selectedLanguage" x-init="$nextTick(() => { $el.querySelectorAll('option:not([value=all])').forEach(o => { if(window._langMap[o.value]) o.textContent = window._langMap[o.value] + ' (' + o.value + ')'; }); })" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm py-2 px-3 border">
                        <option value="all">All Languages</option>
                        @foreach($locales as $locale)
                        <option value="{{ $locale }}">{{ $locale }}</option>
                        @endforeach
                    </select>
                </div>
                <button x-show="selectedLanguage !== 'all' && selectedKeys.length > 0" @click="regenerateBatch()" :disabled="isGeneratingBatch" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors disabled:opacity-50" x-cloak>
                    <svg x-show="isGeneratingBatch" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    <span x-text="isGeneratingBatch ? 'Regenerating...' : 'Regenerate Selected (' + selectedKeys.length + ')'"></span>
                </button>
            </div>

            <div class="bg-white shadow-sm ring-1 ring-black ring-opacity-5 md:rounded-lg overflow-hidden flex-1 overflow-y-auto mb-4">
                <table class="min-w-full divide-y divide-gray-300">
                    <thead class="bg-gray-50 sticky top-0 z-10">
                        <tr>
                            <th scope="col" class="relative w-12 px-6 sm:w-16 sm:px-8" x-show="selectedLanguage !== 'all'" x-cloak>
                                <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6" @change="toggleSelectAll($event)" :checked="selectedKeys.length > 0 && selectedKeys.length === paginatedList.length">
                            </th>
                            <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6 w-1/3">Key</th>
                            <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Base ({{ $defaultLang }})</th>
                            <th scope="col" class="px-3 py-3.5 text-right text-sm font-semibold text-gray-900 sm:pr-6 w-32" x-text="selectedLanguage === 'all' ? 'Status' : 'Translation (' + selectedLanguage + ')'"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <template x-for="data in paginatedList" :key="data.key">
                            <tr class="hover:bg-gray-50 transition-colors" :class="selectedKeys.includes(data.key) ? 'bg-indigo-50' : ''">
                                <td class="relative w-12 px-6 sm:w-16 sm:px-8" x-show="selectedLanguage !== 'all'" x-cloak>
                                    <input type="checkbox" class="absolute left-4 top-1/2 -mt-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 sm:left-6" :value="data.key" x-model="selectedKeys">
                                </td>
                                <td class="py-4 pl-4 pr-3 text-sm font-medium text-gray-900 sm:pl-6 align-top break-words cursor-pointer" @click="openSlideOver(data.key, data)">
                                    <div class="font-mono text-xs text-indigo-600 break-all" x-text="data.key"></div>
                                </td>
                                <td class="py-4 px-3 text-sm text-gray-500 align-top break-words cursor-pointer" @click="openSlideOver(data.key, data)">
                                    <div class="whitespace-pre-wrap" x-text="typeof data.base_value === 'string' ? data.base_value : JSON.stringify(data.base_value, null, 2)"></div>
                                </td>
                                <td class="py-4 px-3 sm:pr-6 text-sm text-gray-500 align-top text-right cursor-pointer" @click="openSlideOver(data.key, data)">
                                    <div x-show="selectedLanguage === 'all'">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                              :class="Object.keys(data.translations).length === {{ count($locales) }} ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'">
                                            <span x-text="Object.keys(data.translations).length"></span> / {{ count($locales) }}
                                        </span>
                                    </div>
                                    <div x-show="selectedLanguage !== 'all'" class="text-left" x-cloak>
                                        <div class="whitespace-pre-wrap break-words text-gray-900" x-text="data.translations[selectedLanguage] ? (typeof data.translations[selectedLanguage] === 'string' ? data.translations[selectedLanguage] : JSON.stringify(data.translations[selectedLanguage], null, 2)) : '-'"></div>
                                    </div>
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
                                    <div class="bg-gray-50 rounded-md p-3 text-sm text-gray-700 whitespace-pre-wrap border border-gray-200 mb-4" x-text="typeof selectedData?.base_value === 'string' ? selectedData?.base_value : JSON.stringify(selectedData?.base_value, null, 2)"></div>
                                    
                                    <label for="translation_context" class="block text-sm font-medium text-gray-700 mb-1">Optional Context (helps AI translate better)</label>
                                    <input type="text" id="translation_context" x-model="translationContext" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border" placeholder="e.g. 'Button label on checkout page' or 'SaaS billing dashboard'">
                                </div>
                                
                                <div class="flex space-x-3 mb-6">
                                    <button @click="generateAllSlideOver()" :disabled="isGeneratingMultiSlideOver" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                        <svg x-show="isGeneratingMultiSlideOver" class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Translate All Locales
                                    </button>
                                    <button @click="saveAllSlideOver()" :disabled="isSavingMultiSlideOver || !hasAnySlideOverDraft" class="flex-1 inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                                        <svg x-show="isSavingMultiSlideOver" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" x-cloak>
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Accept All
                                    </button>
                                </div>
                                
                                <div class="space-y-6">
                                    <template x-for='locale in {!! json_encode($locales) !!}' :key="locale">
                                        <div class="bg-white border rounded-lg overflow-hidden shadow-sm focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                                            <div class="bg-gray-50 px-3 py-2 border-b flex justify-between items-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800" x-text="langName(locale)"></span>
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
        <!-- Add Language Modal -->
        <div x-show="showAddLanguageModal" class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" x-cloak>
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showAddLanguageModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="showAddLanguageModal = false"></div>

                <!-- This element is to trick the browser into centering the modal contents. -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showAddLanguageModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-visible shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                    <div>
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100">
                            <svg class="h-6 w-6 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                Add New Language
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    Select the language you want to add from the list below.
                                </p>
                            </div>
                            <div class="mt-4">
                                <div class="relative" x-data="{ langQuery: '', showLangDropdown: false }" @click.away="showLangDropdown = false">
                                    <input type="text" x-model="langQuery" @focus="showLangDropdown = true" @input="showLangDropdown = true" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md px-3 py-2 border" placeholder="Search languages...">
                                    <div x-show="showLangDropdown" x-cloak class="absolute z-20 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm text-left">
                                        <template x-for="lang in availableLanguages.filter(l => !langQuery || l.name.toLowerCase().includes(langQuery.toLowerCase()) || l.code.toLowerCase().includes(langQuery.toLowerCase()))" :key="lang.code">
                                            <div @click="langQuery = lang.name + ' (' + lang.code + ')'; showLangDropdown = false; newLanguageCode = lang.code;" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-indigo-50 transition-colors" :class="newLanguageCode === lang.code ? 'bg-indigo-50 text-indigo-700' : 'text-gray-900'">
                                                <span class="block truncate" x-text="lang.name + ' (' + lang.code + ')'"></span>
                                            </div>
                                        </template>
                                        <div x-show="langQuery.length > 0 && availableLanguages.filter(l => l.name.toLowerCase().includes(langQuery.toLowerCase()) || l.code.toLowerCase().includes(langQuery.toLowerCase())).length === 0" class="py-3 text-sm text-gray-500 text-center">
                                            No languages found.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                        <button type="button" @click="addLanguage" :disabled="isAddingLanguage || !newLanguageCode" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm disabled:opacity-50 flex items-center">
                            <svg x-show="isAddingLanguage" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Add Language
                        </button>
                        <button type="button" @click="showAddLanguageModal = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Comprehensive language name map
        window._langMap = {
            'af': 'Afrikaans', 'am': 'Amharic', 'ar': 'Arabic', 'az': 'Azerbaijani',
            'be': 'Belarusian', 'bg': 'Bulgarian', 'bn': 'Bengali', 'bs': 'Bosnian',
            'ca': 'Catalan', 'cs': 'Czech', 'cy': 'Welsh', 'da': 'Danish',
            'de': 'German', 'el': 'Greek', 'en': 'English', 'es': 'Spanish',
            'et': 'Estonian', 'eu': 'Basque', 'fa': 'Persian', 'fi': 'Finnish',
            'fil': 'Filipino', 'fr': 'French', 'ga': 'Irish', 'gl': 'Galician',
            'gu': 'Gujarati', 'ha': 'Hausa', 'he': 'Hebrew', 'hi': 'Hindi',
            'hr': 'Croatian', 'hu': 'Hungarian', 'hy': 'Armenian', 'id': 'Indonesian',
            'ig': 'Igbo', 'is': 'Icelandic', 'it': 'Italian', 'ja': 'Japanese',
            'jv': 'Javanese', 'ka': 'Georgian', 'kk': 'Kazakh', 'km': 'Khmer',
            'kn': 'Kannada', 'ko': 'Korean', 'ku': 'Kurdish', 'ky': 'Kyrgyz',
            'lo': 'Lao', 'lt': 'Lithuanian', 'lv': 'Latvian', 'mg': 'Malagasy',
            'mk': 'Macedonian', 'ml': 'Malayalam', 'mn': 'Mongolian', 'mr': 'Marathi',
            'ms': 'Malay', 'mt': 'Maltese', 'my': 'Burmese', 'nb': 'Norwegian Bokmål',
            'ne': 'Nepali', 'nl': 'Dutch', 'nn': 'Norwegian Nynorsk', 'no': 'Norwegian',
            'or': 'Odia', 'pa': 'Punjabi', 'pl': 'Polish', 'ps': 'Pashto',
            'pt': 'Portuguese', 'pt-BR': 'Portuguese (Brazil / Brasil)', 'pt-PT': 'Portuguese (Portugal)', 'ro': 'Romanian',
            'ru': 'Russian', 'rw': 'Kinyarwanda', 'si': 'Sinhala', 'sk': 'Slovak',
            'sl': 'Slovenian', 'so': 'Somali', 'sq': 'Albanian', 'sr': 'Serbian',
            'sv': 'Swedish', 'sw': 'Swahili', 'ta': 'Tamil', 'te': 'Telugu',
            'tg': 'Tajik', 'th': 'Thai', 'tk': 'Turkmen', 'tl': 'Tagalog',
            'tr': 'Turkish', 'uk': 'Ukrainian', 'ur': 'Urdu', 'uz': 'Uzbek',
            'vi': 'Vietnamese', 'xh': 'Xhosa', 'yo': 'Yoruba', 'zh': 'Chinese',
            'zh-CN': 'Chinese (Simplified)', 'zh-TW': 'Chinese (Traditional)',
            'zu': 'Zulu'
        };


        function translationDashboard() {
            return {
                tab: 'missing',
                searchQuery: '',
                selectedLanguage: 'all',
                selectedKeys: [],
                isGeneratingBatch: false,
                batchSize: 100,
                page: 1,
                perPage: 50,
                
                showAddLanguageModal: false,
                newLanguageCode: '',
                isAddingLanguage: false,

                availableLanguages: Object.entries(window._langMap)
                    .map(([code, name]) => ({ code, name }))
                    .filter(lang => lang.code !== '{{ $defaultLang }}' && !{!! json_encode($locales) !!}.includes(lang.code))
                    .sort((a, b) => a.name.localeCompare(b.name)),
                
                isSlideOverOpen: false,
                selectedKey: '',
                selectedData: null,
                translationContext: '',
                slideOverDrafts: {},
                slideOverSaving: {},
                slideOverSaved: {},
                slideOverGenerating: {},
                slideOverGeneratedDrafts: {},
                isGeneratingMultiSlideOver: false,
                isSavingMultiSlideOver: false,
                
                get hasAnySlideOverDraft() {
                    return Object.keys(this.slideOverGeneratedDrafts).some(locale => this.slideOverGeneratedDrafts[locale] !== null);
                },
                
                isGeneratingAll: false,
                batchProgress: 0,
                batchTotal: 0,
                
                missingTranslationsList: {!! json_encode(collect($missingTranslations)->map(function($data, $key) { $data['key'] = $key; return $data; })->values()) !!},
                allTranslationsList: {!! json_encode(collect($allTranslations)->map(function($data, $key) { $data['key'] = $key; return $data; })->values()) !!},

                langName(code) {
                    return window._langMap[code] ? window._langMap[code] + ' (' + code + ')' : code;
                },
                
                get filteredList() {
                    let list = this.allTranslationsList;
                    
                    if (this.searchQuery !== '') {
                        const q = this.searchQuery.toLowerCase();
                        list = list.filter(item => {
                            if (item.key.toLowerCase().includes(q)) return true;
                            let bv = typeof item.base_value === 'string' ? item.base_value : JSON.stringify(item.base_value);
                            if (bv && bv.toLowerCase().includes(q)) return true;
                            
                            if (this.selectedLanguage !== 'all') {
                                let tv = item.translations[this.selectedLanguage];
                                if (tv) {
                                    tv = typeof tv === 'string' ? tv : JSON.stringify(tv);
                                    if (tv.toLowerCase().includes(q)) return true;
                                }
                            } else {
                                for (const locale in item.translations) {
                                    let tv = typeof item.translations[locale] === 'string' ? item.translations[locale] : JSON.stringify(item.translations[locale]);
                                    if (tv && tv.toLowerCase().includes(q)) return true;
                                }
                            }
                            
                            return false;
                        });
                    }
                    return list;
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
                        this.selectedKeys = [];
                    });
                    this.$watch('selectedLanguage', () => {
                        this.page = 1;
                        this.selectedKeys = [];
                    });
                },

                toggleSelectAll(e) {
                    if (e.target.checked) {
                        this.selectedKeys = this.paginatedList.map(item => item.key);
                    } else {
                        this.selectedKeys = [];
                    }
                },
                
                async regenerateBatch() {
                    if (this.selectedKeys.length === 0 || this.selectedLanguage === 'all') return;
                    
                    this.isGeneratingBatch = true;
                    const chunkSize = this.batchSize || 100;
                    let totalGenerated = 0;
                    
                    for (let i = 0; i < this.selectedKeys.length; i += chunkSize) {
                        const chunkKeys = this.selectedKeys.slice(i, i + chunkSize);
                        const itemsToRegenerate = chunkKeys.map(key => {
                            const item = this.allTranslationsList.find(k => k.key === key);
                            return { key: key, base_value: item ? item.base_value : '' };
                        });
                        
                        try {
                            const response = await fetch('{{ route('insta-translate.api.generate-batch') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    items: itemsToRegenerate,
                                    target_locale: this.selectedLanguage,
                                    mode: '{{ $mode }}'
                                })
                            });
                            
                            const data = await response.json();
                            if (data.success && data.translations) {
                                for (const key in data.translations) {
                                    const item = this.allTranslationsList.find(k => k.key === key);
                                    if (item) {
                                        item.translations[this.selectedLanguage] = data.translations[key];
                                    }
                                }
                                totalGenerated += Object.keys(data.translations).length;
                            } else {
                                alert('Error: ' + (data.error || 'Unknown error'));
                                break;
                            }
                        } catch (e) {
                            alert('Network error while generating batch.');
                            break;
                        }
                    }
                    
                    this.selectedKeys = [];
                    this.isGeneratingBatch = false;
                    if (totalGenerated > 0) {
                        alert('Successfully regenerated ' + totalGenerated + ' translations!');
                    }
                },

                async generateAll() {
                    this.isGeneratingAll = true;
                    this.batchProgress = 0;
                    this.batchTotal = 0;
                    
                    const localeTasks = {};
                    this.missingTranslationsList.forEach(item => {
                        item.missing_in.forEach(locale => {
                            if (!localeTasks[locale]) localeTasks[locale] = {};
                            localeTasks[locale][item.key] = item.base_value;
                            this.batchTotal++;
                        });
                    });
                    
                    if (this.batchTotal === 0) {
                        this.isGeneratingAll = false;
                        return;
                    }
                    
                    for (const locale in localeTasks) {
                        const keys = Object.keys(localeTasks[locale]);
                        const chunkSize = this.batchSize || 100;
                        
                        for (let i = 0; i < keys.length; i += chunkSize) {
                            const chunkKeys = keys.slice(i, i + chunkSize);
                            const chunkItems = {};
                            chunkKeys.forEach(k => chunkItems[k] = localeTasks[locale][k]);
                            
                            // Reformat for /api/generate-batch API. 
                            // Wait, the API takes `items` as an array of objects [{key, base_value}] 
                            // Let's verify the API structure! 
                            // Ah, in `regenerateBatch` we send: items = [{key: '...', base_value: '...'}]. 
                            // Let's format it correctly!
                            const itemsToRegenerate = chunkKeys.map(k => ({
                                key: k,
                                base_value: chunkItems[k]
                            }));
                            
                            let retries = 0;
                            let success = false;
                            
                            while (retries < 3 && !success) {
                                try {
                                    const response = await fetch('{{ route('insta-translate.api.generate-batch') }}', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                            'Accept': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            items: itemsToRegenerate,
                                            target_locale: locale,
                                            mode: '{{ $mode }}'
                                        })
                                    });
                                    
                                    const data = await response.json();
                                    if (data.success) {
                                        this.batchProgress += chunkKeys.length;
                                        window.dispatchEvent(new CustomEvent('batch-resolved', { 
                                            detail: { locale, translations: data.translations } 
                                        }));
                                        success = true;
                                    } else {
                                        console.error('Error generating batch:', data.error);
                                        retries++;
                                        if (retries < 3) {
                                            console.log('Retrying in 30 seconds...');
                                            await new Promise(resolve => setTimeout(resolve, 30000));
                                        }
                                    }
                                } catch (e) {
                                    console.error('Network error while generating batch:', e);
                                    retries++;
                                    if (retries < 3) {
                                        console.log('Retrying in 30 seconds...');
                                        await new Promise(resolve => setTimeout(resolve, 30000));
                                    }
                                }
                            }
                            
                            if (!success) {
                                const skip = confirm('Failed to process a batch for [' + locale + '] after 3 attempts. Do you want to skip this batch and continue with the rest?');
                                if (!skip) {
                                    this.isGeneratingAll = false;
                                    return;
                                }
                            }
                        }
                    }
                    
                    window.location.reload();
                },
                async addLanguage() {
                    if (!this.newLanguageCode) return;
                    this.isAddingLanguage = true;
                    
                    try {
                        const response = await fetch('{{ route('insta-translate.api.add-language') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                target_locale: this.newLanguageCode,
                                mode: '{{ $mode }}'
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert('Error adding language: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while adding language.');
                    } finally {
                        this.isAddingLanguage = false;
                    }
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
                            window.dispatchEvent(new CustomEvent('translation-saved', { detail: { key: this.selectedKey, locales: [locale] } }));
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
                },
                async generateAllSlideOver() {
                    this.isGeneratingMultiSlideOver = true;
                    const localesToGenerate = {!! json_encode($locales) !!};
                    
                    try {
                        const response = await fetch('{{ route('insta-translate.api.generate-multi-lang') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.selectedKey,
                                base_value: this.selectedData.base_value,
                                target_locales: localesToGenerate,
                                context: this.translationContext
                            })
                        });
                        
                        let data;
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            data = await response.json();
                        } else {
                            const text = await response.text();
                            alert('Server error. Status: ' + response.status);
                            return;
                        }
                        
                        if (data.success && data.translations) {
                            for (const locale in data.translations) {
                                this.slideOverGeneratedDrafts[locale] = data.translations[locale];
                            }
                        } else {
                            alert('Error generating translations: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while generating translations: ' + e.message);
                    } finally {
                        this.isGeneratingMultiSlideOver = false;
                    }
                },
                async saveAllSlideOver() {
                    this.isSavingMultiSlideOver = true;
                    
                    const translationsToSave = {};
                    for (const locale in this.slideOverGeneratedDrafts) {
                        if (this.slideOverGeneratedDrafts[locale] !== null) {
                            translationsToSave[locale] = this.slideOverGeneratedDrafts[locale];
                        }
                    }
                    
                    if (Object.keys(translationsToSave).length === 0) {
                        this.isSavingMultiSlideOver = false;
                        return;
                    }
                    
                    try {
                        const response = await fetch('{{ route('insta-translate.api.save-multi-lang') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.selectedKey,
                                translations: translationsToSave,
                                mode: '{{ $mode }}'
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            for (const locale in translationsToSave) {
                                this.slideOverDrafts[locale] = translationsToSave[locale];
                                this.selectedData.translations[locale] = translationsToSave[locale];
                                this.slideOverGeneratedDrafts[locale] = null;
                                
                                this.slideOverSaved[locale] = true;
                                setTimeout(() => {
                                    this.slideOverSaved[locale] = false;
                                }, 2000);
                            }
                            window.dispatchEvent(new CustomEvent('translation-saved', { detail: { key: this.selectedKey, locales: Object.keys(translationsToSave) } }));
                        } else {
                            alert('Error saving translations: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while saving translations.');
                    } finally {
                        this.isSavingMultiSlideOver = false;
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

                    window.addEventListener('batch-resolved', (e) => {
                        const { locale, translations } = e.detail;
                        if (this.missingLocales.includes(locale) && translations[this.key] !== undefined) {
                            this.drafts[locale] = translations[this.key];
                            this.resolved[locale] = true;
                        }
                    });

                    window.addEventListener('translation-saved', (e) => {
                        if (e.detail.key === this.key) {
                            e.detail.locales.forEach(l => {
                                if (this.missingLocales.includes(l)) {
                                    this.resolved[l] = true;
                                }
                            });
                        }
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
                },

                get hasAnyDraft() {
                    return this.missingLocales.some(l => this.hasDraft(l) && !this.isResolved(l));
                },

                get missingLocalesList() {
                    return this.missingLocales.filter(l => !this.isResolved(l));
                },

                async generateAllLocales() {
                    const localesToGenerate = this.missingLocales.filter(l => !this.isResolved(l));
                    if (localesToGenerate.length === 0) return;

                    localesToGenerate.forEach(l => this.generating[l] = true);
                    
                    try {
                        const response = await fetch('{{ route('insta-translate.api.generate-multi-lang') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.key,
                                base_value: this.baseValue,
                                target_locales: localesToGenerate
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            localesToGenerate.forEach(locale => {
                                if (data.translations[locale]) {
                                    this.drafts[locale] = data.translations[locale];
                                }
                            });
                        } else {
                            alert('Error generating translations: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while generating translations.');
                    } finally {
                        localesToGenerate.forEach(l => this.generating[l] = false);
                    }
                },

                async approveAllLocales() {
                    const translationsToSave = {};
                    this.missingLocales.forEach(l => {
                        if (this.hasDraft(l) && !this.isResolved(l)) {
                            translationsToSave[l] = this.drafts[l];
                        }
                    });

                    if (Object.keys(translationsToSave).length === 0) return;

                    try {
                        const response = await fetch('{{ route('insta-translate.api.save-multi-lang') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                key: this.key,
                                translations: translationsToSave,
                                mode: '{{ $mode }}'
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            Object.keys(translationsToSave).forEach(locale => {
                                this.resolved[locale] = true;
                            });
                        } else {
                            alert('Error saving translations: ' + (data.error || 'Unknown error'));
                        }
                    } catch (e) {
                        alert('Network error while saving translations.');
                    }
                }
            }
        }
    </script>
</body>
</html>
