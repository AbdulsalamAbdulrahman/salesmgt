<x-mail::message>
# ✅ Stock Has Been Updated

Good news! Stock has been updated following a delivery.

**Order Number:** {{ $purchaseOrder->order_number }}

**Location:** {{ $purchaseOrder->location->name ?? 'N/A' }}

**Received By:** {{ $purchaseOrder->receiver->name ?? 'N/A' }}

**Delivered On:** {{ $purchaseOrder->delivered_at?->format('M d, Y \a\t g:i A') ?? 'N/A' }}

## Items Received

<x-mail::table>
| Product | Qty Delivered | Unit Cost | Total |
|:--------|:-------------:|:---------:|------:|
@foreach($purchaseOrder->items as $item)
| {{ $item->product->name ?? 'N/A' }} | {{ $item->delivered_quantity ?? $item->approved_quantity }} | ₦{{ number_format($item->unit_cost, 2) }} | ₦{{ number_format(($item->delivered_quantity ?? $item->approved_quantity) * $item->unit_cost, 2) }} |
@endforeach
</x-mail::table>

The inventory has been updated and these products are now available for sale.

<x-mail::button :url="url('/inventory')" color="success">
View Inventory
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
