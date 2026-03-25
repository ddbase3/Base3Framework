<?php
// chunk_probe.php
// Plain text chunk streaming probe with fixed cadence and explicit flush.
//
// Usage:
//  - Browser: open /chunk_probe.php
//  - curl:    curl -N "http://localhost/chunk_probe.php?interval=200&count=100"

declare(strict_types=1);

$intervalMs = isset($_GET['interval']) ? max(10, (int)$_GET['interval']) : 200; // default 200ms
$count      = isset($_GET['count']) ? max(1, (int)$_GET['count']) : 200;        // default 200 chunks
$chunkBytes = isset($_GET['bytes']) ? max(0, (int)$_GET['bytes']) : 0;          // optional chunk padding

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
@set_time_limit(0);

while (ob_get_level() > 0) {
	@ob_end_flush();
}
@ob_implicit_flush(true);

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

$start = microtime(true);
$pad = ($chunkBytes > 0) ? str_repeat('x', $chunkBytes) : '';

echo "chunk_probe started; interval={$intervalMs}ms; count={$count}\n";
@flush();

for ($i = 1; $i <= $count; $i++) {
	$now = microtime(true);
	$elapsedMs = (int)round(($now - $start) * 1000);

	// One chunk per line. "ts_ms" is server-side elapsed timestamp to correlate with client receive time.
	echo "{$i}\tts_ms={$elapsedMs}\tinterval_ms={$intervalMs}\t{$pad}\n";
	@flush();

	if (connection_aborted()) {
		break;
	}

	usleep($intervalMs * 1000);
}

echo "done\n";
@flush();

