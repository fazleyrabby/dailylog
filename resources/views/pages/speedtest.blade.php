@extends('layouts.app')

@section('title', 'Speed Test')
@section('header_breadcrumbs', 'DAILYLOG // DIAGNOSTICS // SPEED TEST')

@section('content')
<div 
    x-data="speedtestComponent('{{ $clientIp }}', {{ json_encode($initialLogs) }})"
    x-init="init()"
    class="max-w-6xl mx-auto space-y-6"
>
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between pb-4 border-b border-border">
        <div>
            <div class="text-[10px] font-bold text-accent font-mono uppercase tracking-widest">// Network Quality diagnostics</div>
            <h1 class="text-xl font-bold tracking-tight text-text-main mt-1">Internet Speed Test</h1>
            <p class="text-xs text-text-muted mt-0.5">Test latency, download, and upload bandwidth to regional edge hubs.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Speed Tester Console (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <x-ui.card>
                <!-- Server Region Selection Tabs -->
                <div class="flex border-b border-border -mx-6 -mt-4 bg-surface-2/20">
                    <button 
                        @click="setServer('singapore')"
                        :disabled="running"
                        class="flex-1 py-3 px-4 text-center border-r border-border hover:bg-surface-2/40 transition-colors focus:outline-none disabled:opacity-50 disabled:hover:bg-transparent"
                        :class="selectedServer === 'singapore' ? 'bg-surface border-b-2 border-b-accent' : ''"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <span class="text-base">🇸🇬</span>
                            <span class="text-xs font-mono font-bold text-text-main uppercase">Singapore</span>
                        </div>
                        <div class="text-[10px] text-text-muted mt-0.5 font-mono" x-text="pings.singapore !== null ? pings.singapore + ' ms' : 'pinging...'"></div>
                    </button>
                    <button 
                        @click="setServer('malaysia')"
                        :disabled="running"
                        class="flex-1 py-3 px-4 text-center border-r border-border hover:bg-surface-2/40 transition-colors focus:outline-none disabled:opacity-50 disabled:hover:bg-transparent"
                        :class="selectedServer === 'malaysia' ? 'bg-surface border-b-2 border-b-accent' : ''"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <span class="text-base">🇲🇾</span>
                            <span class="text-xs font-mono font-bold text-text-main uppercase">Malaysia</span>
                        </div>
                        <div class="text-[10px] text-text-muted mt-0.5 font-mono" x-text="pings.malaysia !== null ? pings.malaysia + ' ms' : 'pinging...'"></div>
                    </button>
                    <button 
                        @click="setServer('hongkong')"
                        :disabled="running"
                        class="flex-1 py-3 px-4 text-center hover:bg-surface-2/40 transition-colors focus:outline-none disabled:opacity-50 disabled:hover:bg-transparent"
                        :class="selectedServer === 'hongkong' ? 'bg-surface border-b-2 border-b-accent' : ''"
                    >
                        <div class="flex items-center justify-center space-x-2">
                            <span class="text-base">🇭🇰</span>
                            <span class="text-xs font-mono font-bold text-text-main uppercase">Hong Kong</span>
                        </div>
                        <div class="text-[10px] text-text-muted mt-0.5 font-mono" x-text="pings.hongkong !== null ? pings.hongkong + ' ms' : 'pinging...'"></div>
                    </button>
                </div>

                <!-- Main Speedometer View -->
                <div class="py-12 flex flex-col md:flex-row items-center justify-around gap-8">
                    <!-- Gauge Container -->
                    <div class="relative w-64 h-64 flex-shrink-0">
                        <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                            <!-- Background Track -->
                            <circle 
                                cx="50" cy="50" r="44" 
                                fill="transparent" 
                                stroke="var(--color-border, #3F3F46)" 
                                stroke-width="6"
                                stroke-dasharray="207 276"
                                stroke-linecap="round"
                                class="text-surface-2"
                            />
                            <!-- Foreground Arc -->
                            <circle 
                                cx="50" cy="50" r="44" 
                                fill="transparent" 
                                :stroke="currentTestType === 'upload' ? '#A855F7' : 'var(--color-accent-app, #EA580C)'" 
                                stroke-width="6"
                                :stroke-dasharray="dasharrayVal"
                                stroke-linecap="round"
                                class="transition-all duration-75"
                            />
                        </svg>
                        
                        <!-- Inside Content -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <div class="text-[9px] font-mono tracking-widest text-text-subtle uppercase" x-text="statusText">// IDLE</div>
                            <div class="text-4xl font-bold font-mono tracking-tight text-text-main mt-1" x-text="currentSpeed">0.0</div>
                            <div class="text-[10px] font-mono text-text-muted uppercase mt-0.5">Mbps</div>
                            
                            <div class="mt-4 px-2.5 py-0.5 bg-surface-2/60 border border-border rounded-full text-[9px] font-mono text-text-subtle uppercase" x-text="'STAGE: ' + stage"></div>
                        </div>
                    </div>

                    <!-- Metrics Indicators Card Grid -->
                    <div class="flex-grow w-full max-w-sm space-y-3.5">
                        <!-- DOWNLOAD CARD -->
                        <div class="p-3 bg-surface border border-border rounded-sm flex items-center justify-between hover:bg-surface-2/10 transition-colors">
                            <div class="flex items-center space-x-3">
                                <span class="text-accent text-lg">↓</span>
                                <div>
                                    <div class="text-[9px] font-mono font-bold text-text-subtle uppercase">Download</div>
                                    <div class="text-sm font-bold font-mono text-text-main mt-0.5">
                                        <span x-text="downloadSpeed !== null ? downloadSpeed.toFixed(1) : '-.-'"></span>
                                        <span class="text-[10px] text-text-muted font-normal">Mbps</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[10px] font-mono text-text-subtle" x-show="currentTestType === 'download' && running">testing...</div>
                        </div>

                        <!-- UPLOAD CARD -->
                        <div class="p-3 bg-surface border border-border rounded-sm flex items-center justify-between hover:bg-surface-2/10 transition-colors">
                            <div class="flex items-center space-x-3">
                                <span class="text-purple-500 text-lg">↑</span>
                                <div>
                                    <div class="text-[9px] font-mono font-bold text-text-subtle uppercase">Upload</div>
                                    <div class="text-sm font-bold font-mono text-text-main mt-0.5">
                                        <span x-text="uploadSpeed !== null ? uploadSpeed.toFixed(1) : '-.-'"></span>
                                        <span class="text-[10px] text-text-muted font-normal">Mbps</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-[10px] font-mono text-text-subtle" x-show="currentTestType === 'upload' && running">testing...</div>
                        </div>

                        <!-- LATENCY CARD -->
                        <div class="p-3 bg-surface border border-border rounded-sm flex items-center justify-between hover:bg-surface-2/10 transition-colors">
                            <div class="flex items-center space-x-3">
                                <span class="text-blue-400 text-lg">◷</span>
                                <div>
                                    <div class="text-[9px] font-mono font-bold text-text-subtle uppercase">Latency</div>
                                    <div class="text-sm font-bold font-mono text-text-main mt-0.5">
                                        <span x-text="latency !== null ? latency : '-'"></span>
                                        <span class="text-[10px] text-text-muted font-normal">ms</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CLIENT IP CARD -->
                        <div class="p-3 bg-surface border border-border rounded-sm flex items-center justify-between hover:bg-surface-2/10 transition-colors group relative">
                            <div class="flex items-center space-x-3">
                                <span class="text-text-muted text-lg">⚙</span>
                                <div>
                                    <div class="text-[9px] font-mono font-bold text-text-subtle uppercase">Client IP</div>
                                    <div class="text-xs font-mono font-bold text-text-main mt-0.5" x-text="ip">127.0.0.1</div>
                                </div>
                            </div>
                            <button 
                                @click="copyIp()" 
                                class="text-[9px] font-mono text-accent hover:underline focus:outline-none cursor-pointer"
                            >
                                Tap to Copy
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer Console Buttons -->
                <div class="flex items-center justify-between border-t border-border -mx-6 -mb-4 p-4 bg-surface-2/10">
                    <div class="flex space-x-2.5">
                        <button 
                            @click="startTest()"
                            :disabled="running"
                            class="px-5 py-2 rounded-sm bg-accent text-white hover:bg-accent/90 transition-colors text-xs font-bold font-mono disabled:opacity-50 cursor-pointer"
                        >
                            START SPEEDTEST
                        </button>
                        <button 
                            @click="resetTest()"
                            class="px-4 py-2 rounded-sm bg-surface border border-border text-text-main hover:bg-surface-2/50 transition-colors text-xs font-bold font-mono cursor-pointer"
                        >
                            RESET
                        </button>
                    </div>
                    
                    <div class="text-xxs text-text-muted font-mono" x-text="running ? 'Testing in progress...' : 'System status: ready'"></div>
                </div>
            </x-ui.card>
        </div>

        <!-- History & Logs (1/3 width) -->
        <div class="space-y-6">
            <x-ui.card>
                <x-slot name="header">
                    <div class="flex items-center space-x-1.5">
                        <span class="text-accent text-sm font-mono">◷</span>
                        <h4 class="font-bold text-xs uppercase tracking-wider text-text-main font-mono">Recent Logs</h4>
                    </div>
                    <span class="text-xxs text-text-subtle font-mono">// connection history</span>
                </x-slot>

                <div class="space-y-2 max-h-[480px] overflow-y-auto pr-1">
                    <template x-if="logs.length === 0">
                        <div class="py-8 text-center text-xs text-text-muted border border-dashed border-border rounded-sm bg-surface">
                            No speed tests logged yet.
                        </div>
                    </template>
                    <template x-for="log in logs" :key="log.id">
                        <div class="p-2.5 border border-border bg-surface hover:bg-surface-2/10 rounded-sm text-xs transition-colors">
                            <div class="flex items-center justify-between">
                                <span class="font-bold font-mono text-[10px] text-text-main uppercase" x-text="log.server_name"></span>
                                <span class="text-[9px] text-text-subtle font-mono" x-text="formatDate(log.created_at)"></span>
                            </div>
                            <div class="grid grid-cols-3 gap-1 mt-2 text-[10px] font-mono text-text-muted">
                                <div>
                                    <span class="text-accent font-bold">↓</span> <span x-text="parseFloat(log.download_speed).toFixed(1)"></span>M
                                </div>
                                <div>
                                    <span class="text-purple-400 font-bold">↑</span> <span x-text="parseFloat(log.upload_speed).toFixed(1)"></span>M
                                </div>
                                <div>
                                    <span class="text-blue-400 font-bold">~</span> <span x-text="Math.round(log.latency_ms)"></span>ms
                                </div>
                            </div>
                            <div class="text-[9px] text-text-subtle font-mono mt-1 text-right" x-text="'IP: ' + log.ip_address"></div>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>
    </div>
</div>

<script src="{{ asset('js/speedtest.js') }}"></script>
<script>
window.speedtestComponent = function(clientIp, initialLogs) {
    return {
        ip: clientIp,
        logs: initialLogs,
        selectedServer: 'singapore',
        running: false,
        stage: 'IDLE',
        statusText: 'IDLE',
        currentSpeed: '0.0',
        currentTestType: 'none', // 'download' | 'upload' | 'none'
        speedtest: null,
        
        pings: {
            singapore: null,
            malaysia: null,
            hongkong: null
        },
        
        // Results
        latency: null,
        downloadSpeed: null,
        uploadSpeed: null,
        
        // Gauge variables (SVG length of circle circumference is 2 * PI * r = 2 * 3.14159 * 44 = 276.46)
        // We use a subset arc (270 degrees = 207 length. The remaining is empty track)
        maxDasharray: 207,
        dasharrayVal: '0 276',

        init() {
            this.measurePings();
            // Fetch client's real public IP directly from the browser
            fetch('https://api.ipify.org?format=json')
                .then(res => res.json())
                .then(data => {
                    if (data.ip) {
                        this.ip = data.ip;
                    }
                })
                .catch(err => console.warn('Could not determine public client IP client-side:', err));
        },

        setServer(srv) {
            this.selectedServer = srv;
            if (this.pings[srv] !== null) {
                this.latency = this.pings[srv];
            }
        },

        async measurePings() {
            const targets = {
                singapore: '/robots.txt', // Static asset bypassing PHP/Laravel boot lifecycle
                malaysia: 'https://dynamodb.ap-southeast-4.amazonaws.com', // AWS DynamoDB Malaysia (Kuala Lumpur)
                hongkong: 'https://dynamodb.ap-east-1.amazonaws.com' // AWS DynamoDB Hong Kong
            };

            for (const [key, url] of Object.entries(targets)) {
                try {
                    let total = 0;
                    const count = 3;
                    for (let i = 0; i < count; i++) {
                        const start = performance.now();
                        // Cache-busting parameter and no-cors mode to bypass CORS blockers for latency checks
                        const fetchUrl = url + '?t=' + Date.now();
                        if (key === 'singapore') {
                            await fetch(fetchUrl);
                        } else {
                            await fetch(fetchUrl, { mode: 'no-cors', cache: 'no-store' });
                        }
                        total += (performance.now() - start);
                    }
                    this.pings[key] = Math.round(total / count);
                    
                    if (key === this.selectedServer) {
                        this.latency = this.pings[key];
                    }
                } catch (e) {
                    console.error("Ping fail for " + key, e);
                    this.pings[key] = 999;
                }
            }
        },

        updateGauge(speed, maxLimit = 100) {
            this.currentSpeed = speed.toFixed(1);
            // Logarithmic or linear mapping. Let's use linear with cap
            const pct = Math.min(speed / maxLimit, 1);
            const val = Math.round(pct * this.maxDasharray);
            this.dasharrayVal = `${val} 276`;
        },

        resetTest(keepResults = false) {
            if (this.running) {
                if (this.speedtest) {
                    try {
                        this.speedtest.abort();
                    } catch (e) {}
                }
                this.running = false;
            }
            this.stage = 'IDLE';
            this.statusText = 'IDLE';
            this.currentSpeed = '0.0';
            this.currentTestType = 'none';
            this.dasharrayVal = '0 276';
            this.latency = this.pings[this.selectedServer];
            if (!keepResults) {
                this.downloadSpeed = null;
                this.uploadSpeed = null;
            }
            this.measurePings();
        },

        copyIp() {
            navigator.clipboard.writeText(this.ip).then(() => {
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'IP Address copied to clipboard!' }
                }));
            });
        },

        async startTest() {
            if (this.running) return;
            this.running = true;
            this.stage = 'STARTING';
            this.statusText = 'PREPARING';
            this.downloadSpeed = null;
            this.uploadSpeed = null;
            
            try {
                // Initialize LibreSpeed
                this.speedtest = new Speedtest();
                
                // Configure parameters
                this.speedtest.setParameter("time_dl_max", 8); // 8 seconds max for download
                this.speedtest.setParameter("time_ul_max", 8); // 8 seconds max for upload
                this.speedtest.setParameter("count_ping", 3);  // 3 pings
                this.speedtest.setParameter("xhr_dlMultistream", 1); // 1 thread to prevent VPS worker saturation
                this.speedtest.setParameter("xhr_ulMultistream", 1); // 1 thread to prevent VPS worker saturation
                
                // Setup URLs based on the selected server/region
                this.speedtest.setParameter("url_dl", "/speedtest/download");
                this.speedtest.setParameter("url_ul", "/speedtest/upload");
                
                // Set the ping URL dynamically
                let pingUrl = "/robots.txt";
                if (this.selectedServer === 'malaysia') {
                    pingUrl = "https://dynamodb.ap-southeast-4.amazonaws.com";
                } else if (this.selectedServer === 'hongkong') {
                    pingUrl = "https://dynamodb.ap-east-1.amazonaws.com";
                }
                this.speedtest.setParameter("url_ping", pingUrl);
                this.speedtest.setParameter("url_getIp", "/speedtest/upload");
                
                this.speedtest.onupdate = (data) => {
                    // testState values:
                    // -1 = not started, 0 = starting, 1 = download test, 2 = ping+jitter, 3 = upload, 4 = finished, 5 = aborted
                    if (data.testState === 0) {
                        this.stage = 'STARTING';
                        this.statusText = 'PREPARING';
                    } else if (data.testState === 2) {
                        this.stage = 'LATENCY';
                        this.statusText = 'PINGING';
                        if (data.pingStatus) {
                            this.latency = Math.round(parseFloat(data.pingStatus));
                        }
                    } else if (data.testState === 1) {
                        this.stage = 'DOWNLOAD';
                        this.statusText = 'DOWNLOADING';
                        this.currentTestType = 'download';
                        if (data.dlStatus) {
                            const speed = parseFloat(data.dlStatus);
                            this.updateGauge(speed, 100);
                            this.downloadSpeed = speed;
                        }
                    } else if (data.testState === 3) {
                        this.stage = 'UPLOAD';
                        this.statusText = 'UPLOADING';
                        this.currentTestType = 'upload';
                        if (data.ulStatus) {
                            const speed = parseFloat(data.ulStatus);
                            this.updateGauge(speed, 50);
                            this.uploadSpeed = speed;
                        }
                    }
                };
                
                this.speedtest.onend = async (aborted) => {
                    if (aborted) {
                        this.stage = 'FAILED';
                        this.statusText = 'ABORTED';
                        this.currentTestType = 'none';
                        this.running = false;
                        return;
                    }
                    
                    this.stage = 'LOGGING';
                    this.statusText = 'LOGGING';
                    
                    try {
                        const logRes = await fetch('/speedtest/log', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                server_name: this.selectedServer.toUpperCase(),
                                latency_ms: this.latency || 0,
                                download_speed: this.downloadSpeed || 0,
                                upload_speed: this.uploadSpeed || 0,
                                ip_address: this.ip
                            })
                        });
                        
                        if (logRes.ok) {
                            const resData = await logRes.json();
                            if (resData.success) {
                                this.logs.unshift(resData.log);
                                if (this.logs.length > 10) this.logs.pop();
                            }
                        }
                    } catch (e) {
                        console.error("Failed to log results:", e);
                    }
                    
                    this.stage = 'DONE';
                    this.statusText = 'COMPLETE';
                    this.currentTestType = 'none';
                    this.running = false;
                    
                    window.dispatchEvent(new CustomEvent('show-toast', { 
                        detail: { message: 'Speedtest completed and logged!' }
                    }));

                    // Automatically reset the circular speedometer gauge UI after 4 seconds
                    // but preserve the speed test results on the right side cards.
                    setTimeout(() => {
                        this.resetTest(true);
                    }, 4000);
                };
                
                this.speedtest.start();
                
            } catch (e) {
                console.error(e);
                this.stage = 'FAILED';
                this.statusText = 'ERROR';
                this.currentTestType = 'none';
                this.running = false;
                window.dispatchEvent(new CustomEvent('show-toast', { 
                    detail: { message: 'Speedtest failed. Please retry.' }
                }));
            }
        },

        formatDate(dateStr) {
            const d = new Date(dateStr);
            return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) + ' ' + d.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit', hour12: true });
        }
    };
};
</script>
@endsection
