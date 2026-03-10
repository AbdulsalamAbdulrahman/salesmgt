<x-mail::message>
# ⚠️ Low Stock Alert

@if($locationName)
**Location:** {{ $locationName }}
@endif

The following **{{ count($products) }} product(s)** have fallen below their minimum stock threshold and require restocking:

<x-mail::table>
| Product | SKU | Current Stock | Threshold |
|:--------|:----|:-------------:|:---------:|
@foreach($products as $product)
| **{{ $product['name'] }}** | {{ $product['sku'] ?? 'N/A' }} | {{ $product['current_stock'] }} | {{ $product['threshold'] }} |
@endforeach
</x-mail::table>

**Recommended Action:** Create a purchase order to restock these items as soon as possible to avoid stockouts.

<x-mail::button :url="url('/purchase-orders')" color="primary">
Create Purchase Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
