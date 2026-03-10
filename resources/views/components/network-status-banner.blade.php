<!-- Network Status Banner - Shows at top when offline or slow connection -->
<div x-data="networkStatus()" 
     x-init="init()"
     x-show="showBanner"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-full"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-full"
     class="w-full"
     :class="{
         'bg-amber-500': isOffline,
         'bg-yellow-500': !isOffline && isSlowConnection,
         'bg-blue-500': !isOffline && !isSlowConnection && pendingCount > 0
     }">
    
    <div class="px-4 py-2">
        <div class="flex items-center justify-between text-white text-sm">
            <!-- Left: Status message -->
            <div class="flex items-center gap-2">
                <!-- Offline Icon -->
                <template x-if="isOffline">
                    <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/>
                    </svg>
                </template>
                
                <!-- Slow Connection Icon -->
                <template x-if="!isOffline && isSlowConnection">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </template>
                
                <!-- Pending Sync Icon -->
                <template x-if="!isOffline && !isSlowConnection && pendingCount > 0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                </template>
                
                <!-- Message -->
                <span class="font-medium">
                    <template x-if="isOffline">
                        <span>You're offline. Don't worry - your work will be saved locally.</span>
                    </template>
                    <template x-if="!isOffline && isSlowConnection">
                        <span>Slow connection detected. Transactions may take longer to process.</span>
                    </template>
                    <template x-if="!isOffline && !isSlowConnection && pendingCount > 0">
                        <span x-text="pendingCount + ' pending transaction(s) waiting to sync'"></span>
                    </template>
                </span>
            </div>
            
            <!-- Right: Actions -->
            <div class="flex items-center gap-3">
                <!-- Pending count badge -->
                <span x-show="pendingCount > 0 && isOffline" 
                      class="bg-white/20 px-2 py-0.5 rounded-full text-xs font-medium"
                      x-text="pendingCount + ' pending'"></span>
                
                <!-- Sync button (when online with pending) -->
                <button x-show="!isOffline && pendingCount > 0 && !syncInProgress"
                        @click="syncNow()"
                        class="bg-white/20 hover:bg-white/30 px-3 py-1 rounded-full text-xs font-medium transition-colors">
                    Sync Now
                </button>
                
                <!-- Syncing indicator -->
                <span x-show="syncInProgress" class="flex items-center gap-1 text-xs">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Syncing...
                </span>
                
                <!-- Dismiss button (for slow connection warning only) -->
                <button x-show="!isOffline && isSlowConnection && pendingCount === 0"
                        @click="dismissSlowWarning()"
                        class="text-white/80 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function networkStatus() {
        return {
            isOffline: !navigator.onLine,
            isSlowConnection: false,
            pendingCount: 0,
            syncInProgress: false,
            showBanner: false,
            slowWarningDismissed: false,
            connectionType: null,
            
            init() {
                this.checkConnection();
                this.updateStatus();
                this.updateBannerVisibility();
                
                // Listen for online/offline events
                window.addEventListener('online', () => {
                    this.isOffline = false;
                    this.checkConnection();
                    this.updateStatus();
                    this.updateBannerVisibility();
                });
                
                window.addEventListener('offline', () => {
                    this.isOffline = true;
                    this.slowWarningDismissed = false;
                    this.updateBannerVisibility();
                });
                
                // Listen for connection change (if supported)
                if (navigator.connection) {
                    navigator.connection.addEventListener('change', () => {
                        this.checkConnection();
                        this.updateBannerVisibility();
                    });
                }
                
                // Listen for sync events
                window.addEventListener('offline-sync-complete', (e) => {
                    this.syncInProgress = false;
                    this.updateStatus();
                    this.updateBannerVisibility();
                });
                
                window.addEventListener('offline-sync-error', () => {
                    this.syncInProgress = false;
                    this.updateStatus();
                });
                
                // Poll for pending count
                setInterval(() => {
                    this.updateStatus();
                    this.updateBannerVisibility();
                }, 5000);
            },
            
            checkConnection() {
                if (navigator.connection) {
                    const conn = navigator.connection;
                    this.connectionType = conn.effectiveType;
                    
                    // Consider 2g or slow-2g as slow connections
                    // Also check if downlink is very low (< 0.5 Mbps)
                    this.isSlowConnection = 
                        conn.effectiveType === 'slow-2g' || 
                        conn.effectiveType === '2g' ||
                        (conn.downlink && conn.downlink < 0.5) ||
                        (conn.rtt && conn.rtt > 1000);
                } else {
                    // Fallback: check with a simple performance test
                    this.performConnectionTest();
                }
            },
            
            async performConnectionTest() {
                // Simple connection speed test using a small request
                try {
                    const start = performance.now();
                    await fetch('/favicon.ico', { cache: 'no-store' });
                    const duration = performance.now() - start;
                    
                    // If fetching favicon takes > 2 seconds, consider it slow
                    this.isSlowConnection = duration > 2000;
                } catch (e) {
                    // If fetch fails, we're probably offline (handled separately)
                }
            },
            
            async updateStatus() {
                if (window.offlineQueue) {
                    const status = await window.offlineQueue.getStatus();
                    this.pendingCount = status.pendingCount;
                    this.syncInProgress = status.syncInProgress;
                }
            },
            
            updateBannerVisibility() {
                this.showBanner = 
                    this.isOffline || 
                    (this.isSlowConnection && !this.slowWarningDismissed) ||
                    this.pendingCount > 0;
            },
            
            async syncNow() {
                if (window.offlineQueue && !this.isOffline && !this.syncInProgress) {
                    this.syncInProgress = true;
                    try {
                        await window.offlineQueue.syncPending();
                    } catch (e) {
                        console.error('Manual sync failed:', e);
                    }
                }
            },
            
            dismissSlowWarning() {
                this.slowWarningDismissed = true;
                this.updateBannerVisibility();
            }
        };
    }
</script>
