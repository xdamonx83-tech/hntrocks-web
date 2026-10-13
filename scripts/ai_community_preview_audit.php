<?php
/**
 * Audit a private AI-community preview through the SAME draft quality guard as
 * Laravel generation. Reads preview JSON, prints decision-only output.
 *
 * No Laravel bootstrap, .env, API request, user account, database or file writes.
 *
 * Usage: php scripts/ai_community_preview_audit.php /root/private-preview.json
 * IMPORTANT: Passing these narrow guards does NOT establish Hunt factual accuracy,
 * natural language, legitimacy, or permission to publish.
 */
declare(strict_types=1);

$source = $argv[1] ?? null;
if ($source === null || !is_file($source) || !is_readable($source)) {
    fwrite(STDERR, "STOP: specify a readable private preview JSON file.\n");
    exit(2);
}
$root = realpath(dirname(__DIR__));
if (!$root) {
    fwrite(STDERR, "STOP: isolated checkout not found.\n");
    exit(2);
}
$autoload = is_file($root.'/vendor/autoload.php')
    ? $root.'/vendor/autoload.php'
    : '/home/codex/hntrocks-web-current/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "STOP: Composer autoloader unavailable.\n");
    exit(2);
}
require_once $autoload;
require_once $root.'/app/Services/AiCommunityDraftQualityGuard.php';

use App\Services\AiCommunityDraftQualityGuard;
use Illuminate\Validation\ValidationException;

try {
    $data = json_decode((string) file_get_contents($source), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !isset($data['posts']) || !is_array($data['posts']) ||
        count($data['posts']) < 1 || count($data['posts']) > 30) {
        throw new UnexpectedValueException('Expected between one and thirty private preview posts.');
    }
    $seen = [];
    $recentBodies = [];
    $counts = ['accepted_by_structural_guard' => 0, 'blocked_by_structural_guard' => 0];
    $items = [];
    $guard = new AiCommunityDraftQualityGuard();
    foreach ($data['posts'] as $i => $post) {
        if (!is_array($post) || !isset($post['body'], $post['expected_ending']) ||
            !is_string($post['body']) ||
            !in_array($post['expected_ending'], ['question', 'statement'], true)) {
            throw new UnexpectedValueException("Malformed private item #".($i + 1));
        }
        $id = $post['id'] ?? sprintf('item-%02d', $i + 1);
        if (!is_string($id) || strlen($id) > 64 || isset($seen[$id])) {
            throw new UnexpectedValueException('Missing or repeated preview ID.');
        }
        $seen[$id] = true;
        try {
            $guard->validate($post['body'], $recentBodies, $post['expected_ending']);
            $counts['accepted_by_structural_guard']++;
            $items[] = ['id' => $id, 'status' => 'STRUCTURAL_PASS_ONLY'];
            $recentBodies[] = $post['body'];
        } catch (ValidationException $e) {
            $counts['blocked_by_structural_guard']++;
            $items[] = [
                'id' => $id, 'status' => 'BLOCKED',
                'reason' => (string) ($e->errors()['generation'][0] ?? 'Draft validation failed'),
            ];
        }
    }
    $report = [
        'type' => 'private_read_only_laravel_quality_guard_audit',
        'source_count' => count($data['posts']),
        'counts' => $counts, 'items' => $items,
        'note' => 'STRUCTURAL_PASS_ONLY is not human or factual approval. Never auto-publish.',
        'writes' => false,
    ];
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), "\n";
} catch (Throwable $e) {
    fwrite(STDERR, "STOP: private preview audit failed (".get_class($e).").\n");
    exit(2);
}
