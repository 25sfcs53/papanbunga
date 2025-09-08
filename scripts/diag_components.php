<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Component;

$text = $argv[1] ?? "Happy Graduation SEPTIANA NUR FADILAH, S.P selamat sukses & berkah ilmunya we proud of you from adek syakilla & shafiyya Kupu-kupu Topi Toga";

echo "Diagnosing component parse for text:\n$text\n\n";

$componentsToAttach = [];
$textNorm = preg_replace('/\s+/u', ' ', trim($text));

$multiCandidates = Component::whereIn('type', [Component::TYPE_KATA_SAMBUNG, Component::TYPE_HIASAN])
    ->get()
    ->filter(function ($c) { return mb_strlen($c->name) > 1; })
    ->sortByDesc(function ($c) { return mb_strlen($c->name); });

foreach ($multiCandidates as $mc) {
    $pattern = '/(?<!\\p{L})' . preg_quote($mc->name, '/') . '(?!\\p{L})/iu';
    $count = preg_match_all($pattern, $textNorm, $m);
    if ($count) {
        $componentsToAttach[$mc->id] = ($componentsToAttach[$mc->id] ?? 0) + $count;
        echo "Matched multi '{$mc->name}' (id={$mc->id}) x{$count}\n";
        $textNorm = preg_replace($pattern, ' ', $textNorm);
    }
}

$chars = preg_split('//u', preg_replace('/\s+/u', '', $textNorm), -1, PREG_SPLIT_NO_EMPTY);
foreach ($chars as $ch) {
    if (preg_match('/^\\p{N}$/u', $ch)) {
        $type = Component::TYPE_ANGKA;
    } elseif (preg_match('/^\\p{Lu}$/u', $ch)) {
        $type = Component::TYPE_HURUF_BESAR;
    } elseif (preg_match('/^\\p{Ll}$/u', $ch)) {
        $type = Component::TYPE_HURUF_KECIL;
    } else {
        $type = Component::TYPE_SIMBOL;
    }

    $comp = Component::where('type', $type)->where('name', $ch)->first();
    if (!$comp) {
        if ($type === Component::TYPE_HURUF_BESAR) {
            $comp = Component::where('type', $type)->where('name', mb_strtoupper($ch))->first();
        } elseif ($type === Component::TYPE_HURUF_KECIL) {
            $comp = Component::where('type', $type)->where('name', mb_strtolower($ch))->first();
        }
    }

    if ($comp) {
        $componentsToAttach[$comp->id] = ($componentsToAttach[$comp->id] ?? 0) + 1;
    } else {
        echo "No component match for char '{$ch}' (type={$type})\n";
    }
}

// Print results grouped by component name
$results = [];
foreach ($componentsToAttach as $id => $qty) {
    $c = Component::find($id);
    $results[] = ['id' => $id, 'name' => $c->name, 'type' => $c->type, 'qty' => $qty, 'avail' => $c->quantity_available];
}

usort($results, fn($a,$b) => $b['qty'] <=> $a['qty']);

foreach ($results as $r) {
    echo "- {$r['name']} (id={$r['id']}, type={$r['type']}) => required={$r['qty']}, avail={$r['avail']}\n";
}

echo "\nTotal unique components: " . count($results) . "\n";
