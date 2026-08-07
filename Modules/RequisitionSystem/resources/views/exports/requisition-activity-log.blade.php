<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity log — Requisition {{ $requisition->number }}</title>
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
        .muted {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Activity log</h1>
    <p class="meta">Requisition {{ $requisition->number }}</p>

    <h2>History</h2>
    @if($requisition->logs->isEmpty())
        <p class="muted">No activity recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date &amp; time</th>
                    <th>Detail</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requisition->logs->sortBy('created_at') as $log)
                    @php
                        $actor = $logUsers->get($log->user_id);
                        $detailParts = array_filter([
                            $log->summary,
                            $log->comments,
                            $log->file_name ? 'Attachment: '.$log->file_name : null,
                        ]);
                    @endphp
                    <tr>
                        <td>{{ $actor?->name ?? 'Unknown user' }}</td>
                        <td>{{ optional($log->created_at)->timezone(config('app.timezone'))->format('M j, Y g:i A') ?? '—' }}</td>
                        <td>{{ $detailParts ? implode(' — ', $detailParts) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
