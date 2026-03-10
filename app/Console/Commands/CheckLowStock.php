<?php

namespace App\Console\Commands;

use App\Services\StockAlertService;
use Illuminate\Console\Command;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-low {--location= : Location ID to check (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock products and send email alerts to admins';

    /**
     * Execute the console command.
     */
    public function handle(StockAlertService $stockAlertService): int
    {
        $locationId = $this->option('location') ? (int) $this->option('location') : null;

        $this->info('Checking for low stock products...');

        $lowStockProducts = $stockAlertService->checkAndAlert($locationId);

        if (count($lowStockProducts) === 0) {
            $this->info('✓ All products are well stocked.');
            return Command::SUCCESS;
        }

        $this->warn('⚠ Found ' . count($lowStockProducts) . ' products with low stock:');

        $headers = ['Product', 'SKU', 'Current Stock', 'Threshold'];
        $rows = array_map(function ($product) {
            return [
                $product['name'],
                $product['sku'] ?? 'N/A',
                $product['current_stock'],
                $product['threshold'],
            ];
        }, $lowStockProducts);

        $this->table($headers, $rows);

        $this->info('✓ Low stock alerts have been queued for all admins.');

        return Command::SUCCESS;
    }
}
