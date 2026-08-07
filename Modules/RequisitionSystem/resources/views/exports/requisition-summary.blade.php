<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Requisition {{ $requisition->number }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 24px;
        }
        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }
        h2 {
            font-size: 13px;
            margin: 22px 0 8px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 4px;
        }
        .meta {
            color: #6b7280;
            margin-bottom: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 6px 8px;
            vertical-align: top;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: 600;
        }
        .label {
            width: 28%;
            color: #4b5563;
            background: #f9fafb;
        }
        .muted {
            color: #6b7280;
        }
        .section-note {
            margin: 0 0 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Requisition {{ $requisition->number }}</h1>
    <p class="meta">Printed {{ $generatedAt->timezone(config('app.timezone'))->format('M j, Y g:i A') }}</p>

    <h2>Requisition details</h2>
    <table>
        <tr>
            <td class="label">Number</td>
            <td>{{ $requisition->number }}</td>
        </tr>
        <tr>
            <td class="label">Status</td>
            <td>{{ $requisition->status?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Stage</td>
            <td>{{ $requisition->stage?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Cost center</td>
            <td>
                {{ $requisition->costCenter?->name ?? '—' }}
                @if($requisition->costCenter?->number)
                    ({{ $requisition->costCenter->number }})
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Priority</td>
            <td>{{ ucfirst((string) $requisition->priority) }}</td>
        </tr>
        <tr>
            <td class="label">Date prepared</td>
            <td>{{ optional($requisition->date_prepared)->format('M j, Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Expected delivery</td>
            <td>{{ optional($requisition->expected_delivery_date)->format('M j, Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Currency</td>
            <td>{{ $currency?->name ?? '—' }}{{ $currency?->symbol ? ' ('.$currency->symbol.')' : '' }}</td>
        </tr>
        <tr>
            <td class="label">Total</td>
            <td>{{ number_format((float) $requisition->total, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Recurring</td>
            <td>{{ $requisition->is_recurring ? 'Yes' : 'No' }}</td>
        </tr>
        <tr>
            <td class="label">50% downpayment</td>
            <td>{{ $requisition->requires_downpayment ? 'Yes' : 'No' }}</td>
        </tr>
        <tr>
            <td class="label">Purchase order #</td>
            <td>{{ $requisition->purchase_order_number ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Description</td>
            <td>{{ $requisition->description ?: '—' }}</td>
        </tr>
        <tr>
            <td class="label">Tags</td>
            <td>
                @if($requisition->tags->isEmpty())
                    —
                @else
                    {{ $requisition->tags->pluck('name')->implode(', ') }}
                @endif
            </td>
        </tr>
        @if($requisition->quote_waiver_reason)
            <tr>
                <td class="label">Quote waiver reason</td>
                <td>{{ $requisition->quote_waiver_reason }}</td>
            </tr>
        @endif
    </table>

    <h2>Line items</h2>
    @if($requisition->items->isEmpty())
        <p class="muted">No line items.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Account</th>
                    <th>Qty</th>
                    <th>Unit cost</th>
                    <th>GST</th>
                    <th>Total</th>
                    <th>Comments</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requisition->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $item->chartOfAccount?->account_no ?? '—' }}
                            @if($item->chartOfAccount?->description)
                                — {{ $item->chartOfAccount->description }}
                            @endif
                        </td>
                        <td>{{ rtrim(rtrim(number_format((float) $item->quantity, 4, '.', ''), '0'), '.') }}</td>
                        <td>{{ number_format((float) $item->unit_cost, 2) }}</td>
                        <td>{{ $item->gst_applicable ? 'Yes' : 'No' }}</td>
                        <td>{{ number_format((float) $item->total, 2) }}</td>
                        <td>{{ $item->comments ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Supplier quotes</h2>
    <p class="section-note">Quotation PDF files are appended after this summary.</p>
    @if($requisition->suppliers->isEmpty() && $requisition->attachments->isEmpty())
        <p class="muted">No supplier quotes.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Supplier</th>
                    <th>Preferred</th>
                    <th>Quoted total</th>
                    <th>Reference</th>
                    <th>PDF</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requisition->attachments as $attachment)
                    @php
                        $supplier = $attachment->supplier
                            ?? $requisition->suppliers->firstWhere('id', $attachment->supplier_id);
                        $pivot = $supplier?->pivot;
                    @endphp
                    <tr>
                        <td>{{ $supplier?->name ?? '—' }}</td>
                        <td>{{ $pivot?->is_recommended ? 'Yes' : 'No' }}</td>
                        <td>
                            {{ $pivot?->quoted_total !== null ? number_format((float) $pivot->quoted_total, 2) : '—' }}
                        </td>
                        <td>{{ $pivot?->quote_reference_number ?: '—' }}</td>
                        <td>{{ $attachment->file_name ?: '—' }}</td>
                    </tr>
                @empty
                    @foreach($requisition->suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->pivot?->is_recommended ? 'Yes' : 'No' }}</td>
                            <td>
                                {{ $supplier->pivot?->quoted_total !== null ? number_format((float) $supplier->pivot->quoted_total, 2) : '—' }}
                            </td>
                            <td>{{ $supplier->pivot?->quote_reference_number ?: '—' }}</td>
                            <td>—</td>
                        </tr>
                    @endforeach
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>
