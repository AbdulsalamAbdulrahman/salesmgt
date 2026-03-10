<?php

namespace App\Services;

use App\Mail\LowStockAlert;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class StockAlertService
{
    /**
     * Check for low stock products and send alerts to admins
     *
     * @param int|null $locationId Specific location to check, null for all locations
     * @return array Products that are low on stock
     */
    public function checkAndAlert(?int $locationId = null): array
    {
        $lowStockProducts = $this->getLowStockProducts($locationId);
        
        if (count($lowStockProducts) > 0) {
            $this->sendAlertToAdmins($lowStockProducts, $locationId);
        }
        
        return $lowStockProducts;
    }

    /**
     * Get products that are below their low stock threshold
     *
     * @param int|null $locationId
     * @return array
     */
    public function getLowStockProducts(?int $locationId = null): array
    {
        $query = Product::where('is_active', true)
            ->whereNotNull('low_stock_threshold')
            ->where('low_stock_threshold', '>', 0);

        if ($locationId) {
            // Check stock for specific location
            $query->withSum(['inventoryStocks' => function ($q) use ($locationId) {
                $q->where('location_id', $locationId);
            }], 'quantity');
        } else {
            // Check total stock across all locations
            $query->withSum('inventoryStocks', 'quantity');
        }

        $products = $query->get();

        $lowStockProducts = [];
        foreach ($products as $product) {
            $currentStock = $product->inventory_stocks_sum_quantity ?? 0;
            
            if ($currentStock <= $product->low_stock_threshold) {
                $lowStockProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => $currentStock,
                    'threshold' => $product->low_stock_threshold,
                ];
            }
        }

        return $lowStockProducts;
    }

    /**
     * Send low stock alert email to all admins
     *
     * @param array $products
     * @param int|null $locationId
     * @return void
     */
    public function sendAlertToAdmins(array $products, ?int $locationId = null): void
    {
        $locationName = null;
        if ($locationId) {
            $location = \App\Models\Location::find($locationId);
            $locationName = $location?->name;
        }

        $admins = User::where('role', 'admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)->queue(new LowStockAlert($products, $locationName));
        }
    }

    /**
     * Check if a specific product is low on stock
     *
     * @param int $productId
     * @param int|null $locationId
     * @return bool
     */
    public function isProductLowStock(int $productId, ?int $locationId = null): bool
    {
        $product = Product::find($productId);
        
        if (!$product || !$product->low_stock_threshold) {
            return false;
        }

        if ($locationId) {
            $stock = InventoryStock::where('product_id', $productId)
                ->where('location_id', $locationId)
                ->sum('quantity');
        } else {
            $stock = InventoryStock::where('product_id', $productId)->sum('quantity');
        }

        return $stock <= $product->low_stock_threshold;
    }
}
