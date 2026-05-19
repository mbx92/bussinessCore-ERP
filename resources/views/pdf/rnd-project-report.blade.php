<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>R&D Report - {{ $project->name }}</title>
    <style>
        @page { margin: 24px 28px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 11px; color: {{ $theme['base_content'] ?? '#111827' }}; line-height: 1.45; }
        h1, h2, h3, p { margin: 0; }
        .header { margin-bottom: 18px; }
        .label { color: {{ $theme['primary'] ?? '#1d4ed8' }}; font-weight: 800; letter-spacing: 0.12em; text-transform: uppercase; font-size: 11px; }
        .title { margin-top: 6px; font-size: 24px; font-weight: 800; color: {{ $theme['primary'] ?? '#1d4ed8' }}; }
        .muted { color: {{ $theme['muted'] ?? '#6b7280' }}; }
        .grid { width: 100%; border-collapse: separate; border-spacing: 8px; margin: 0 -8px 10px; }
        .card { background: {{ $theme['base_200'] ?? '#f3f6fb' }}; border-radius: 10px; padding: 10px 12px; }
        .card-title { font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em; color: {{ $theme['muted'] ?? '#6b7280' }}; }
        .card-value { margin-top: 4px; font-size: 16px; font-weight: 800; color: {{ $theme['base_content'] ?? '#111827' }}; }
        .section { margin-top: 16px; }
        .section h2 { font-size: 14px; font-weight: 800; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: {{ $theme['primary'] ?? '#1d4ed8' }}; color: {{ $theme['primary_content'] ?? '#fff' }}; padding: 8px; text-align: left; font-size: 10px; }
        td { padding: 7px 8px; border-bottom: 1px solid {{ $theme['base_300'] ?? '#dbe3ef' }}; vertical-align: top; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .small { font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="label">R&D Project Report</div>
        <div class="title">{{ $project->name }}</div>
        <p class="muted" style="margin-top: 6px;">Kategori: {{ $project->category }} | Status: {{ $project->status }} | PIC: {{ $project->picUser?->name ?? '-' }}</p>
        <p class="muted" style="margin-top: 2px;">Tanggal mulai: {{ $project->start_date?->format('d M Y') ?? '-' }} | Dicetak: {{ $generatedAt->format('d M Y H:i') }}</p>
    </div>

    <table class="grid">
        <tr>
            <td class="card" style="width: 33.33%;">
                <div class="card-title">Estimated Budget</div>
                <div class="card-value">Rp {{ number_format($summary['estimated_budget_total'], 0, ',', '.') }}</div>
            </td>
            <td class="card" style="width: 33.33%;">
                <div class="card-title">Actual Spend</div>
                <div class="card-value">Rp {{ number_format($summary['actual_spend_total'], 0, ',', '.') }}</div>
            </td>
            <td class="card" style="width: 33.33%;">
                <div class="card-title">Variance</div>
                <div class="card-value">Rp {{ number_format($summary['variance'], 0, ',', '.') }}</div>
            </td>
        </tr>
        <tr>
            <td class="card">
                <div class="card-title">Alat</div>
                <div class="card-value">Rp {{ number_format($summary['alat_total'], 0, ',', '.') }}</div>
            </td>
            <td class="card">
                <div class="card-title">Bahan</div>
                <div class="card-value">Rp {{ number_format($summary['bahan_total'], 0, ',', '.') }}</div>
            </td>
            <td class="card">
                <div class="card-title">HPP / Unit</div>
                <div class="card-value">Rp {{ number_format($summary['hpp_per_unit'], 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <div class="section">
        <h2>Budget Planning</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Harga Est.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->budgetItems as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format((float) $item->qty, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right">{{ number_format((float) $item->estimated_unit_price, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) $item->total_price, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="small muted">Belum ada item budget.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Product Outputs</h2>
        <table>
            <thead>
                <tr>
                    <th>Nama</th>
                    <th class="text-center">Units</th>
                    <th class="text-right">HPP / Unit</th>
                    <th class="text-right">Allocated Cost</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->productOutputs as $output)
                    <tr>
                        <td>{{ $output->name }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format((float) $output->units_produced, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right">{{ number_format((float) $summary['hpp_per_unit'], 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) $summary['hpp_per_unit'] * (float) $output->units_produced, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="small muted">Belum ada output produk.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <h2>Purchases</h2>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Item</th>
                    <th>Supplier</th>
                    <th>Kategori</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($project->purchases as $purchase)
                    <tr>
                        <td>{{ $purchase->purchase_date?->format('d M Y') }}</td>
                        <td>{{ $purchase->product?->name ?? '-' }}</td>
                        <td>{{ $purchase->supplier?->name ?? '-' }}</td>
                        <td>{{ ucfirst($purchase->category) }}</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format((float) $purchase->qty, 2, ',', '.'), '0'), ',') }}</td>
                        <td class="text-right">{{ number_format((float) $purchase->unit_price, 0, ',', '.') }}</td>
                        <td class="text-right">{{ number_format((float) $purchase->total_price, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="small muted">Belum ada pembelian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
