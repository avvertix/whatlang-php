<?php

declare(strict_types=1);

/**
 * Regenerates tests/fixtures/reference.json by running a corpus through the
 * Rust implementation, so the PHP port can be diffed against it.
 *
 * Needs cargo and a checkout of whatlang-rs.
 *
 * Usage: php tools/generate-fixture.php [path-to-whatlang-rs]
 */
$repo = dirname(__DIR__);
$rust = $argv[1] ?? $repo.'/../whatlang-rs';
$harness = $repo.'/tools/xcheck';

if (! is_file($rust.'/tests/examples.json')) {
    fwrite(STDERR, "Cannot find whatlang-rs sources at {$rust}\n");
    exit(1);
}

// The committed Cargo.toml already points at a sibling whatlang-rs checkout.
// Only rewrite it when the caller asked for a different one, so a plain run
// leaves no machine-specific path behind. TOML reads backslashes as escapes,
// and cargo is happy with forward slashes on Windows.
if (isset($argv[1])) {
    $path = str_replace('\\', '/', realpath($rust));

    file_put_contents($harness.'/Cargo.toml', <<<TOML
    [package]
    name = "xcheck"
    version = "0.1.0"
    edition = "2021"

    [dependencies]
    whatlang = { path = "{$path}" }

    TOML);
}

echo "building rust harness...\n";
exec(sprintf('cargo build --release --manifest-path %s 2>&1', escapeshellarg($harness.'/Cargo.toml')), $build, $status);

if ($status !== 0) {
    fwrite(STDERR, implode("\n", $build)."\n");
    exit(1);
}

$examples = json_decode(file_get_contents($rust.'/tests/examples.json'), true, flags: JSON_THROW_ON_ERROR);

$corpus = [];

foreach ($examples as $text) {
    $corpus[] = $text;

    // Truncations, to exercise the short-text path where the alphabet score
    // carries most of the weight.
    foreach ([3, 8, 20, 60, 150] as $length) {
        $slice = trim(mb_substr($text, 0, $length));

        if ($slice !== '') {
            $corpus[] = $slice;
        }
    }

    // Individual words.
    foreach (array_slice(preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY), 0, 6) as $word) {
        $corpus[] = $word;
    }
}

// Degenerate and mixed-script inputs.
$corpus = array_merge($corpus, [
    ' ', '1234567890-,;!', '...', 'a', 'ab', 'abc', 'ß', 'ё',
    'Привет! Текст на русском with some English.',
    'Russian word любовь means love.',
    '水', '日本', 'この間、川越城や松井田城などの諸城を拡張・改修',
    'カタカナ', 'ひらがな', '한국어 텍스트입니다',
    'Can you tell me where is Schönheitstraße?',
    'Façade', 'I am begging pardon', 'Mi ne scias!',
    'Die Ordnung muss für immer in diesem Codebase bleiben',
    'Дуже цікаво', '🎉🎉🎉', 'ABC123!@#',
]);

$corpus = array_values(array_unique(array_filter($corpus, static fn (string $t): bool => $t !== '')));

// The harness reads one hex-encoded UTF-8 text per line.
$input = $harness.'/corpus.hex';
file_put_contents($input, implode("\n", array_map('bin2hex', $corpus))."\n");

$exe = $harness.'/target/release/xcheck'.(PHP_OS_FAMILY === 'Windows' ? '.exe' : '');
exec(sprintf('%s < %s', escapeshellarg($exe), escapeshellarg($input)), $out, $status);

unlink($input);

if ($status !== 0 || count($out) !== count($corpus)) {
    fwrite(STDERR, "harness failed (status {$status}, ".count($out).' lines for '.count($corpus)." inputs)\n");
    exit(1);
}

$records = [];

foreach ($corpus as $i => $text) {
    [$lang, $script, $confidence] = explode("\t", $out[$i]);

    $records[] = [
        'text' => $text,
        'lang' => $lang === '-' ? null : $lang,
        'script' => $script === '-' ? null : $script,
        'confidence' => round((float) $confidence, 12),
    ];
}

$json = json_encode($records, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

@mkdir($repo.'/tests/fixtures', 0777, true);
file_put_contents($repo.'/tests/fixtures/reference.json', $json."\n");

printf("wrote %d records, %.1f KB\n", count($records), strlen($json) / 1024);
