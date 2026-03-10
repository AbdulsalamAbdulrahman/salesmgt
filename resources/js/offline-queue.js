/**
 * Offline Queue Manager
 * 
 * Handles storing and syncing transactions when offline.
 * Uses IndexedDB for persistent storage.
 */

const DB_NAME = 'salesmgt_offline';
const DB_VERSION = 1;
const STORE_NAME = 'pending_transactions';

class OfflineQueue {
    constructor() {
        this.db = null;
        this.dbReady = null; // Promise that resolves when DB is ready
        this.isOnline = navigator.onLine;
        this.syncInProgress = false;
        this.listeners = [];
        
        this.init();
    }

    async init() {
        this.dbReady = this.openDatabase();
        await this.dbReady;
        this.setupEventListeners();
        
        // Try to sync any pending transactions on init
        if (this.isOnline) {
            this.syncPending();
        }
    }

    /**
     * Ensure database is ready before operations
     */
    async ensureDbReady() {
        if (this.dbReady) {
            await this.dbReady;
        }
        return this.db !== null;
    }

    openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => {
                console.error('Failed to open offline database');
                reject(request.error);
            };

            request.onsuccess = () => {
                this.db = request.result;
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    const store = db.createObjectStore(STORE_NAME, { 
                        keyPath: 'offline_id' 
                    });
                    store.createIndex('type', 'type', { unique: false });
                    store.createIndex('timestamp', 'timestamp', { unique: false });
                    store.createIndex('status', 'status', { unique: false });
                }
            };
        });
    }

    setupEventListeners() {
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.notifyListeners();
            this.syncPending();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.notifyListeners();
        });
    }

    /**
     * Add a listener for status changes
     */
    onStatusChange(callback) {
        this.listeners.push(callback);
        return () => {
            this.listeners = this.listeners.filter(l => l !== callback);
        };
    }

    notifyListeners() {
        this.getStatus().then(status => {
            this.listeners.forEach(callback => callback(status));
        });
    }

    /**
     * Get current status
     */
    async getStatus() {
        await this.ensureDbReady();
        const count = await this.getPendingCount();
        return {
            isOnline: this.isOnline,
            pendingCount: count,
            syncInProgress: this.syncInProgress,
        };
    }

    /**
     * Generate a unique offline ID
     */
    generateOfflineId() {
        return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Add a transaction to the queue
     */
    async addTransaction(type, data) {
        await this.ensureDbReady();
        if (!this.db) {
            console.error('Database not available');
            return { offlineId: null, queued: false, error: 'Database not available' };
        }
        
        const offlineId = this.generateOfflineId();
        
        const transaction = {
            offline_id: offlineId,
            type: type, // 'sale' or 'expense'
            data: data,
            timestamp: new Date().toISOString(),
            status: 'pending',
            attempts: 0,
        };

        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_NAME], 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.add(transaction);

            request.onsuccess = () => {
                this.notifyListeners();
                resolve({ offlineId, queued: true });
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    /**
     * Get all pending transactions
     */
    async getPendingTransactions() {
        await this.ensureDbReady();
        if (!this.db) {
            return [];
        }
        
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_NAME], 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const index = store.index('status');
            const request = index.getAll('pending');

            request.onsuccess = () => {
                resolve(request.result || []);
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    /**
     * Get count of pending transactions
     */
    async getPendingCount() {
        const pending = await this.getPendingTransactions();
        return pending.length;
    }

    /**
     * Update a transaction's status
     */
    async updateTransaction(offlineId, updates) {
        await this.ensureDbReady();
        if (!this.db) {
            return;
        }
        
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_NAME], 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.get(offlineId);

            request.onsuccess = () => {
                const transaction = request.result;
                if (transaction) {
                    Object.assign(transaction, updates);
                    store.put(transaction);
                    resolve(transaction);
                } else {
                    reject(new Error('Transaction not found'));
                }
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    /**
     * Remove a transaction from the queue
     */
    async removeTransaction(offlineId) {
        await this.ensureDbReady();
        if (!this.db) {
            return;
        }
        
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_NAME], 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.delete(offlineId);

            request.onsuccess = () => {
                this.notifyListeners();
                resolve();
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    /**
     * Sync all pending transactions
     */
    async syncPending() {
        if (this.syncInProgress || !this.isOnline) {
            return;
        }

        this.syncInProgress = true;
        this.notifyListeners();

        try {
            const pending = await this.getPendingTransactions();
            
            if (pending.length === 0) {
                this.syncInProgress = false;
                this.notifyListeners();
                return { synced: 0, failed: 0 };
            }

            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            const response = await fetch('/api/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    transactions: pending.map(t => ({
                        type: t.type,
                        offline_id: t.offline_id,
                        data: t.data,
                    })),
                }),
            });

            if (!response.ok) {
                throw new Error(`Sync failed: ${response.status}`);
            }

            const result = await response.json();

            // Remove successfully synced transactions
            for (const syncResult of result.results) {
                if (syncResult.success) {
                    await this.removeTransaction(syncResult.offline_id);
                } else {
                    // Mark as failed with error message
                    await this.updateTransaction(syncResult.offline_id, {
                        status: 'failed',
                        error: syncResult.message,
                        attempts: (await this.getTransaction(syncResult.offline_id))?.attempts + 1 || 1,
                    });
                }
            }

            this.syncInProgress = false;
            this.notifyListeners();

            // Dispatch event for UI to react
            window.dispatchEvent(new CustomEvent('offline-sync-complete', {
                detail: result,
            }));

            return result;

        } catch (error) {
            console.error('Sync failed:', error);
            this.syncInProgress = false;
            this.notifyListeners();
            
            window.dispatchEvent(new CustomEvent('offline-sync-error', {
                detail: { error: error.message },
            }));

            throw error;
        }
    }

    /**
     * Get a single transaction
     */
    async getTransaction(offlineId) {
        await this.ensureDbReady();
        if (!this.db) {
            return null;
        }
        
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_NAME], 'readonly');
            const store = tx.objectStore(STORE_NAME);
            const request = store.get(offlineId);

            request.onsuccess = () => {
                resolve(request.result);
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }

    /**
     * Clear all transactions (for debugging)
     */
    async clearAll() {
        await this.ensureDbReady();
        if (!this.db) {
            return;
        }
        
        return new Promise((resolve, reject) => {
            const tx = this.db.transaction([STORE_NAME], 'readwrite');
            const store = tx.objectStore(STORE_NAME);
            const request = store.clear();

            request.onsuccess = () => {
                this.notifyListeners();
                resolve();
            };

            request.onerror = () => {
                reject(request.error);
            };
        });
    }
}

// Create singleton instance
window.offlineQueue = new OfflineQueue();

export default window.offlineQueue;
