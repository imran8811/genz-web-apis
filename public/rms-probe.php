<?php

/**
 * ONE-OFF PRODUCTION DIAGNOSTIC — DELETE THIS FILE WHEN YOU ARE DONE.
 *
 *     https://api.genzfoods.pk/rms-probe.php?key=7f3c1a9e2b
 *
 * An online order produced neither the customer email nor a row in the RMS.
 * Two independent notifications failing together points at one shared cause,
 * so this checks all of the usual ones from the host itself (there is no SSH):
 *
 *   1. Is the deployed code current?      (old CheckoutController = no notifications at all)
 *   2. Is the config cache stale?         (older than .env = new keys silently invisible)
 *   3. Can this host reach the RMS?       (secret + reachability, non-destructively)
 *   4. Can this host reach an SMTP relay? (shared hosts routinely block outbound 587/465)
 *   5. What does laravel.log actually say about the last orders?
 *
 * Nothing here writes: the RMS call sends an EMPTY body, which WebOrderController
 * rejects at validation *after* the secret check — so the status alone identifies
 * the fault and no order can be created. Secrets are printed as fingerprints only.
 */

const PROBE_KEY = '7f3c1a9e2b';

if (($_GET['key'] ?? '') !== PROBE_KEY) {
    http_response_code(404);
    exit('Not found.');
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== genz-web-apis production doctor ===\n\n";

/**
 * Find the Laravel install.
 *
 * On this hosting the app root is neither this folder nor an ancestor of it —
 * the docroot holds only the front controller and the application lives in a
 * sibling directory — so look in all three places rather than assuming any.
 */
$isRoot = fn (string $d) => is_file($d.'/vendor/autoload.php') && is_file($d.'/bootstrap/app.php');

$base = null;
$trace = [];

// (a) here, then straight up.
$dir = __DIR__;
for ($i = 0; $i < 6 && ! $base; $i++) {
    $trace[] = "up: $dir";
    if ($isRoot($dir)) {
        $base = $dir;
    }
    $parent = dirname($dir);
    if ($parent === $dir) {
        break;
    }
    $dir = $parent;
}

// (b) whatever the deployed front controller itself requires — the one place
//     that must already know where the application is.
if (! $base) {
    foreach (['/index.php', '/../index.php'] as $rel) {
        $front = __DIR__.$rel;
        if (! is_file($front)) {
            continue;
        }
        $trace[] = "front controller: $front";
        if (preg_match_all('#[\'"]([^\'"]*(?:vendor/autoload|bootstrap/app)\.php)[\'"]#', (string) file_get_contents($front), $m)) {
            foreach ($m[1] as $ref) {
                $guess = realpath(dirname($front).'/'.$ref) ?: realpath($ref);
                if (! $guess) {
                    continue;
                }
                // vendor/autoload.php and bootstrap/app.php both sit two levels
                // under the application root.
                $candidate = dirname($guess, 2);
                $trace[] = "  references $ref → $candidate";
                if ($isRoot($candidate)) {
                    $base = $candidate;
                    break 2;
                }
            }
        }
    }
}

// (c) siblings of the docroot and of the home directory.
if (! $base) {
    $searchIn = array_unique([dirname(__DIR__), dirname(__DIR__, 2), dirname(__DIR__, 3)]);
    foreach ($searchIn as $parent) {
        foreach ((array) @glob($parent.'/*', GLOB_ONLYDIR) as $candidate) {
            if ($isRoot($candidate)) {
                $base = $candidate;
                $trace[] = "sibling scan: found $candidate";
                break 2;
            }
        }
        $trace[] = "sibling scan: nothing under $parent";
    }
}

if (! $base) {
    echo "Could not locate the Laravel application root.\n\n";
    echo "Looked in:\n";
    foreach ($trace as $line) {
        echo "  $line\n";
    }
    echo "\nThis folder (".__DIR__.") contains:\n";
    foreach ((array) @scandir(__DIR__) as $entry) {
        if ($entry !== '.' && $entry !== '..') {
            echo '  '.(is_dir(__DIR__.'/'.$entry) ? '[dir] ' : '      ').$entry."\n";
        }
    }
    echo "\nUpload this file into the folder that contains `artisan`, or send me\n";
    echo "the listing above and I will point it at the right place.\n";
    exit;
}

echo "app root      : $base\n";

require $base.'/vendor/autoload.php';
$app = require $base.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$stamp = fn (?string $path) => $path && is_file($path)
    ? date('Y-m-d H:i:s', filemtime($path))
    : '(missing)';

echo 'app env       : '.config('app.env')."\n";
echo 'server time   : '.date('Y-m-d H:i:s')." (".date_default_timezone_get().")\n\n";

// ---------------------------------------------------------------------------
echo "--- 1. Is the deployed code current? ---\n";
// If the uploaded CheckoutController predates the notification feature, neither
// the email nor the RMS forward is even attempted — and nothing is logged,
// because the code that would log is not there.
$checkout = $base.'/app/Http/Controllers/Api/CheckoutController.php';
$forwarder = $base.'/app/Services/RmsOrderForwarder.php';
$checkoutSrc = is_file($checkout) ? file_get_contents($checkout) : '';
$forwarderSrc = is_file($forwarder) ? file_get_contents($forwarder) : '';

echo 'CheckoutController   : '.$stamp($checkout)."\n";
echo '  dispatchNotifications() present : '
    .(str_contains($checkoutSrc, 'dispatchNotifications') ? 'yes' : 'NO — this alone explains both failures')."\n";
echo '  sends OrderConfirmationMail     : '
    .(str_contains($checkoutSrc, 'OrderConfirmationMail') ? 'yes' : 'NO')."\n";
echo '  calls RmsOrderForwarder         : '
    .(str_contains($checkoutSrc, 'RmsOrderForwarder') ? 'yes' : 'NO')."\n";
echo 'RmsOrderForwarder    : '.$stamp($forwarder)."\n";
echo '  logs the unconfigured case      : '
    .(str_contains($forwarderSrc, 'integration not configured') ? 'yes (current build)' : 'no (older build)')."\n";
echo '  OPcache                         : '
    .(function_exists('opcache_get_status') && @opcache_get_status(false) ? 'ENABLED — may still be serving old files; flush it in cPanel' : 'off')."\n\n";

// ---------------------------------------------------------------------------
echo "--- 2. Is the config cache stale? ---\n";
// The number one cause of "changed .env, nothing happened" on this hosting.
$cache = $base.'/bootstrap/cache/config.php';
$env = $base.'/.env';
echo 'bootstrap/cache/config.php : '.$stamp($cache)."\n";
echo '.env                       : '.$stamp($env)."\n";
if (is_file($cache) && is_file($env) && filemtime($cache) < filemtime($env)) {
    echo "  ⚠ CACHE IS OLDER THAN .env — every key added since is invisible to the\n";
    echo "    app. Delete bootstrap/cache/config.php in File Manager and reload.\n";
} elseif (is_file($cache)) {
    echo "  cache is newer than .env (not obviously stale)\n";
} else {
    echo "  no config cache — .env is read live, nothing to clear\n";
}
echo "\n";

// ---------------------------------------------------------------------------
echo "--- 3. Can this host reach the RMS? ---\n";
$url = config('genz.rms.orders_url');
$secret = config('genz.rms.secret');
echo 'orders url    : '.($url ?: '(NOT SET)')."\n";
echo 'secret set    : '.($secret ? 'yes' : 'NO — this alone stops every forward')."\n";
// A fingerprint, never the secret: enough to prove both hosts hold the same
// value without putting it in a browser window or a screenshot.
echo 'secret finger : '.($secret ? substr(hash('sha256', $secret), 0, 12) : '—')
    .'  (len '.($secret ? strlen($secret) : 0).")\n";

if ($url && $secret) {
    try {
        $response = Illuminate\Support\Facades\Http::timeout(15)
            ->withHeaders(['X-Integration-Secret' => $secret])
            ->acceptJson()
            ->post($url, []); // empty body — cannot be written as an order

        echo 'HTTP status   : '.$response->status()."\n";
        echo 'body          : '.substr($response->body(), 0, 300)."\n";
        echo match (true) {
            $response->status() === 422 => "  ✓ secret OK and RMS reachable — the secret is not the problem.\n",
            $response->status() === 401 => "  ✗ RMS rejected the secret: the two .env values differ, or the RMS has\n"
                ."    none loaded (stale bootstrap/cache/config.php on the RMS host).\n",
            $response->status() === 404 => "  ✗ integration route missing on the RMS deployment.\n",
            default => "  ? unexpected — read the body above.\n",
        };
    } catch (Throwable $e) {
        echo 'EXCEPTION     : '.get_class($e).' — '.$e->getMessage()."\n";
        echo "  ✗ this host cannot reach the RMS at all (outbound HTTPS blocked?).\n";
    }
}
echo "\n";

// ---------------------------------------------------------------------------
echo "--- 4. Can this host send mail? ---\n";
$mailer = config('mail.default');
echo 'mailer        : '.$mailer."\n";
echo 'from address  : '.config('mail.from.address').' ('.config('mail.from.name').")\n";

if ($mailer === 'sendmail') {
    // Hands the message to the host's own Exim binary, sidestepping SMTP and
    // TLS entirely — which is the point, because this host intercepts outbound
    // SMTP and answers as itself no matter who you dialled.
    $path = (string) config('mail.mailers.sendmail.path');
    $binary = strtok(trim($path), ' ');
    echo 'sendmail path : '.$path."\n";
    echo 'binary        : '.($binary && is_file($binary)
        ? (is_executable($binary) ? 'present and executable' : 'present but NOT executable')
        : 'NOT FOUND — ask the host for the correct path')."\n";
} else {
    $host = (string) config("mail.mailers.$mailer.host");
    $port = (int) config("mail.mailers.$mailer.port");
    echo 'host:port     : '.$host.':'.$port."\n";
    echo 'username set  : '.(config("mail.mailers.$mailer.username") ? 'yes' : 'NO')."\n";

    // Not a reachability test — a *identity* test. These ports are open here;
    // the failure mode on this host is that they answer as s12.hosterpk.com
    // whoever you dialled, so STARTTLS fails on the certificate name.
    if ($host && $port) {
        $sock = @fsockopen($host, $port, $errno, $errstr, 8);
        if ($sock) {
            stream_set_timeout($sock, 5);
            $banner = trim((string) fgets($sock, 256));
            fclose($sock);
            echo '  banner      : '.substr($banner, 0, 70)."\n";
            $answeredAs = preg_match('/220[- ]([^\s]+)/', $banner, $m) ? $m[1] : '';
            echo '  '.($answeredAs && stripos($host, $answeredAs) === false && stripos($answeredAs, $host) === false
                ? "⚠ dialled $host but it answered as $answeredAs — outbound SMTP is\n"
                    ."    INTERCEPTED by the host. External SMTP can never authenticate;\n"
                    ."    STARTTLS will fail on the certificate name. Use MAIL_MAILER=sendmail.\n"
                : "the server identifies as the host you dialled.\n");
        } else {
            echo "  connect failed: $errno $errstr\n";
        }
    }
}

// A live send is the only real proof. Opt-in, because it puts a message in a
// real inbox: add &testmail=you@example.com to the probe URL.
$to = trim((string) ($_GET['testmail'] ?? ''));
if ($to === '') {
    echo "\n  No live send attempted. To actually prove delivery, reload with\n";
    echo "  &testmail=your@address — it sends one plain message and reports the error if any.\n";
} elseif (! filter_var($to, FILTER_VALIDATE_EMAIL)) {
    echo "\n  testmail: '".htmlspecialchars($to)."' is not a valid address.\n";
} else {
    echo "\n  Sending a test message to $to …\n";
    try {
        Illuminate\Support\Facades\Mail::raw(
            "Test from the genz-web-apis production doctor at ".date('Y-m-d H:i:s').".\n\n"
                ."If you are reading this, order confirmation emails work again.",
            fn ($m) => $m->to($to)->subject('GEN Z Foods — mail test')
        );
        echo "  ✓ handed to the transport with no error. Check the inbox (and spam).\n";
        echo "    Delivery still depends on SPF/DKIM for ".config('mail.from.address').".\n";
    } catch (Throwable $e) {
        echo '  ✗ FAILED: '.get_class($e)."\n";
        echo '    '.substr($e->getMessage(), 0, 400)."\n";
    }
}
echo "\n";

// ---------------------------------------------------------------------------
echo "--- 5. What does the log say? ---\n";
$log = $base.'/storage/logs/laravel.log';
echo 'laravel.log   : '.$stamp($log).'  '.(is_file($log) ? round(filesize($log) / 1024).'KB' : '')."\n";
echo 'writable      : '.(is_writable($base.'/storage/logs') ? 'yes' : 'NO — nothing can be logged at all!')."\n\n";

if (is_file($log)) {
    // Tail only, and only the lines that bear on this — the file can be huge.
    $size = filesize($log);
    $fh = fopen($log, 'r');
    fseek($fh, max(0, $size - 400000));
    $tail = fread($fh, 400000);
    fclose($fh);

    $hits = array_values(array_filter(
        explode("\n", $tail),
        fn ($line) => preg_match('/RMS|Forwarding|confirmation email|Mail|SMTP|Connection could not|checkout/i', $line),
    ));
    $hits = array_slice($hits, -25);

    if ($hits) {
        foreach ($hits as $line) {
            echo '  '.substr($line, 0, 300)."\n";
        }
    } else {
        echo "  No matching lines. If an order was placed since ".$stamp($log).",\n";
        echo "  the notification code never ran — see section 1.\n";
    }
}

echo "\n--- delete rms-probe.php now that you have the answer ---\n";
