<div wire:poll.5s="loadAllStats" 
     class="min-h-screen bg-surface-variant"
     x-data="{ 
         activeTab: 'overview',
         showMetricDetails: false,
         selectedMetric: null
     }">
    
    <!-- Top App Bar -->
    <div class="sticky top-0 z-50 bg-white border-b border-outline shadow-sm">
        <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl text-primary-600">monitoring</span>
                        </div>
                        <div>
                            <h1 class="text-xl font-medium text-gray-900">System Monitor</h1>
                            <p class="text-xs text-gray-500">Real-time metrics • Updates every 5s</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1 px-3 py-1.5 bg-green-50 rounded-full">
                        <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        <span class="text-xs font-medium text-green-700">Live</span>
                    </div>
                    <button wire:click="loadAllStats" 
                            class="p-2 text-gray-700 hover:bg-gray-100 rounded-full transition-colors duration-200"
                            title="Refresh">
                        <span class="material-symbols-outlined text-xl">refresh</span>
                    </button>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 -mb-px">
                <button @click="activeTab = 'overview'" 
                        :class="activeTab === 'overview' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-medium transition-colors duration-200">
                    Overview
                </button>
                <button @click="activeTab = 'services'" 
                        :class="activeTab === 'services' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-medium transition-colors duration-200">
                    Services
                </button>
                <button @click="activeTab = 'queues'" 
                        :class="activeTab === 'queues' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-medium transition-colors duration-200">
                    Queue Jobs
                </button>
                <button @click="activeTab = 'database'" 
                        :class="activeTab === 'database' ? 'border-b-2 border-primary-600 text-primary-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-4 py-3 text-sm font-medium transition-colors duration-200">
                    Database
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-[2000px] mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Overview Tab -->
        <div x-show="activeTab === 'overview'" x-transition class="space-y-6">
            
            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- CPU Usage -->
                <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-all duration-200 cursor-pointer"
                     @click="selectedMetric = 'cpu'; showMetricDetails = true">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl text-green-600">memory</span>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-3xl font-bold text-gray-900">
                                {{ number_format($systemStats['cpu']['usage'] ?? 0, 1) }}%
                            </span>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $systemStats['cpu']['cores'] ?? 1 }} cores
                            </span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">CPU Usage</h3>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-green-500 to-green-600 h-full rounded-full transition-all duration-1000 ease-out"
                             x-bind:style="'width: ' + {{ $systemStats['cpu']['usage'] ?? 0 }} + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Load avg: {{ number_format($systemStats['load_average']['1min'] ?? 0, 2) }}
                    </p>
                </div>

                <!-- Memory Usage -->
                <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-all duration-200 cursor-pointer"
                     @click="selectedMetric = 'memory'; showMetricDetails = true">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl text-blue-600">storage</span>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-3xl font-bold text-gray-900">
                                {{ number_format($systemStats['memory']['percent'] ?? 0, 1) }}%
                            </span>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ number_format($systemStats['memory']['used'] ?? 0, 1) }} / {{ number_format($systemStats['memory']['total'] ?? 0, 1) }} GB
                            </span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Memory</h3>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-full rounded-full transition-all duration-1000 ease-out"
                             x-bind:style="'width: ' + {{ $systemStats['memory']['percent'] ?? 0 }} + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Free: {{ number_format($systemStats['memory']['available'] ?? 0, 1) }} GB
                    </p>
                </div>

                <!-- Disk Usage -->
                <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-all duration-200 cursor-pointer"
                     @click="selectedMetric = 'disk'; showMetricDetails = true">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 bg-orange-50 rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl text-orange-600">hard_drive</span>
                        </div>
                        <div class="flex flex-col items-end">
                            <span class="text-3xl font-bold text-gray-900">
                                {{ number_format($diskUsage['disk_used_percent'] ?? 0, 1) }}%
                            </span>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ number_format($diskUsage['disk_used'] ?? 0, 1) }} / {{ number_format($diskUsage['disk_total'] ?? 0, 1) }} GB
                            </span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Disk Space</h3>
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r from-orange-500 to-orange-600 h-full rounded-full transition-all duration-1000 ease-out"
                             x-bind:style="'width: ' + {{ $diskUsage['disk_used_percent'] ?? 0 }} + '%'"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Free: {{ number_format($diskUsage['disk_free'] ?? 0, 1) }} GB
                    </p>
                </div>

                <!-- AI Service Status -->
                <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-all duration-200">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 {{ ($aiServiceStats['status'] ?? 'offline') === 'online' ? 'bg-purple-50' : 'bg-red-50' }} rounded-xl flex items-center justify-center">
                            <span class="material-symbols-outlined text-2xl {{ ($aiServiceStats['status'] ?? 'offline') === 'online' ? 'text-purple-600' : 'text-red-600' }}">
                                psychology
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 {{ ($aiServiceStats['status'] ?? 'offline') === 'online' ? 'bg-green-500' : 'bg-red-500' }} rounded-full {{ ($aiServiceStats['status'] ?? 'offline') === 'online' ? 'animate-pulse' : '' }}"></span>
                            <span class="text-xs font-medium {{ ($aiServiceStats['status'] ?? 'offline') === 'online' ? 'text-green-700' : 'text-red-700' }}">
                                {{ ucfirst($aiServiceStats['status'] ?? 'offline') }}
                            </span>
                        </div>
                    </div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">AI Service</h3>
                    @if (($aiServiceStats['status'] ?? 'offline') === 'online')
                        <div class="space-y-1 mt-3">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Response time</span>
                                <span class="text-gray-900 font-medium">{{ number_format($aiServiceStats['avg_response_time'] ?? 0) }}ms</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500">Success rate</span>
                                <span class="text-green-600 font-medium">{{ number_format($aiServiceStats['success_rate'] ?? 0, 1) }}%</span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-red-600 mt-3">Service unavailable</p>
                    @endif
                </div>
            </div>

            <!-- Queue & Database Stats -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                
                <!-- Queue Stats Card -->
                <div class="bg-white rounded-xl shadow-md3-1 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-2xl text-gray-700">playlist_play</span>
                            <span>Queue Status</span>
                        </h2>
                        <a wire:navigate href="#" @click.prevent="activeTab = 'queues'"
                           class="text-sm font-medium text-primary-600 hover:text-primary-700">
                            View details →
                        </a>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-gray-900 mb-1">
                                {{ $queueStats['pending'] ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">Pending</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-green-600 mb-1">
                                {{ $queueStats['completed_today'] ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">Completed today</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl font-bold text-red-600 mb-1">
                                {{ $queueStats['failed'] ?? 0 }}
                            </div>
                            <div class="text-xs text-gray-500">Failed</div>
                        </div>
                    </div>
                </div>

                <!-- Database Stats Card -->
                <div class="bg-white rounded-xl shadow-md3-1 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                            <span class="material-symbols-outlined text-2xl text-gray-700">database</span>
                            <span>Database</span>
                        </h2>
                        <a wire:navigate href="#" @click.prevent="activeTab = 'database'"
                           class="text-sm font-medium text-primary-600 hover:text-primary-700">
                            View details →
                        </a>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Total media files</span>
                            <span class="text-lg font-bold text-gray-900">{{ number_format($databaseStats['total_media'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Processed</span>
                            <span class="text-lg font-bold text-green-600">{{ number_format($databaseStats['processed'] ?? 0) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Database size</span>
                            <span class="text-lg font-bold text-gray-900">{{ $diskUsage['database_size'] ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Services Tab -->
        <div x-show="activeTab === 'services'" x-transition class="space-y-4">
            @php
                $services = [
                    [
                        'name' => 'AI Service (Python)',
                        'status' => ($aiServiceStats['status'] ?? 'offline') === 'online',
                        'icon' => 'psychology',
                        'color' => 'purple',
                        'metrics' => [
                            'Response time' => ($aiServiceStats['avg_response_time'] ?? 0) . 'ms',
                            'Success rate' => number_format($aiServiceStats['success_rate'] ?? 0, 1) . '%',
                            'Total requests' => $aiServiceStats['total_requests'] ?? 0,
                        ]
                    ],
                    [
                        'name' => 'Queue Worker',
                        'status' => ($queueStats['pending'] ?? 0) >= 0,
                        'icon' => 'settings',
                        'color' => 'blue',
                        'metrics' => [
                            'Pending jobs' => $queueStats['pending'] ?? 0,
                            'Completed today' => $queueStats['completed_today'] ?? 0,
                            'Failed jobs' => $queueStats['failed'] ?? 0,
                        ]
                    ],
                    [
                        'name' => 'Database',
                        'status' => true,
                        'icon' => 'database',
                        'color' => 'green',
                        'metrics' => [
                            'Total records' => number_format($databaseStats['total_media'] ?? 0),
                            'Processed' => number_format($databaseStats['processed'] ?? 0),
                            'Size' => $diskUsage['database_size'] ?? 'N/A',
                        ]
                    ],
                ];
            @endphp

            @foreach ($services as $service)
                <div class="bg-white rounded-xl shadow-md3-1 p-6 hover:shadow-md3-2 transition-shadow duration-200">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-4 flex-1">
                            <div class="w-12 h-12 bg-{{ $service['color'] }}-50 rounded-xl flex items-center justify-center flex-shrink-0">
                                <span class="material-symbols-outlined text-2xl text-{{ $service['color'] }}-600">{{ $service['icon'] }}</span>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3 class="text-lg font-medium text-gray-900">{{ $service['name'] }}</h3>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium
                                                 {{ $service['status'] ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $service['status'] ? 'bg-green-500 animate-pulse' : 'bg-red-500' }}"></span>
                                        {{ $service['status'] ? 'Running' : 'Offline' }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    @foreach ($service['metrics'] as $label => $value)
                                        <div>
                                            <div class="text-xs text-gray-500 mb-1">{{ $label }}</div>
                                            <div class="text-lg font-semibold text-gray-900">{{ $value }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <button class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors duration-200">
                            <span class="material-symbols-outlined">more_vert</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Queues Tab -->
        <div x-show="activeTab === 'queues'" x-transition>
            <div class="bg-white rounded-xl shadow-md3-1 overflow-hidden">
                <div class="p-6 border-b border-outline">
                    <h2 class="text-lg font-medium text-gray-900">Queue Jobs</h2>
                    <p class="text-sm text-gray-500 mt-1">Monitor background job processing</p>
                </div>

                <div class="divide-y divide-outline">
                    @php
                        $jobTypes = [
                            ['name' => 'Pending Jobs', 'count' => $queueStats['pending'] ?? 0, 'icon' => 'schedule', 'color' => 'blue'],
                            ['name' => 'Processing', 'count' => $queueStats['processing'] ?? 0, 'icon' => 'sync', 'color' => 'orange'],
                            ['name' => 'Completed Today', 'count' => $queueStats['completed_today'] ?? 0, 'icon' => 'check_circle', 'color' => 'green'],
                            ['name' => 'Failed Jobs', 'count' => $queueStats['failed'] ?? 0, 'icon' => 'error', 'color' => 'red'],
                        ];
                    @endphp

                    @foreach ($jobTypes as $job)
                        <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 bg-{{ $job['color'] }}-50 rounded-lg flex items-center justify-center">
                                        <span class="material-symbols-outlined text-xl text-{{ $job['color'] }}-600">{{ $job['icon'] }}</span>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900">{{ $job['name'] }}</h3>
                                        <p class="text-sm text-gray-500">Queue status</p>
                                    </div>
                                </div>
                                <div class="text-3xl font-bold text-gray-900">{{ number_format($job['count']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Failed Jobs Details -->
            @if (count($failedJobs) > 0)
                <div class="bg-white rounded-xl shadow-md3-1 overflow-hidden mt-6">
                    <div class="p-6 border-b border-outline bg-red-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-red-900 flex items-center gap-2">
                                    <span class="material-symbols-outlined">error</span>
                                    Failed Jobs ({{ count($failedJobs) }})
                                </h2>
                                <p class="text-sm text-red-700 mt-1">Jobs that failed during processing</p>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="retryAllFailedJobs" 
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg shadow-md3-1 hover:shadow-md3-2 transition-all duration-200"
                                        wire:loading.attr="disabled">
                                    <span class="material-symbols-outlined text-lg">refresh</span>
                                    <span>Retry All</span>
                                </button>
                                <button wire:click="clearAllFailedJobs" 
                                        onclick="return confirm('Are you sure you want to clear all failed jobs?')"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg shadow-md3-1 hover:shadow-md3-2 transition-all duration-200"
                                        wire:loading.attr="disabled">
                                    <span class="material-symbols-outlined text-lg">delete_sweep</span>
                                    <span>Clear All</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    @if (session()->has('success'))
                        <div class="p-4 bg-green-50 border-b border-green-200">
                            <div class="flex items-center gap-2 text-green-800">
                                <span class="material-symbols-outlined">check_circle</span>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif
                    @if (session()->has('error'))
                        <div class="p-4 bg-red-50 border-b border-red-200">
                            <div class="flex items-center gap-2 text-red-800">
                                <span class="material-symbols-outlined">error</span>
                                <span>{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="divide-y divide-outline max-h-[600px] overflow-y-auto">
                        @foreach ($failedJobs as $job)
                            <div class="p-6 hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <span class="material-symbols-outlined text-red-600 text-lg">warning</span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h3 class="font-medium text-gray-900 truncate">{{ $job['display_name'] }}</h3>
                                                <p class="text-xs text-gray-500">Failed {{ $job['failed_at'] }}</p>
                                            </div>
                                        </div>
                                        <div class="ml-11 space-y-2">
                                            <div class="text-sm">
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                                    <span class="material-symbols-outlined text-xs">schedule</span>
                                                    Queue: {{ $job['queue'] }}
                                                </span>
                                            </div>
                                            <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                                <p class="text-xs text-red-900 font-mono">{{ $job['error'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <button wire:click="retryFailedJob('{{ $job['uuid'] }}')" 
                                                class="p-2 text-primary-600 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors duration-200"
                                                title="Retry this job"
                                                wire:loading.attr="disabled">
                                            <span class="material-symbols-outlined">refresh</span>
                                        </button>
                                        <button wire:click="deleteFailedJob('{{ $job['uuid'] }}')" 
                                                onclick="return confirm('Are you sure you want to delete this failed job?')"
                                                class="p-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors duration-200"
                                                title="Delete this job"
                                                wire:loading.attr="disabled">
                                            <span class="material-symbols-outlined">delete</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- AI Service Status -->
                <div class="bg-white rounded-xl shadow-md3-1 overflow-hidden mt-6">
                    <div class="p-6 border-b border-outline bg-yellow-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-medium text-yellow-900 flex items-center gap-2">
                                    <span class="material-symbols-outlined">psychology</span>
                                    AI Service Status
                                </h2>
                                <p class="text-sm text-yellow-700 mt-1">Check AI service health</p>
                            </div>
                            <button wire:click="restartAiService" 
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-lg shadow-md3-1 hover:shadow-md3-2 transition-all duration-200"
                                    wire:loading.attr="disabled">
                                <span class="material-symbols-outlined text-lg">restart_alt</span>
                                <span>Reset Circuit Breaker</span>
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600">
                            <span class="material-symbols-outlined text-base align-middle text-yellow-600">info</span>
                            If jobs are failing due to AI service timeouts, try resetting the circuit breaker. This will allow the system to retry connecting to the AI service.
                        </p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Database Tab -->
        <div x-show="activeTab === 'database'" x-transition>
            <div class="bg-white rounded-xl shadow-md3-1 overflow-hidden">
                <div class="p-6 border-b border-outline">
                    <h2 class="text-lg font-medium text-gray-900">Database Statistics</h2>
                    <p class="text-sm text-gray-500 mt-1">Storage and record counts</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-symbols-outlined text-2xl text-primary-600">photo_library</span>
                                <h3 class="font-medium text-gray-900">Media Files</h3>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 mb-1">
                                {{ number_format($databaseStats['total_media'] ?? 0) }}
                            </div>
                            <p class="text-sm text-gray-500">Total files</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-symbols-outlined text-2xl text-green-600">check_circle</span>
                                <h3 class="font-medium text-gray-900">Processed</h3>
                            </div>
                            <div class="text-3xl font-bold text-green-600 mb-1">
                                {{ number_format($databaseStats['processed'] ?? 0) }}
                            </div>
                            <p class="text-sm text-gray-500">AI analyzed</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-symbols-outlined text-2xl text-orange-600">pending</span>
                                <h3 class="font-medium text-gray-900">Pending</h3>
                            </div>
                            <div class="text-3xl font-bold text-orange-600 mb-1">
                                {{ number_format($databaseStats['pending'] ?? 0) }}
                            </div>
                            <p class="text-sm text-gray-500">Awaiting processing</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-symbols-outlined text-2xl text-purple-600">hard_drive</span>
                                <h3 class="font-medium text-gray-900">Database Size</h3>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 mb-1">
                                {{ $diskUsage['database_size'] ?? 'N/A' }}
                            </div>
                            <p class="text-sm text-gray-500">Total storage</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-symbols-outlined text-2xl text-blue-600">folder</span>
                                <h3 class="font-medium text-gray-900">Storage Used</h3>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 mb-1">
                                {{ $diskUsage['storage_used'] ?? 'N/A' }}
                            </div>
                            <p class="text-sm text-gray-500">Media storage</p>
                        </div>

                        <div class="p-4 bg-gray-50 rounded-xl">
                            <div class="flex items-center gap-3 mb-3">
                                <span class="material-symbols-outlined text-2xl text-gray-600">data_usage</span>
                                <h3 class="font-medium text-gray-900">Cache Size</h3>
                            </div>
                            <div class="text-3xl font-bold text-gray-900 mb-1">
                                {{ $diskUsage['cache_size'] ?? 'N/A' }}
                            </div>
                            <p class="text-sm text-gray-500">Temporary files</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
