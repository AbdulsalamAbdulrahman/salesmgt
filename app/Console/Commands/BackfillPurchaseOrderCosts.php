<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrderItem;
use Illuminate\Console\Command;

class BackfillPurchaseOrderCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:backfill-costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill unit_cost for purchase order items that have null values';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Backfilling unit costs for purchase order items...');

        $items = PurchaseOrderItem::whereNull('unit_cost')
            ->with('product')
            ->get();

        if ($items->isEmpty()) {
            $this->info('No items need backfilling. All items already have unit_cost set.');
            return Command::SUCCESS;
        }

        $this->info("Found {$items->count()} items to update.");

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $updated = 0;
        foreach ($items as $item) {
            if ($item->product && $item->product->cost_price) {
                $item->update(['unit_cost' => $item->product->cost_price]);
                $updated++;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated {$updated} items with product cost prices.");

        return Command::SUCCESS;
    }
}
