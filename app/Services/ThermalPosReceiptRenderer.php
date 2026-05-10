<?php

namespace App\Services;

final class ThermalPosReceiptRenderer
{
    public function defaultHeaderTemplate(): string
    {
        return "{{app_name}}\n"
            ."STRUK PENJUALAN\n"
            ."No: {{transaction_number}}\n"
            ."{{date}}  {{time}}\n"
            .'Metode: {{payment_method}}';
    }

    public function defaultItemLineTemplate(): string
    {
        return "{{qty}} x {{name}}\n"
            .'{{unit_price}} / sat  |  {{line_total}}';
    }

    public function defaultFooterTemplate(): string
    {
        return "Subtotal    {{gross_total}}\n"
            ."Diskon      {{discount_total}}\n"
            ."TOTAL       {{grand_total}}\n"
            ."Dibayar     {{cash_paid}}\n"
            .'Kembali     {{change}}';
    }

    /**
     * Margin kiri dalam jumlah spasi (kolom Font A), dari mm dan lebar kertas nominal.
     */
    public static function marginCharsFromMm(float $marginMm, string $paperMm, int $cols): int
    {
        if ($marginMm <= 0) {
            return 0;
        }
        $paper = $paperMm === '58' ? 58.0 : 80.0;
        $v = (int) round($marginMm * $cols / $paper);

        return max(0, min($cols - 4, $v));
    }

    /**
     * @param  array{header?: string|null, item_line?: string|null, footer?: string|null}  $template
     * @param  array{
     *     margin_left_mm?: float|int|string|null,
     *     header_align?: string|null,
     *     item_align?: string|null,
     *     footer_align?: string|null,
     *     section_gap?: int|string|null,
     *     header_emphasis?: bool|string|null,
     * }  $layout
     * @return list<array{type: 'lines', align: 'left'|'center'|'right', lines: list<string>, double_height_first?: bool}|array{type: 'separator'}|array{type: 'spacer', count: positive-int}>
     */
    public function buildReceiptSegments(array $template, ThermalPosReceiptData $data, string $paperMm, int $cols, array $layout): array
    {
        $header = trim((string) ($template['header'] ?? '')) ?: $this->defaultHeaderTemplate();
        $itemLine = trim((string) ($template['item_line'] ?? '')) ?: $this->defaultItemLineTemplate();
        $footer = trim((string) ($template['footer'] ?? '')) ?: $this->defaultFooterTemplate();

        $headerAlign = $this->normalizeAlign($layout['header_align'] ?? 'center');
        $itemAlign = $this->normalizeAlign($layout['item_align'] ?? 'left');
        $footerAlign = $this->normalizeAlign($layout['footer_align'] ?? 'right');
        $gap = max(0, min(3, (int) ($layout['section_gap'] ?? 0)));
        $emphasis = filter_var($layout['header_emphasis'] ?? true, FILTER_VALIDATE_BOOL);

        $segments = [];

        $headerLines = $this->mapLines($this->expandMultiline($this->replaceScalars($header, $data)), $cols);
        $segments[] = [
            'type' => 'lines',
            'align' => $headerAlign,
            'lines' => $headerLines,
            'double_height_first' => $emphasis,
        ];
        $segments = array_merge($segments, $this->spacerSegments($gap));
        $segments[] = ['type' => 'separator'];
        $segments = array_merge($segments, $this->spacerSegments($gap));

        foreach ($data->lines as $row) {
            $rawLines = $this->expandMultiline($this->replaceItemLine($itemLine, $row));
            $segments[] = [
                'type' => 'lines',
                'align' => $itemAlign,
                'lines' => $this->mapLines($rawLines, $cols),
                'double_height_first' => false,
            ];
        }

        $segments = array_merge($segments, $this->spacerSegments($gap));
        $segments[] = ['type' => 'separator'];
        $segments = array_merge($segments, $this->spacerSegments($gap));

        $footerLines = $this->mapLines($this->expandMultiline($this->replaceScalars($footer, $data)), $cols);
        $segments[] = [
            'type' => 'lines',
            'align' => $footerAlign,
            'lines' => $footerLines,
            'double_height_first' => false,
        ];

        return $segments;
    }

    /**
     * @return list<array{type: 'spacer', count: positive-int}>
     */
    private function spacerSegments(int $gap): array
    {
        if ($gap <= 0) {
            return [];
        }

        return [['type' => 'spacer', 'count' => $gap]];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function mapLines(array $lines, int $cols): array
    {
        return array_values(array_map(fn (string $ln) => $this->truncateLine($ln, $cols), $lines));
    }

    /**
     * @return list<string>
     */
    public function renderPlainLines(array $template, ThermalPosReceiptData $data, int $maxCols): array
    {
        $layout = [
            'header_align' => 'left',
            'item_align' => 'left',
            'footer_align' => 'left',
            'section_gap' => 0,
            'header_emphasis' => false,
        ];
        $segments = $this->buildReceiptSegments($template, $data, '80', $maxCols, $layout);
        $out = [];
        foreach ($segments as $seg) {
            if (($seg['type'] ?? '') === 'lines') {
                foreach ($seg['lines'] as $ln) {
                    $out[] = $ln;
                }
            } elseif (($seg['type'] ?? '') === 'separator') {
                $out[] = str_repeat('-', max(8, min($maxCols, 48)));
            }
        }

        return $out;
    }

    private function normalizeAlign(?string $align): string
    {
        $a = strtolower(trim((string) $align));

        return in_array($a, ['left', 'center', 'right'], true) ? $a : 'left';
    }

    private function replaceScalars(string $text, ThermalPosReceiptData $data): string
    {
        $map = [
            '{{app_name}}' => $data->appName,
            '{{transaction_number}}' => $data->transactionNumber,
            '{{date}}' => $data->date,
            '{{time}}' => $data->time,
            '{{payment_method}}' => $data->paymentMethod,
            '{{gross_total}}' => $data->grossTotal,
            '{{discount_total}}' => $data->discountTotal,
            '{{grand_total}}' => $data->grandTotal,
            '{{cash_paid}}' => $data->cashPaid,
            '{{change}}' => $data->change,
        ];

        return strtr($text, $map);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function replaceItemLine(string $text, array $row): string
    {
        $sku = (string) ($row['sku'] ?? '');
        $name = (string) ($row['name'] ?? '');
        $qty = $row['qty'] ?? '';
        $unit = (string) ($row['unit_price'] ?? '');
        $total = (string) ($row['line_total'] ?? '');
        $disc = $row['discount_percent'] ?? '0';
        if (is_float($disc) || is_int($disc)) {
            $disc = (string) $disc;
        }

        $map = [
            '{{sku}}' => $sku,
            '{{name}}' => $name,
            '{{qty}}' => (string) $qty,
            '{{unit_price}}' => $unit,
            '{{line_total}}' => $total,
            '{{discount_percent}}' => (string) $disc,
        ];

        return strtr($text, $map);
    }

    /**
     * @return list<string>
     */
    private function expandMultiline(string $text): array
    {
        $parts = preg_split("/\r\n|\n|\r/", $text) ?: [];

        return array_values(array_map('trim', $parts));
    }

    private function truncateLine(string $line, int $maxCols): string
    {
        if (strlen($line) <= $maxCols) {
            return $line;
        }

        return substr($line, 0, max(1, $maxCols - 1)).'…';
    }
}
