<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Fire Alarm System - IoT Monitoring Dashboard</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#ef4444',
                        dark: '#0f172a',
                    },
                    animation: {
                        'pulse-slow': 'pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                        'ping-slow': 'ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite',
                        'flash-red': 'flashRed 1s infinite alternate',
                    },
                    keyframes: {
                        flashRed: {
                            '0%': { backgroundColor: 'rgba(239, 68, 68, 0.2)', borderColor: 'rgba(239, 68, 68, 0.8)' },
                            '100%': { backgroundColor: 'rgba(153, 27, 27, 0.5)', borderColor: 'rgba(248, 113, 113, 1)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap');
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        .font-mono-val {
            font-family: 'JetBrains Mono', monospace;
        }
        .danger-glow {
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.45);
        }
        .safe-glow {
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col antialiased selection:bg-red-500 selection:text-white">

    <!-- Navbar / Header -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-30 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex flex-wrap items-center justify-between gap-4">
            
            <!-- Branding -->
            <div class="flex items-center gap-3">
                <div id="header-icon-badge" class="w-10 h-10 rounded-xl bg-gradient-to-tr from-red-600 to-amber-500 flex items-center justify-center shadow-lg shadow-red-500/20 ring-1 ring-red-400/30">
                    <i data-lucide="flame" class="w-6 h-6 text-white animate-bounce"></i>
                </div>
                <div>
                    <h1 class="text-lg sm:text-xl font-bold tracking-tight text-white flex items-center gap-2">
                        FIRE ALARM SYSTEM
                        <span id="live-indicator-badge" class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            Live 3s
                        </span>
                    </h1>
                    <p class="text-xs text-slate-400">Monitoring IoT Sensor Asap MQ-2 & Api ESP32</p>
                </div>
            </div>

            <!-- Controls (Clock, Audio Alarm, Simulation) -->
            <div class="flex items-center gap-2 sm:gap-3 flex-wrap">
                <!-- Digital Clock -->
                <div class="flex items-center gap-2 text-xs text-slate-300 bg-slate-800/80 px-3 py-1.5 rounded-lg border border-slate-700/60 shadow-inner">
                    <i data-lucide="clock" class="w-4 h-4 text-slate-400"></i>
                    <span id="current-clock" class="font-mono-val">--:--:--</span>
                </div>

                <!-- Tombol Audio Alarm (Matikan / Hidupkan Suara) -->
                <button id="btn-sound-toggle" onclick="toggleAudioAlarm()" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition shadow-sm bg-slate-800 hover:bg-slate-700 text-slate-300 border-slate-700">
                    <i id="icon-sound" data-lucide="volume-2" class="w-3.5 h-3.5 text-emerald-400"></i>
                    <span id="text-sound">Suara: Aktif</span>
                </button>

                <!-- Tombol Simulasi Data -->
                <button onclick="toggleSimulationPanel()" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white rounded-lg shadow-sm transition">
                    <i data-lucide="cpu" class="w-3.5 h-3.5"></i>
                    <span>Simulasi ESP32</span>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 flex-1 w-full space-y-6">

        <!-- Simulation Tool (Collapsible for easy IoT demo) -->
        <div id="simulation-panel" class="hidden bg-slate-800/95 rounded-2xl p-5 border border-indigo-500/40 shadow-2xl transition-all">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div>
                    <h3 class="text-sm font-bold text-indigo-300 flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-indigo-400"></i>
                        Panel Uji Coba Pengiriman Data ESP32 (Endpoint: POST /api/v1/sensor-data)
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Uji coba langsung respons sistem, perubahan warna indikator, pencegahan duplikasi insiden, dan sirine bahaya:</p>
                </div>
                <button onclick="toggleSimulationPanel()" class="text-slate-400 hover:text-slate-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5">
                <button onclick="sendSimulationData(110, false)" 
                        class="px-3 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="check-circle" class="w-4 h-4 text-emerald-400"></i>
                    Kondisi AMAN (MQ-2: 110, Api: 0)
                </button>
                <button onclick="sendSimulationData(490, false)" 
                        class="px-3 py-2 rounded-xl bg-amber-500/10 hover:bg-amber-500/20 text-amber-300 border border-amber-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="cloud-fog" class="w-4 h-4 text-amber-400"></i>
                    Bahaya Asap (MQ-2: 490, Api: 0)
                </button>
                <button onclick="sendSimulationData(220, true)" 
                        class="px-3 py-2 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-300 border border-red-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="flame" class="w-4 h-4 text-red-400"></i>
                    Bahaya Api (MQ-2: 220, Api: 1)
                </button>
                <button onclick="sendSimulationData(750, true)" 
                        class="px-3 py-2 rounded-xl bg-purple-500/10 hover:bg-purple-500/20 text-purple-300 border border-purple-500/30 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="alert-octagon" class="w-4 h-4 text-purple-400"></i>
                    Bahaya Keduanya (MQ-2: 750, Api: 1)
                </button>
            </div>
            <div id="sim-feedback" class="mt-2.5 text-xs hidden"></div>
        </div>

        <!-- Banner Indikator Darurat (Tampil & Berkedip Merah saat BAHAYA) -->
        <div id="danger-banner" class="{{ $isDanger ? '' : 'hidden' }} rounded-2xl p-4 sm:p-5 bg-gradient-to-r from-red-950 via-red-900 to-red-950 border-2 border-red-500 shadow-2xl shadow-red-900/80 flex flex-wrap items-center justify-between gap-4 animate-flash-red">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-red-500/30 text-red-300 flex items-center justify-center shrink-0 ring-2 ring-red-400/50">
                    <i data-lucide="alert-triangle" class="w-7 h-7 animate-bounce"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-white tracking-wide flex items-center gap-2">
                        <span>PERINGATAN BAHAYA KEBAKARAN AKTIF!</span>
                        <span class="w-3 h-3 rounded-full bg-red-500 animate-ping"></span>
                    </h2>
                    <p id="danger-banner-desc" class="text-xs sm:text-sm text-red-100 mt-0.5 font-medium">
                        @if ($flameDetected && $smokeDetected)
                            Nyala api dan konsentrasi asap pekat terdeteksi secara bersamaan!
                        @elseif ($flameDetected)
                            Sensor mendeteksi adanya nyala api aktif di lokasi pemantauan!
                        @else
                            Konsentrasi asap/gas MQ-2 mencapai ambang batas bahaya ({{ $mq2Value }} PPM)!
                        @endif
                    </p>
                </div>
            </div>
            <!-- Quick Silence Alarm Button -->
            <div>
                <button onclick="silenceCurrentAlarm()" 
                        class="px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white font-bold text-xs uppercase tracking-wider shadow-lg shadow-red-600/50 flex items-center gap-2 transition transform active:scale-95">
                    <i data-lucide="volume-x" class="w-4 h-4"></i>
                    <span id="banner-mute-text">Matikan Suara Sirine</span>
                </button>
            </div>
        </div>

        <!-- SECTION: 4 KARTU INDIKATOR UTAMA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            
            <!-- KARTU 1: Status Sistem (Hijau AMAN / Merah Berkedip BAHAYA) -->
            <div id="card-system" class="relative overflow-hidden rounded-2xl p-5 border shadow-lg transition-all duration-300 {{ $isDanger ? 'border-red-500 bg-red-950/40 animate-pulse danger-glow' : 'border-emerald-500/30 bg-slate-800/80 safe-glow' }}">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Status Sistem</p>
                        <div class="mt-2 flex items-center gap-2">
                            <!-- Indikator bulat berkedip -->
                            <span id="dot-system-status" class="w-3.5 h-3.5 rounded-full {{ $isDanger ? 'bg-red-500 animate-ping' : 'bg-emerald-400' }}"></span>
                            <span id="text-system-status" class="text-2xl sm:text-3xl font-black tracking-tight {{ $isDanger ? 'text-red-400' : 'text-emerald-400' }}">
                                {{ $isDanger ? 'BAHAYA' : 'AMAN' }}
                            </span>
                        </div>
                    </div>
                    <div id="icon-container-system" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border {{ $isDanger ? 'bg-red-500/20 text-red-400 border-red-500/40' : 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30' }}">
                        <i id="icon-system" data-lucide="{{ $isDanger ? 'shield-alert' : 'shield-check' }}" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-700/40 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Kondisi Ruangan:</span>
                    <span id="text-system-condition" class="font-bold {{ $isDanger ? 'text-red-300' : 'text-emerald-300' }}">
                        {{ $isDanger ? 'Terdeteksi Bahaya!' : 'Normal Terkendali' }}
                    </span>
                </div>
            </div>

            <!-- KARTU 2: Status Koneksi ESP32 -->
            <div id="card-online" class="relative overflow-hidden rounded-2xl bg-slate-800/80 p-5 border {{ $isOnline ? 'border-slate-700/60' : 'border-amber-500/30' }} shadow-lg transition duration-200">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Koneksi ESP32</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span id="text-online-status" class="text-2xl sm:text-3xl font-black tracking-tight {{ $isOnline ? 'text-emerald-400' : 'text-slate-400' }}">
                                {{ $isOnline ? 'ONLINE' : 'OFFLINE' }}
                            </span>
                            <span id="ping-online" class="relative flex h-3 w-3 {{ $isOnline ? '' : 'hidden' }}">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                            </span>
                        </div>
                    </div>
                    <div id="icon-container-online" class="w-11 h-11 rounded-xl {{ $isOnline ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-700/40 text-slate-400' }} flex items-center justify-center shrink-0 border">
                        <i data-lucide="wifi" class="w-6 h-6"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-700/40 flex items-center justify-between text-xs">
                    <span class="text-slate-400 truncate max-w-[120px]">{{ $device->name ?? 'Belum Terhubung' }}</span>
                    <span id="text-device-code" class="font-mono-val text-slate-300">
                        {{ $device->device_code ?? '-' }}
                    </span>
                </div>
            </div>

            <!-- KARTU 3: Nilai Sensor MQ-2 -->
            @php
                $mq2Percent = min(100, max(5, ($mq2Value / 800) * 100));
                $mq2Color = $mq2Value > $mq2Threshold ? 'red' : ($mq2Value >= 300 ? 'amber' : 'emerald');
            @endphp
            <div id="card-mq2" class="relative overflow-hidden rounded-2xl bg-slate-800/80 p-5 border border-slate-700/60 shadow-lg transition duration-200">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Sensor Asap (MQ-2)</p>
                        <div class="mt-2 flex items-baseline gap-1.5">
                            <span id="text-mq2-val" class="text-2xl sm:text-3xl font-black tracking-tight font-mono-val {{ $mq2Value > $mq2Threshold ? 'text-red-400' : ($mq2Value >= 300 ? 'text-amber-400' : 'text-white') }}">
                                {{ $mq2Value }}
                            </span>
                            <span class="text-xs font-semibold text-slate-400">PPM/ADC</span>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-xl bg-orange-500/10 text-orange-400 border border-orange-500/20 flex items-center justify-center shrink-0">
                        <i data-lucide="wind" class="w-6 h-6"></i>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mt-3">
                    <div class="w-full bg-slate-700/50 rounded-full h-2 overflow-hidden">
                        <div id="bar-mq2" class="h-2 rounded-full transition-all duration-500 {{ $mq2Color === 'red' ? 'bg-red-500' : ($mq2Color === 'amber' ? 'bg-amber-400' : 'bg-emerald-500') }}"
                             style="width: {{ $mq2Percent }}%"></div>
                    </div>
                </div>

                <div class="mt-3 pt-2 border-t border-slate-700/40 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Threshold: &gt; {{ $mq2Threshold }}</span>
                    <span id="text-mq2-status" class="font-medium {{ $mq2Color === 'red' ? 'text-red-400 font-bold' : ($mq2Color === 'amber' ? 'text-amber-300' : 'text-emerald-300') }}">
                        {{ $mq2Color === 'red' ? 'Bahaya Asap' : ($mq2Color === 'amber' ? 'Waspada' : 'Normal') }}
                    </span>
                </div>
            </div>

            <!-- KARTU 4: Deteksi Api (Flame Sensor) -->
            <div id="card-flame" class="relative overflow-hidden rounded-2xl p-5 border shadow-lg transition duration-200 {{ $flameDetected ? 'border-red-500/60 bg-red-950/30' : 'border-slate-700/60 bg-slate-800/80' }}">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Deteksi Api</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span id="text-flame-status" class="text-2xl sm:text-3xl font-black tracking-tight {{ $flameDetected ? 'text-red-500 animate-pulse' : 'text-emerald-400' }}">
                                {{ $flameDetected ? 'API ADA!' : 'TIDAK ADA' }}
                            </span>
                        </div>
                    </div>
                    <div id="icon-container-flame" class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border {{ $flameDetected ? 'bg-red-500/20 text-red-400 border-red-500/30' : 'bg-slate-700/40 text-slate-300 border-slate-600/30' }}">
                        <i id="icon-flame" data-lucide="flame" class="w-6 h-6 {{ $flameDetected ? 'text-red-400 animate-bounce' : 'text-slate-400' }}"></i>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-700/40 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Sensor Flame IR</span>
                    <span id="text-flame-condition" class="font-medium {{ $flameDetected ? 'text-red-400 font-bold' : 'text-emerald-300' }}">
                        {{ $flameDetected ? 'Nyala Api Terdeteksi!' : 'Aman (Tanpa Api)' }}
                    </span>
                </div>
            </div>

        </div>

        <!-- Telemetry Summary Info Bar -->
        <div class="bg-slate-800/50 rounded-xl px-4 py-3 border border-slate-700/50 flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
            <div class="flex items-center gap-2">
                <i data-lucide="radio" class="w-4 h-4 text-indigo-400"></i>
                <span>Data Pembacaan Terakhir:</span>
                <span id="text-last-reading" class="text-slate-200 font-medium">
                    {{ $latestReading ? $latestReading->created_at->format('d/m/Y H:i:s') . ' (' . $latestReading->created_at->diffForHumans() . ')' : 'Belum ada data sensor' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <i data-lucide="activity" class="w-4 h-4 text-slate-400"></i>
                <span>Heartbeat Terakhir ESP32:</span>
                <span id="text-last-heartbeat" class="text-slate-200 font-medium font-mono-val">
                    {{ $device && $device->last_heartbeat ? $device->last_heartbeat->format('H:i:s') . ' (' . $device->last_heartbeat->diffForHumans() . ')' : '-' }}
                </span>
            </div>
        </div>

        <!-- SECTION: REAL-TIME MQ-2 CHART -->
        <div class="rounded-2xl bg-slate-800/80 border border-slate-700/60 p-5 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-orange-500/10 text-orange-400 flex items-center justify-center border border-orange-500/20">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-white tracking-tight">Tren Konsentrasi Asap (MQ-2 Sensor Trend)</h2>
                        <p class="text-xs text-slate-400">Grafik pembacaan sensor MQ-2 diperbarui otomatis secara real-time</p>
                    </div>
                </div>
                <span class="text-xs text-slate-400 bg-slate-900/60 px-2.5 py-1 rounded-lg border border-slate-700/60 font-mono-val">
                    Batas Bahaya: &gt; 400 PPM
                </span>
            </div>
            <div class="h-56 sm:h-60 w-full">
                <canvas id="mq2Chart"></canvas>
            </div>
        </div>

        <!-- SECTION: TABEL RIWAYAT KEJADIAN (ALARM_EVENTS) -->
        <div class="rounded-2xl bg-slate-800/80 border border-slate-700/60 shadow-xl overflow-hidden">
            <!-- Table Header Bar -->
            <div class="p-5 border-b border-slate-700/60 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-red-500/10 text-red-400 flex items-center justify-center border border-red-500/20">
                        <i data-lucide="history" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">Riwayat Kejadian Alarm (Alarm Events)</h2>
                        <p class="text-xs text-slate-400">Pencatatan insiden bahaya api dan asap tanpa duplikasi berulang</p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-xs">
                    <span id="badge-total-events" class="px-2.5 py-1 rounded-lg bg-slate-900/60 text-slate-300 border border-slate-700/60 font-mono-val">
                        Total Riwayat: {{ $alarmEvents->count() }}
                    </span>
                </div>
            </div>

            <!-- Table Responsive Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs sm:text-sm">
                    <thead class="bg-slate-900/60 text-slate-400 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-700/60">
                        <tr>
                            <th scope="col" class="py-3.5 px-4 text-center w-14">No</th>
                            <th scope="col" class="py-3.5 px-4">Waktu Kejadian</th>
                            <th scope="col" class="py-3.5 px-4">Perangkat</th>
                            <th scope="col" class="py-3.5 px-4">Jenis Bahaya</th>
                            <th scope="col" class="py-3.5 px-4 text-right">Nilai Sensor</th>
                            <th scope="col" class="py-3.5 px-4 text-center">Status Penanganan</th>
                            <th scope="col" class="py-3.5 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="alarm-table-body" class="divide-y divide-slate-700/40 text-slate-200">
                        @forelse ($alarmEvents as $index => $event)
                            <tr class="hover:bg-slate-700/20 transition-colors">
                                <!-- No -->
                                <td class="py-3.5 px-4 text-center text-slate-400 font-mono-val">
                                    {{ $index + 1 }}
                                </td>

                                <!-- Waktu Kejadian -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-100">
                                        {{ $event->detected_at ? $event->detected_at->format('d/m/Y H:i:s') : '-' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400">
                                        {{ $event->detected_at ? $event->detected_at->diffForHumans() : '' }}
                                    </div>
                                </td>

                                <!-- Perangkat -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <div class="font-medium text-slate-200">
                                        {{ $event->device->name ?? 'Perangkat IoT' }}
                                    </div>
                                    <div class="text-[11px] text-slate-400 font-mono-val">
                                        {{ $event->device->device_code ?? '-' }}
                                    </div>
                                </td>

                                <!-- Jenis Bahaya -->
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    @if ($event->event_type === 'BOTH')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-purple-500/15 text-purple-300 border border-purple-500/30">
                                            <i data-lucide="alert-octagon" class="w-3.5 h-3.5 text-purple-400"></i>
                                            API & ASAP (BOTH)
                                        </span>
                                    @elseif ($event->event_type === 'FLAME')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                                            <i data-lucide="flame" class="w-3.5 h-3.5 text-red-400"></i>
                                            API (FLAME)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                                            <i data-lucide="cloud-fog" class="w-3.5 h-3.5 text-amber-400"></i>
                                            ASAP (SMOKE)
                                        </span>
                                    @endif
                                </td>

                                <!-- Nilai Sensor (mq2_value) -->
                                <td class="py-3.5 px-4 text-right font-mono-val font-semibold whitespace-nowrap">
                                    <span class="{{ $event->mq2_value > 400 ? 'text-red-400' : 'text-slate-200' }}">
                                        {{ number_format($event->mq2_value) }}
                                    </span>
                                    <span class="text-xs text-slate-400 font-normal">PPM</span>
                                </td>

                                <!-- Status Penanganan -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if ($event->status === 'DANGER')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-300 border border-red-500/40 animate-pulse">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span>
                                            DANGER
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                                            <i data-lucide="check" class="w-3 h-3 text-emerald-400"></i>
                                            RESOLVED
                                        </span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                    @if ($event->status === 'DANGER')
                                        <button onclick="resolveAlarm({{ $event->id }})" 
                                                class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition shadow inline-flex items-center gap-1">
                                            <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                                            <span>Selesaikan</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-500 italic">Selesai</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="empty-table-row">
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center mb-3 text-slate-500">
                                            <i data-lucide="shield-check" class="w-6 h-6"></i>
                                        </div>
                                        <p class="font-medium text-slate-300 text-sm">Tidak ada riwayat bahaya alarm</p>
                                        <p class="text-xs text-slate-500 mt-1">Sistem berjalan dengan aman dan belum ada insiden yang tercatat.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-slate-900/80 py-4 text-center text-xs text-slate-500">
        <div class="max-w-7xl mx-auto px-4">
            Fire Alarm & Smoke Monitoring IoT Dashboard &bull; Laravel {{ app()->version() }} &bull; ESP32 Node
        </div>
    </footer>

    <!-- SCRIPTS -->
    <script>
        // Inisialisasi ikon
        lucide.createIcons();

        // Digital Clock
        function updateClock() {
            const now = new Date();
            const clockEl = document.getElementById('current-clock');
            if (clockEl) {
                clockEl.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        /* ==============================================================
         * 1. AUDIO ALARM SIRINE & BEEP SISTEM (Web Audio API)
         * ============================================================== */
        let audioCtx = null;
        let sirenInterval = null;
        let isAudioMuted = false;
        let isSirenActive = false;

        function getAudioContext() {
            if (!audioCtx) {
                const AudioContextClass = window.AudioContext || window.webkitAudioContext;
                if (AudioContextClass) {
                    audioCtx = new AudioContextClass();
                }
            }
            if (audioCtx && audioCtx.state === 'suspended') {
                audioCtx.resume();
            }
            return audioCtx;
        }

        // Memainkan 1 pulsa beep/nada sirine kebakaran
        function playBeepTone(freq, durationMs) {
            if (isAudioMuted) return;
            const ctx = getAudioContext();
            if (!ctx) return;

            try {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(freq, ctx.currentTime);

                gain.gain.setValueAtTime(0.18, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + (durationMs / 1000));

                osc.connect(gain);
                gain.connect(ctx.destination);

                osc.start();
                osc.stop(ctx.currentTime + (durationMs / 1000));
            } catch (err) {
                console.warn('Gagal memutar audio:', err);
            }
        }

        // Mulai pola suara sirine (nada tinggi dan rendah bergantian)
        function startFireSiren() {
            if (isSirenActive || isAudioMuted) return;
            isSirenActive = true;
            let highTone = true;

            // Langsung bunyikan nada pertama
            playBeepTone(880, 260);

            sirenInterval = setInterval(() => {
                if (isAudioMuted) {
                    stopFireSiren();
                    return;
                }
                playBeepTone(highTone ? 880 : 580, 260);
                highTone = !highTone;
            }, 300);
        }

        // Hentikan sirine
        function stopFireSiren() {
            if (sirenInterval) {
                clearInterval(sirenInterval);
                sirenInterval = null;
            }
            isSirenActive = false;
        }

        // Toggle Audio Alarm secara umum
        function toggleAudioAlarm() {
            getAudioContext();
            isAudioMuted = !isAudioMuted;
            updateSoundButtonUI();

            if (isAudioMuted) {
                stopFireSiren();
            }
        }

        // Tombol cepat "Matikan Suara" pada banner darurat
        function silenceCurrentAlarm() {
            getAudioContext();
            isAudioMuted = true;
            stopFireSiren();
            updateSoundButtonUI();
        }

        function updateSoundButtonUI() {
            const btn = document.getElementById('btn-sound-toggle');
            const icon = document.getElementById('icon-sound');
            const text = document.getElementById('text-sound');
            const bannerMuteText = document.getElementById('banner-mute-text');

            if (isAudioMuted) {
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition shadow-sm bg-red-950/40 text-red-300 border-red-500/40';
                text.textContent = 'Suara: Dimatikan';
                icon.setAttribute('data-lucide', 'volume-x');
                if (bannerMuteText) bannerMuteText.textContent = 'Suara Telah Dimatikan';
            } else {
                btn.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border transition shadow-sm bg-slate-800 hover:bg-slate-700 text-slate-300 border-slate-700';
                text.textContent = 'Suara: Aktif';
                icon.setAttribute('data-lucide', 'volume-2');
                if (bannerMuteText) bannerMuteText.textContent = 'Matikan Suara Sirine';
            }
            lucide.createIcons();
        }

        /* ==============================================================
         * 2. CHART.JS REAL-TIME
         * ============================================================== */
        const initialReadings = @json($recentReadings);
        const chartLabels = initialReadings.map(r => {
            const date = new Date(r.created_at);
            return isNaN(date) ? '' : date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        });
        const chartData = initialReadings.map(r => r.mq2_value);

        const ctx = document.getElementById('mq2Chart').getContext('2d');
        const mq2Chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [
                    {
                        label: 'Nilai MQ-2 (PPM)',
                        data: chartData,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointBackgroundColor: '#ea580c',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    },
                    {
                        label: 'Ambang Batas Bahaya (400 PPM)',
                        data: Array(chartLabels.length || 1).fill(400),
                        borderColor: 'rgba(239, 68, 68, 0.7)',
                        borderDash: [5, 5],
                        borderWidth: 1.5,
                        pointRadius: 0,
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { color: '#94a3b8', font: { size: 11 } } }
                },
                scales: {
                    x: {
                        ticks: { color: '#64748b', font: { size: 10 } },
                        grid: { color: 'rgba(51, 65, 85, 0.25)' }
                    },
                    y: {
                        min: 0,
                        suggestedMax: 800,
                        ticks: { color: '#64748b', font: { size: 10 } },
                        grid: { color: 'rgba(51, 65, 85, 0.25)' }
                    }
                }
            }
        });

        /* ==============================================================
         * 3. REAL-TIME POLLING JAVASCRIPT (Tiap 3 Detik ke /api/v1/status)
         * ============================================================== */
        async function fetchRealTimeStatus() {
            try {
                const response = await fetch('/api/v1/status', {
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) return;

                const data = await response.json();

                // 1. UPDATE KARTU STATUS SISTEM (AMAN / BAHAYA)
                const cardSystem = document.getElementById('card-system');
                const dotSystemStatus = document.getElementById('dot-system-status');
                const textSystemStatus = document.getElementById('text-system-status');
                const textSystemCondition = document.getElementById('text-system-condition');
                const iconContainerSystem = document.getElementById('icon-container-system');
                const dangerBanner = document.getElementById('danger-banner');
                const dangerBannerDesc = document.getElementById('danger-banner-desc');
                const liveBadge = document.getElementById('live-indicator-badge');

                if (data.is_danger) {
                    // Indikator Merah Berkedip
                    cardSystem.className = 'relative overflow-hidden rounded-2xl p-5 border border-red-500 bg-red-950/40 animate-pulse danger-glow transition-all duration-300';
                    dotSystemStatus.className = 'w-3.5 h-3.5 rounded-full bg-red-500 animate-ping';
                    textSystemStatus.textContent = 'BAHAYA';
                    textSystemStatus.className = 'text-2xl sm:text-3xl font-black tracking-tight text-red-400';
                    textSystemCondition.textContent = 'Terdeteksi Bahaya!';
                    textSystemCondition.className = 'font-bold text-red-300';
                    iconContainerSystem.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border bg-red-500/20 text-red-400 border-red-500/40';

                    dangerBanner.classList.remove('hidden');

                    let desc = 'Perhatian! ';
                    if (data.flame_detected && data.mq2_value > data.mq2_threshold) {
                        desc += 'Nyala api dan asap pekat terdeteksi secara bersamaan!';
                    } else if (data.flame_detected) {
                        desc += 'Sensor api mendeteksi nyala api langsung di area pemantauan!';
                    } else {
                        desc += `Konsentrasi asap/gas MQ-2 mencapai ${data.mq2_value} PPM (melebihi ambang batas ${data.mq2_threshold})!`;
                    }
                    dangerBannerDesc.textContent = desc;

                    // Bunyikan Sirine
                    startFireSiren();

                    liveBadge.className = 'text-xs font-semibold px-2 py-0.5 rounded-full bg-red-500/10 text-red-400 border border-red-500/20 flex items-center gap-1.5';
                } else {
                    // Indikator Hijau AMAN
                    cardSystem.className = 'relative overflow-hidden rounded-2xl p-5 border border-emerald-500/30 bg-slate-800/80 safe-glow transition-all duration-300';
                    dotSystemStatus.className = 'w-3.5 h-3.5 rounded-full bg-emerald-400';
                    textSystemStatus.textContent = 'AMAN';
                    textSystemStatus.className = 'text-2xl sm:text-3xl font-black tracking-tight text-emerald-400';
                    textSystemCondition.textContent = 'Normal Terkendali';
                    textSystemCondition.className = 'font-bold text-emerald-300';
                    iconContainerSystem.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border bg-emerald-500/20 text-emerald-400 border-emerald-500/30';

                    dangerBanner.classList.add('hidden');

                    // Hentikan Sirine
                    stopFireSiren();

                    liveBadge.className = 'text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5';
                }

                // 2. UPDATE KONEKSI ESP32
                const textOnlineStatus = document.getElementById('text-online-status');
                const pingOnline = document.getElementById('ping-online');
                const iconContainerOnline = document.getElementById('icon-container-online');
                if (data.is_online) {
                    textOnlineStatus.textContent = 'ONLINE';
                    textOnlineStatus.className = 'text-2xl sm:text-3xl font-black tracking-tight text-emerald-400';
                    pingOnline.classList.remove('hidden');
                    iconContainerOnline.className = 'w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center shrink-0 border';
                } else {
                    textOnlineStatus.textContent = 'OFFLINE';
                    textOnlineStatus.className = 'text-2xl sm:text-3xl font-black tracking-tight text-slate-400';
                    pingOnline.classList.add('hidden');
                    iconContainerOnline.className = 'w-11 h-11 rounded-xl bg-slate-700/40 text-slate-400 border border-slate-600/30 flex items-center justify-center shrink-0 border';
                }

                // 3. UPDATE NILAI SENSOR MQ-2 & PROGRESS BAR
                const textMq2Val = document.getElementById('text-mq2-val');
                const barMq2 = document.getElementById('bar-mq2');
                const textMq2Status = document.getElementById('text-mq2-status');
                textMq2Val.textContent = data.mq2_value;

                const mq2Percent = Math.min(100, Math.max(5, (data.mq2_value / 800) * 100));
                barMq2.style.width = mq2Percent + '%';

                if (data.mq2_value > data.mq2_threshold) {
                    textMq2Val.className = 'text-2xl sm:text-3xl font-black tracking-tight font-mono-val text-red-400';
                    barMq2.className = 'h-2 rounded-full transition-all duration-500 bg-red-500';
                    textMq2Status.textContent = 'Bahaya Asap';
                    textMq2Status.className = 'font-bold text-red-400';
                } else if (data.mq2_value >= 300) {
                    textMq2Val.className = 'text-2xl sm:text-3xl font-black tracking-tight font-mono-val text-amber-400';
                    barMq2.className = 'h-2 rounded-full transition-all duration-500 bg-amber-400';
                    textMq2Status.textContent = 'Waspada';
                    textMq2Status.className = 'font-medium text-amber-300';
                } else {
                    textMq2Val.className = 'text-2xl sm:text-3xl font-black tracking-tight font-mono-val text-white';
                    barMq2.className = 'h-2 rounded-full transition-all duration-500 bg-emerald-500';
                    textMq2Status.textContent = 'Normal';
                    textMq2Status.className = 'font-medium text-emerald-300';
                }

                // 4. UPDATE DETEKSI API (FLAME)
                const cardFlame = document.getElementById('card-flame');
                const textFlameStatus = document.getElementById('text-flame-status');
                const textFlameCondition = document.getElementById('text-flame-condition');
                const iconContainerFlame = document.getElementById('icon-container-flame');
                const iconFlame = document.getElementById('icon-flame');

                if (data.flame_detected) {
                    cardFlame.className = 'relative overflow-hidden rounded-2xl p-5 border border-red-500/60 bg-red-950/30 shadow-lg transition duration-200';
                    textFlameStatus.textContent = 'API ADA!';
                    textFlameStatus.className = 'text-2xl sm:text-3xl font-black tracking-tight text-red-500 animate-pulse';
                    textFlameCondition.textContent = 'Nyala Api Terdeteksi!';
                    textFlameCondition.className = 'font-bold text-red-400';
                    iconContainerFlame.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border bg-red-500/20 text-red-400 border-red-500/30';
                    iconFlame.className = 'w-6 h-6 text-red-400 animate-bounce';
                } else {
                    cardFlame.className = 'relative overflow-hidden rounded-2xl p-5 border border-slate-700/60 bg-slate-800/80 shadow-lg transition duration-200';
                    textFlameStatus.textContent = 'TIDAK ADA';
                    textFlameStatus.className = 'text-2xl sm:text-3xl font-black tracking-tight text-emerald-400';
                    textFlameCondition.textContent = 'Aman (Tanpa Api)';
                    textFlameCondition.className = 'font-medium text-emerald-300';
                    iconContainerFlame.className = 'w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border bg-slate-700/40 text-slate-300 border-slate-600/30';
                    iconFlame.className = 'w-6 h-6 text-slate-400';
                }

                // 5. UPDATE TIMESTAMP INFO
                document.getElementById('text-last-reading').textContent = `${data.last_reading_full} (${data.last_reading_time})`;
                document.getElementById('text-last-heartbeat').textContent = data.last_heartbeat;

                // 6. UPDATE GRAFIK CHART.JS
                if (data.recent_readings && data.recent_readings.length > 0) {
                    mq2Chart.data.labels = data.recent_readings.map(r => r.time);
                    mq2Chart.data.datasets[0].data = data.recent_readings.map(r => r.mq2);
                    mq2Chart.data.datasets[1].data = Array(data.recent_readings.length).fill(data.mq2_threshold);
                    mq2Chart.update('none');
                }

                // 7. UPDATE TABEL RIWAYAT ALARM SECARA REAL-TIME
                if (data.alarm_events) {
                    renderAlarmTable(data.alarm_events);
                }

                lucide.createIcons();
            } catch (err) {
                console.warn('Gagal polling status real-time:', err);
            }
        }

        // Render data tabel riwayat alarm
        function renderAlarmTable(events) {
            const tbody = document.getElementById('alarm-table-body');
            const totalBadge = document.getElementById('badge-total-events');
            if (totalBadge) totalBadge.textContent = `Total Riwayat: ${events.length}`;

            if (!tbody) return;

            if (events.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-12 h-12 rounded-2xl bg-slate-800 flex items-center justify-center mb-3 text-slate-500">
                                    <i data-lucide="shield-check" class="w-6 h-6"></i>
                                </div>
                                <p class="font-medium text-slate-300 text-sm">Tidak ada riwayat bahaya alarm</p>
                                <p class="text-xs text-slate-500 mt-1">Sistem berjalan dengan aman dan belum ada insiden yang tercatat.</p>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = events.map(event => {
                let badgeType = '';
                if (event.event_type === 'BOTH') {
                    badgeType = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-purple-500/15 text-purple-300 border border-purple-500/30">
                        <i data-lucide="alert-octagon" class="w-3.5 h-3.5 text-purple-400"></i> API & ASAP (BOTH)
                    </span>`;
                } else if (event.event_type === 'FLAME') {
                    badgeType = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-red-500/15 text-red-400 border border-red-500/30">
                        <i data-lucide="flame" class="w-3.5 h-3.5 text-red-400"></i> API (FLAME)
                    </span>`;
                } else {
                    badgeType = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold bg-amber-500/15 text-amber-300 border border-amber-500/30">
                        <i data-lucide="cloud-fog" class="w-3.5 h-3.5 text-amber-400"></i> ASAP (SMOKE)
                    </span>`;
                }

                let badgeStatus = event.status === 'DANGER'
                    ? `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-red-500/20 text-red-300 border border-red-500/40 animate-pulse">
                         <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> DANGER
                       </span>`
                    : `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                         <i data-lucide="check" class="w-3 h-3 text-emerald-400"></i> RESOLVED
                       </span>`;

                let actionBtn = event.status === 'DANGER'
                    ? `<button onclick="resolveAlarm(${event.id})" 
                               class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-semibold transition shadow inline-flex items-center gap-1">
                           <i data-lucide="check-circle" class="w-3.5 h-3.5"></i>
                           <span>Selesaikan</span>
                       </button>`
                    : `<span class="text-xs text-slate-500 italic">Selesai</span>`;

                return `
                    <tr class="hover:bg-slate-700/20 transition-colors">
                        <td class="py-3.5 px-4 text-center text-slate-400 font-mono-val">${event.no}</td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="font-medium text-slate-100">${event.detected_at_formatted}</div>
                            <div class="text-[11px] text-slate-400">${event.detected_at_diff}</div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            <div class="font-medium text-slate-200">${event.device_name}</div>
                            <div class="text-[11px] text-slate-400 font-mono-val">${event.device_code}</div>
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">${badgeType}</td>
                        <td class="py-3.5 px-4 text-right font-mono-val font-semibold whitespace-nowrap">
                            <span class="${event.mq2_value > 400 ? 'text-red-400' : 'text-slate-200'}">${event.mq2_value}</span>
                            <span class="text-xs text-slate-400 font-normal">PPM</span>
                        </td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">${badgeStatus}</td>
                        <td class="py-3.5 px-4 text-center whitespace-nowrap">${actionBtn}</td>
                    </tr>
                `;
            }).join('');
        }

        // Interval Polling Tiap 3 Detik
        setInterval(fetchRealTimeStatus, 3000);

        /* ==============================================================
         * 4. FUNGSI SIMULASI & RESOLVE
         * ============================================================== */
        function toggleSimulationPanel() {
            getAudioContext();
            document.getElementById('simulation-panel').classList.toggle('hidden');
        }

        async function sendSimulationData(mq2Value, flameDetected) {
            getAudioContext();
            const feedback = document.getElementById('sim-feedback');
            feedback.classList.remove('hidden');
            feedback.innerHTML = '<span class="text-indigo-400">Mengirim data simulasi ke POST /api/v1/sensor-data...</span>';

            try {
                const response = await fetch('/api/v1/sensor-data', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        device_code: 'ESP32-FIRE-01',
                        mq2_value: mq2Value,
                        flame_detected: flameDetected
                    })
                });

                const data = await response.json();
                if (response.ok) {
                    feedback.innerHTML = `<span class="text-emerald-400">✓ Berhasil! Response: "${data.message}". Polling live memperbarui dashboard.</span>`;
                    fetchRealTimeStatus();
                } else {
                    feedback.innerHTML = `<span class="text-red-400">Gagal: ${data.message || 'Error'}</span>`;
                }
            } catch (err) {
                feedback.innerHTML = `<span class="text-red-400">Terjadi kesalahan koneksi API.</span>`;
            }
        }

        async function resolveAlarm(eventId) {
            if (!confirm('Tandai alarm ini sebagai terselesaikan (RESOLVED)?')) return;

            try {
                const response = await fetch(`/api/v1/alarm-events/${eventId}/resolve`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    fetchRealTimeStatus();
                } else {
                    alert('Gagal menyelesaikan alarm.');
                }
            } catch (err) {
                alert('Terjadi kesalahan jaringan.');
            }
        }
    </script>
</body>
</html>
