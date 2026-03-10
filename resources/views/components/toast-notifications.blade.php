<!-- Global Toast Notifications -->
<div x-data="toastNotifications()" 
     x-on:notify.window="add($event.detail)"
     x-init="
        @if(session('message'))
            add({ type: 'success', message: @js(session('message')) });
        @endif
        @if(session('error'))
            add({ type: 'error', message: @js(session('error')) });
        @endif
        @if(session('warning'))
            add({ type: 'warning', message: @js(session('warning')) });
        @endif
        @if(session('info'))
            add({ type: 'info', message: @js(session('info')) });
        @endif
     "
     class="fixed top-4 right-4 z-[9999] space-y-2 max-w-sm w-full pointer-events-none">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.visible"
             x-transition:enter="transform ease-out duration-300 transition"
             x-transition:enter-start="translate-x-full opacity-0"
             x-transition:enter-end="translate-x-0 opacity-100"
             x-transition:leave="transform ease-in duration-200 transition"
             x-transition:leave-start="translate-x-0 opacity-100"
             x-transition:leave-end="translate-x-full opacity-0"
             class="pointer-events-auto rounded-lg shadow-lg overflow-hidden"
             :class="{
                 'bg-green-50 border border-green-200': toast.type === 'success',
                 'bg-red-50 border border-red-200': toast.type === 'error',
                 'bg-yellow-50 border border-yellow-200': toast.type === 'warning',
                 'bg-blue-50 border border-blue-200': toast.type === 'info'
             }">
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <!-- Success Icon -->
                        <template x-if="toast.type === 'success'">
                            <svg class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        <!-- Error Icon -->
                        <template x-if="toast.type === 'error'">
                            <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                        <!-- Warning Icon -->
                        <template x-if="toast.type === 'warning'">
                            <svg class="h-5 w-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </template>
                        <!-- Info Icon -->
                        <template x-if="toast.type === 'info'">
                            <svg class="h-5 w-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </template>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium"
                           :class="{
                               'text-green-800': toast.type === 'success',
                               'text-red-800': toast.type === 'error',
                               'text-yellow-800': toast.type === 'warning',
                               'text-blue-800': toast.type === 'info'
                           }"
                           x-text="toast.message"></p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button @click="remove(toast.id)" 
                                class="rounded-md inline-flex focus:outline-none focus:ring-2 focus:ring-offset-2"
                                :class="{
                                    'text-green-500 hover:text-green-600 focus:ring-green-500': toast.type === 'success',
                                    'text-red-500 hover:text-red-600 focus:ring-red-500': toast.type === 'error',
                                    'text-yellow-500 hover:text-yellow-600 focus:ring-yellow-500': toast.type === 'warning',
                                    'text-blue-500 hover:text-blue-600 focus:ring-blue-500': toast.type === 'info'
                                }">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastNotifications', () => ({
            toasts: [],
            add(detail) {
                const id = Date.now() + Math.random();
                this.toasts.push({
                    id: id,
                    type: detail.type || 'info',
                    message: detail.message,
                    visible: true
                });
                
                // Auto-remove after 5 seconds
                setTimeout(() => {
                    this.remove(id);
                }, 5000);
            },
            remove(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index > -1) {
                    this.toasts[index].visible = false;
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(t => t.id !== id);
                    }, 300);
                }
            }
        }));
    });

    // Listen for Livewire events
    document.addEventListener('livewire:init', () => {
        Livewire.on('toast', (data) => {
            window.dispatchEvent(new CustomEvent('notify', { detail: data[0] || data }));
        });
    });
</script>
