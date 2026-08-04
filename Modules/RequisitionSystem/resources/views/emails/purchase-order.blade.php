<x-mail::message>
# Purchase Order

Hello {{ $supplier->contact_person ?: $supplier->name }},

Please find attached purchase order
**{{ $requisition->purchase_order_number ?: $requisition->number }}**
for requisition **{{ $requisition->number }}**.

@if ($officerMessage)
{{ $officerMessage }}
@endif

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
