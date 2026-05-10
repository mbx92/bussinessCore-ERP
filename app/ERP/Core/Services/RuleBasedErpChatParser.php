<?php

namespace App\ERP\Core\Services;

use App\Models\ErpChatParserRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class RuleBasedErpChatParser
{
    /**
     * Sinonim/alias umum untuk fuzzy matching sederhana.
     * Setiap key akan diganti dengan value sebelum dicocokkan.
     */
    private array $synonyms = [
        'invoice jatuh tempo' => 'invoice jatuh tempo',
        'tagihan jatuh tempo' => 'invoice jatuh tempo',
        'tagihan belum bayar' => 'invoice belum dibayar',
        'belum lunas'         => 'invoice belum dibayar',
        'project berjalan'    => 'project aktif',
        'project on going'    => 'project aktif',
        'project ongoing'     => 'project aktif',
        'proyek aktif'        => 'project aktif',
        'penjualan hari ini'  => 'pos hari ini',
        'sales hari ini'      => 'pos hari ini',
        'kas hari ini'        => 'cashflow hari ini',
        'keuangan hari ini'   => 'cashflow hari ini',
        'stok habis'          => 'stok rendah',
        'barang habis'        => 'stok rendah',
        'stock rendah'        => 'stok rendah',
        'pengeluaran'         => 'biaya operasional',
        'biaya bulan ini'     => 'biaya operasional',
        'kirim tagihan'       => 'kirim invoice',
        'send invoice'        => 'kirim invoice',
    ];

    public function parse(string $message, ?Collection $rules = null): array
    {
        $normalized = $this->normalize($message);
        $activeRules = ($rules ?? $this->activeRules())->sortBy('priority')->values();

        foreach ($activeRules as $rule) {
            $keywords = collect($rule->keywords)
                ->filter(fn ($kw) => is_string($kw) && trim($kw) !== '')
                ->map(fn ($kw) => Str::of($kw)->lower()->trim()->toString())
                ->values();

            if ($keywords->isEmpty()) {
                continue;
            }

            $matchMode = $rule->match_mode ?? 'and';
            $matched = $matchMode === 'or'
                ? $keywords->some(fn ($kw) => Str::contains($normalized, $kw))
                : $keywords->every(fn ($kw) => Str::contains($normalized, $kw));

            if (! $matched) {
                continue;
            }

            return [
                'matched' => true,
                'rule' => [
                    'id'            => $rule->id,
                    'name'          => $rule->name,
                    'intent_key'    => $rule->intent_key,
                    'priority'      => $rule->priority,
                    'keywords'      => $keywords->values()->all(),
                    'match_mode'    => $matchMode,
                    'response_text' => $rule->response_text,
                ],
            ];
        }

        return [
            'matched' => false,
            'rule'    => null,
        ];
    }

    public function activeRules(): Collection
    {
        return ErpChatParserRule::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderBy('id')
            ->get();
    }

    private function normalize(string $message): string
    {
        $lower = Str::of($message)->lower()->squish()->toString();

        foreach ($this->synonyms as $alias => $canonical) {
            $lower = str_replace($alias, $canonical, $lower);
        }

        return $lower;
    }
}
