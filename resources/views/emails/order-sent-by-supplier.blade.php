<x-mail::message>
# 🚚 Order Shipped

A purchase order has been shipped by the supplier and is on its way.

**Order Number:** {{ $purchaseOrder->order_number }}

**Shipped By:** {{ $purchaseOrder->sender->name ?? 'Supplier' }}

**Shipped Date:** {{ $purchaseOrder->sent_at?->format('M d, Y \a\t g:i A') ?? now()->format('M d, Y \a\t g:i A') }}

**Destination:** {{ $purchaseOrder->location->name ?? 'N/A' }}

## Shipped Items

<x-mail::table>
| Product | Qty Shipped |
|:--------|:-----------:|
@foreach($purchaseOrder->items as $item)
| {{ $item->product->name ?? 'N/A' }} | {{ $item->delivered_quantity ?? $item->approved_quantity ?? $item->requested_quantity }} |
@endforeach
</x-mail::table>

Please prepare to receive this shipment and confirm delivery once the items arrive.

<x-mail::button :url="url('/purchase-orders')" color="primary">
View Order & Confirm Delivery
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
