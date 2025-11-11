<div wire:poll.5s="loadAllStats" wire:loading.class="updating" style="padding: 2rem; max-width: 1600px; margin: 0 auto;">
    <!-- Page Header -->
    <div style="margin-bottom: 2rem;">
        <h1 style="font-size: 2rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;">
            <span class="material-symbols-outlined" style="font-size: 2rem; vertical-align: middle; margin-right: 0.5rem;">
                monitoring
            </span>
            System Monitor
        </h1>
        <p style="color: var(--text-secondary); font-size: 0.95rem;">
            Real-time system statistics and performance metrics • Updates every 5 seconds
        </p>
    </div>

    <!-- System Overview Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- CPU Card -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem; color: #10b981;">
                        memory
                    </span>
                    CPU Usage
                </h3>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; font-weight: 700; color: #10b981; margin-bottom: 0.5rem; min-width: 120px; transition: all 0.3s ease;">
                    {{ number_format($systemStats['cpu']['usage'] ?? 0, 1) }}%
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; min-height: 20px;">
                    {{ $systemStats['cpu']['cores'] ?? 1 }} Cores • Load: {{ number_format($systemStats['load_average']['1min'] ?? 0, 2) }}
                </div>
                <div style="background: #f3f4f6; border-radius: 8px; height: 8px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #10b981, #059669); height: 100%; width: {{ $systemStats['cpu']['usage'] ?? 0 }}%; transition: width 1s ease;"></div>
                </div>
            </div>
        </div>

        <!-- Memory Card -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem; color: #3b82f6;">
                        storage
                    </span>
                    Memory Usage
                </h3>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; font-weight: 700; color: #3b82f6; margin-bottom: 0.5rem; min-width: 120px; transition: all 0.3s ease;">
                    {{ number_format($systemStats['memory']['percent'] ?? 0, 1) }}%
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; min-height: 20px;">
                    {{ number_format($systemStats['memory']['used'] ?? 0, 1) }} GB / {{ number_format($systemStats['memory']['total'] ?? 0, 1) }} GB
                </div>
                <div style="background: #f3f4f6; border-radius: 8px; height: 8px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #3b82f6, #2563eb); height: 100%; width: {{ $systemStats['memory']['percent'] ?? 0 }}%; transition: width 1s ease;"></div>
                </div>
            </div>
        </div>

        <!-- Disk Usage Card -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem; color: #f59e0b;">
                        hard_drive
                    </span>
                    Disk Usage
                </h3>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 2.5rem; font-weight: 700; color: #f59e0b; margin-bottom: 0.5rem; min-width: 120px; transition: all 0.3s ease;">
                    {{ number_format($diskUsage['disk_used_percent'] ?? 0, 1) }}%
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 1rem; min-height: 20px;">
                    {{ number_format($diskUsage['disk_free'] ?? 0, 1) }} GB free of {{ number_format($diskUsage['disk_total'] ?? 0, 1) }} GB
                </div>
                <div style="background: #f3f4f6; border-radius: 8px; height: 8px; overflow: hidden;">
                    <div style="background: linear-gradient(90deg, #f59e0b, #d97706); height: 100%; width: {{ $diskUsage['disk_used_percent'] ?? 0 }}%; transition: width 1s ease;"></div>
                </div>
            </div>
        </div>

        <!-- AI Service Card -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h3 style="font-size: 1rem; font-weight: 600; color: var(--text-primary);">
                    <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem; color: #8b5cf6;">
                        psychology
                    </span>
                    AI Service
                </h3>
            </div>
            <div style="text-align: center;">
                @if(($aiServiceStats['status'] ?? 'offline') === 'online')
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #10b98114; color: #10b981; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem;">
                        <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite;"></span>
                        ONLINE
                    </div>
                @else
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #ef444414; color: #ef4444; padding: 0.5rem 1rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600; margin-bottom: 1rem;">
                        <span style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%;"></span>
                        OFFLINE
                    </div>
                @endif
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                    Response Time: {{ $aiServiceStats['response_time'] ?? 'N/A' }}
                </div>
                <div style="font-size: 0.875rem; color: var(--text-secondary);">
                    Models: {{ count($aiServiceStats['models_loaded'] ?? []) }}
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- System Resources Chart -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">
                    show_chart
                </span>
                System Resources (Live)
            </h3>
            <canvas id="resourcesChart" style="max-height: 300px;"></canvas>
        </div>

        <!-- Processing History Chart -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">
                    trending_up
                </span>
                Image Processing (24h)
            </h3>
            <canvas id="processingChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Queue & Database Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Queue Stats -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">
                    queue
                </span>
                Queue Status
            </h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Pending Jobs</span>
                    <span style="font-weight: 600; color: #f59e0b; font-size: 1.125rem;">{{ $queueStats['pending'] ?? 0 }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Failed Jobs</span>
                    <span style="font-weight: 600; color: #ef4444; font-size: 1.125rem;">{{ $queueStats['failed'] ?? 0 }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Worker Status</span>
                    @if($queueStats['queue_worker_running'] ?? false)
                        <span style="color: #10b981; font-weight: 600; font-size: 0.875rem;">RUNNING</span>
                    @else
                        <span style="color: #ef4444; font-weight: 600; font-size: 0.875rem;">STOPPED</span>
                    @endif
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Scheduler Status</span>
                    @if($queueStats['scheduler_running'] ?? false)
                        <span style="color: #10b981; font-weight: 600; font-size: 0.875rem;">RUNNING</span>
                    @else
                        <span style="color: #ef4444; font-weight: 600; font-size: 0.875rem;">STOPPED</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Database Stats -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">
                    database
                </span>
                Database Statistics
            </h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Total Images</span>
                    <span style="font-weight: 600; color: #3b82f6; font-size: 1.125rem;">{{ number_format($databaseStats['total_images'] ?? 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Completed</span>
                    <span style="font-weight: 600; color: #10b981; font-size: 1.125rem;">{{ number_format($databaseStats['completed'] ?? 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Processing</span>
                    <span style="font-weight: 600; color: #f59e0b; font-size: 1.125rem;">{{ number_format($databaseStats['processing'] ?? 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Database Size</span>
                    <span style="font-weight: 600; color: #8b5cf6; font-size: 1.125rem;">{{ $databaseStats['database_size'] ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- AI Features Stats -->
        <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
            <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">
                    auto_awesome
                </span>
                AI Features
            </h3>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">With Faces</span>
                    <span style="font-weight: 600; color: #ec4899; font-size: 1.125rem;">{{ number_format($databaseStats['with_faces'] ?? 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Ollama Descriptions</span>
                    <span style="font-weight: 600; color: #8b5cf6; font-size: 1.125rem;">{{ number_format($databaseStats['with_ollama_desc'] ?? 0) }}</span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Ollama Available</span>
                    @if($aiServiceStats['ollama_available'] ?? false)
                        <span style="color: #10b981; font-weight: 600; font-size: 0.875rem;">YES</span>
                    @else
                        <span style="color: #ef4444; font-weight: 600; font-size: 0.875rem;">NO</span>
                    @endif
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Face Detection</span>
                    @if($aiServiceStats['face_recognition_available'] ?? false)
                        <span style="color: #10b981; font-weight: 600; font-size: 0.875rem;">YES</span>
                    @else
                        <span style="color: #ef4444; font-weight: 600; font-size: 0.875rem;">NO</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div style="background: var(--card-background); border-radius: 12px; padding: 1.5rem; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
            <span class="material-symbols-outlined" style="font-size: 1.25rem; vertical-align: middle; margin-right: 0.5rem;">
                info
            </span>
            System Information
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">System Uptime</div>
                <div style="font-weight: 600; color: var(--text-primary);">{{ $systemStats['uptime'] ?? 'N/A' }}</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Load Average (1m / 5m / 15m)</div>
                <div style="font-weight: 600; color: var(--text-primary);">
                    {{ $systemStats['load_average']['1min'] ?? 0 }} / 
                    {{ $systemStats['load_average']['5min'] ?? 0 }} / 
                    {{ $systemStats['load_average']['15min'] ?? 0 }}
                </div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Images Storage</div>
                <div style="font-weight: 600; color: var(--text-primary);">{{ $diskUsage['images_size'] ?? 0 }} GB</div>
            </div>
            <div>
                <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Last Updated</div>
                <div style="font-weight: 600; color: var(--text-primary);">{{ now()->format('H:i:s') }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    document.addEventListener('livewire:initialized', () => {
        let resourcesChart = null;
        let processingChart = null;

        // CPU & Memory Chart
        const resourcesCtx = document.getElementById('resourcesChart').getContext('2d');
        resourcesChart = new Chart(resourcesCtx, {
            type: 'line',
            data: {
                labels: Array(20).fill(''),
                datasets: [
                    {
                        label: 'CPU %',
                        data: Array(20).fill(0),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Memory %',
                        data: Array(20).fill(0),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: value => value + '%'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    }
                },
                animation: {
                    duration: 0  // Disable animation on initial draw
                }
            }
        });

        // Processing History Chart
        const processingCtx = document.getElementById('processingChart').getContext('2d');
        const processingHistory = @json($processingHistory);
        
        processingChart = new Chart(processingCtx, {
            type: 'bar',
            data: {
                labels: processingHistory.map(item => {
                    const date = new Date(item.hour);
                    return date.getHours() + ':00';
                }),
                datasets: [{
                    label: 'Images Processed',
                    data: processingHistory.map(item => item.count),
                    backgroundColor: 'rgba(139, 92, 246, 0.8)',
                    borderColor: '#8b5cf6',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Update charts on Livewire update (smooth, no jumping)
        document.addEventListener('livewire:update', () => {
            setTimeout(() => {
                // Update resource chart with new data
                const cpuUsage = {{ $systemStats['cpu']['usage'] ?? 0 }};
                const memUsage = {{ $systemStats['memory']['percent'] ?? 0 }};

                if (resourcesChart) {
                    resourcesChart.data.datasets[0].data.push(cpuUsage);
                    resourcesChart.data.datasets[1].data.push(memUsage);

                    if (resourcesChart.data.datasets[0].data.length > 20) {
                        resourcesChart.data.datasets[0].data.shift();
                        resourcesChart.data.datasets[1].data.shift();
                    }

                    // Update without animation to prevent jumping
                    resourcesChart.update('none');
                }
            }, 100);
        });

        // Initial update after 1 second
        setTimeout(() => {
            @this.dispatch('stats-updated');
        }, 1000);
    });

    // Pulse animation and smooth transitions
    const style = document.createElement('style');
    style.textContent = `
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        /* Smooth transitions for stats to prevent jumping */
        .stat-number {
            display: inline-block;
            min-width: 90px;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Smooth chart updates */
        canvas {
            transition: opacity 0.2s ease;
        }
        
        /* Prevent content shifts during updates - keep opacity at 1 */
        [wire\\:loading],
        .updating {
            opacity: 1 !important;
        }
        
        /* Fixed heights to prevent jumping */
        .card-content {
            min-height: 150px;
        }
        
        /* Smooth number transitions */
        [style*="font-size: 2.5rem"] {
            font-variant-numeric: tabular-nums;
            letter-spacing: 0.05em;
        }
        
        /* Prevent layout shift */
        * {
            will-change: auto !important;
        }
        
        /* Smooth progress bar animations */
        [style*="transition: width"] {
            transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
    `;
    document.head.appendChild(style);
</script>
