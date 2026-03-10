<x-mail::message>
# ✅ Purchase Order Approved

Good news! A purchase order has been approved and is ready for fulfillment.

**Order Number:** {{ $purchaseOrder->order_number }}

**Approved By:** {{ $purchaseOrder->approver->name ?? 'N/A' }}

**Approved Date:** {{ $purchaseOrder->approved_at?->format('M d, Y \a\t g:i A') ?? now()->format('M d, Y \a\t g:i A') }}

**Delivery Location:** {{ $purchaseOrder->location->name ?? 'N/A' }}

## Approved Items

<x-mail::table>
| Product | Approved Qty |
|:--------|:------------:|
@foreach($purchaseOrder->items as $item)
| {{ $item->product->name ?? 'N/A' }} | {{ $item->approved_quantity ?? $item->requested_quantity }} |
@endforeach
</x-mail::table>

@if($purchaseOrder->notes)
**Notes:** {{ $purchaseOrder->notes }}
@endif

Please prepare and ship these items to the delivery location as soon as possible.

<x-mail::button :url="url('/purchase-orders')" color="success">
View Order Details
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
