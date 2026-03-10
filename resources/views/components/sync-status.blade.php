<!-- Offline Sync Status Indicator -->
<div x-data="syncStatus()" 
     x-init="init()"
     class="fixed bottom-4 right-4 z-[9998]">
    
    <!-- Offline Badge -->
    <div x-show="!isOnline" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex items-center gap-2 px-4 py-2 bg-amber-500 text-white rounded-full shadow-lg">
        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414"/>
        </svg>
        <span class="font-medium text-sm">Offline Mode</span>
        <span x-show="pendingCount > 0" class="bg-white/20 px-2 py-0.5 rounded-full text-xs" x-text="pendingCount + ' pending'"></span>
    </div>

    <!-- Syncing Indicator -->
    <div x-show="syncInProgress" 
         x-transition
         class="flex items-center gap-2 px-4 py-2 bg-blue-500 text-white rounded-full shadow-lg">
        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="font-medium text-sm">Syncing...</span>
    </div>

    <!-- Pending Transactions Badge (when online but has pending) -->
    <button x-show="isOnline && !syncInProgress && pendingCount > 0" 
            @click="syncNow()"
            x-transition
            class="flex items-center gap-2 px-4 py-2 bg-brand-500 hover:bg-brand-600 text-white rounded-full shadow-lg transition-colors cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        <span class="font-medium text-sm" x-text="pendingCount + ' pending'"></span>
        <span class="text-xs opacity-75">Tap to sync</span>
    </button>

    <!-- Connection Restored Toast -->
    <div x-show="showReconnected" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="flex items-center gap-2 px-4 py-2 bg-green-500 text-white rounded-full shadow-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="font-medium text-sm">Back Online</span>
    </div>
</div>

<script>
    function syncStatus() {
        return {
            isOnline: navigator.onLine,
            pendingCount: 0,
            syncInProgress: false,
            showReconnected: false,
            wasOffline: false,

            init() {
                // Initial status check
                this.updateStatus();

                // Listen for online/offline events
                window.addEventListener('online', () => {
                    this.isOnline = true;
                    if (this.wasOffline) {
                        this.showReconnectedToast();
                    }
                    this.updateStatus();
                });

                window.addEventListener('offline', () => {
                    this.wasOffline = true;
                    this.isOnline = false;
                    this.updateStatus();
                });

                // Listen for sync events
                window.addEventListener('offline-sync-complete', (e) => {
                    this.syncInProgress = false;
                    this.updateStatus();
                    
                    if (e.detail.synced > 0) {
                        window.dispatchEvent(new CustomEvent('notify', {
                            detail: {
                                type: 'success',
                                message: `Synced ${e.detail.synced} transaction(s)`
                            }
                        }));
                    }
                });

                window.addEventListener('offline-sync-error', () => {
                    this.syncInProgress = false;
                    this.updateStatus();
                });

                // Poll for pending count (in case of background updates)
                setInterval(() => this.updateStatus(), 5000);
            },

            async updateStatus() {
                if (window.offlineQueue) {
                    const status = await window.offlineQueue.getStatus();
                    this.pendingCount = status.pendingCount;
                    this.syncInProgress = status.syncInProgress;
                }
            },

            async syncNow() {
                if (window.offlineQueue && this.isOnline && !this.syncInProgress) {
                    this.syncInProgress = true;
                    try {
                        await window.offlineQueue.syncPending();
                    } catch (e) {
                        console.error('Manual sync failed:', e);
                    }
                }
            },

            showReconnectedToast() {
                this.showReconnected = true;
                setTimeout(() => {
                    this.showReconnected = false;
                    this.wasOffline = false;
                }, 3000);
            }
        };
    }
</script>
