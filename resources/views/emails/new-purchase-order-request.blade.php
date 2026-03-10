<x-mail::message>
# 📦 New Purchase Order Request

A new purchase order has been submitted and requires your approval.

**Order Number:** {{ $purchaseOrder->order_number }}

**Requested By:** {{ $purchaseOrder->requester->name ?? 'N/A' }}

**Location:** {{ $purchaseOrder->location->name ?? 'N/A' }}

**Date:** {{ $purchaseOrder->created_at->format('M d, Y \a\t g:i A') }}

## Order Items

<x-mail::table>
| Product | Requested Qty |
|:--------|:-------------:|
@foreach($purchaseOrder->items as $item)
| {{ $item->product->name ?? 'N/A' }} | {{ $item->requested_quantity }} |
@endforeach
</x-mail::table>

@if($purchaseOrder->notes)
**Notes:** {{ $purchaseOrder->notes }}
@endif

<x-mail::button :url="url('/purchase-orders')" color="primary">
Review Purchase Order
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
