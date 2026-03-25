<?php
// sse_probe.php
// Server-Sent Events probe with fixed cadence and explicit flush.
//
// Usage:
//  - Browser: open /sse_probe.php
//  - curl:    curl -N -H "Accept: text/event-stream" "http://localhost/sse_probe.php?interval=200&count=100"

declare(strict_types=1);

$intervalMs = isset($_GET['interval']) ? max(10, (int)$_GET['interval']) : 200;  // default 200ms
$count      = isset($_GET['count']) ? max(1, (int)$_GET['count']) : 200;        // default 200 events
$padBytes   = isset($_GET['pad']) ? max(0, (int)$_GET['pad']) : 0;              // optional payload padding

@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
@set_time_limit(0);

while (ob_get_level() > 0) {
	@ob_end_flush();
}
@ob_implicit_flush(true);

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Connection: keep-alive');
// If behind nginx, this header helps disable response buffering (harmless if not used).
header('X-Accel-Buffering: no');

$start = microtime(true);
$payloadPad = ($padBytes > 0) ? str_repeat('x', $padBytes) : '';

echo ": sse_probe started; interval={$intervalMs}ms; count={$count}\n\n";
@flush();

for ($i = 1; $i <= $count; $i++) {
	$now = microtime(true);
	$elapsedMs = (int)round(($now - $start) * 1000);

	echo "id: {$i}\n";
	echo "event: tick\n";
	echo "data: {$i}\tserver_ts_ms={$elapsedMs}\tinterval_ms={$intervalMs}\t{$payloadPad}\n\n";

	@flush();

	if (function_exists('fastcgi_finish_request')) {
		// Not used here; keep streaming. (Do NOT call it.)
	}

	if (connection_aborted()) {
		break;
	}

	usleep($intervalMs * 1000);
}

echo "event: done\n";
echo "data: done\n\n";
@flush();

