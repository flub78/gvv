<?php
/**
 * CLI script standalone : appel streaming de l'API Anthropic.
 * Écrit des lignes NDJSON dans NL_STREAM_FILE au fur et à mesure,
 * puis écrit le texte final dans NL_DRAFT_FILE.
 *
 * Variables d'environnement :
 *   NL_API_KEY      clé API Anthropic
 *   NL_MODEL        identifiant du modèle
 *   NL_CONTEXT_FILE chemin vers le fichier de contexte (system prompt)
 *   NL_PROMPT_FILE  chemin vers le fichier d'instruction (user message)
 *   NL_STREAM_FILE  chemin du fichier NDJSON de progression
 *   NL_DRAFT_FILE   chemin du fichier markdown final
 *   NL_ERROR_FILE   chemin du fichier d'erreur
 */

$api_key      = getenv('NL_API_KEY');
$model        = getenv('NL_MODEL')        ?: 'claude-sonnet-4-6';
$context_file = getenv('NL_CONTEXT_FILE') ?: '';
$prompt_file  = getenv('NL_PROMPT_FILE')  ?: '';
$stream_file  = getenv('NL_STREAM_FILE')  ?: '';
$draft_file   = getenv('NL_DRAFT_FILE')   ?: '';
$error_file   = getenv('NL_ERROR_FILE')   ?: '';

function fail($error_file, $msg) {
    if ($error_file) file_put_contents($error_file, $msg);
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

if (!$api_key)     fail($error_file, 'NL_API_KEY manquant.');
if (!file_exists($context_file)) fail($error_file, 'Fichier de contexte introuvable : ' . $context_file);
if (!file_exists($prompt_file))  fail($error_file, 'Fichier de prompt introuvable : '   . $prompt_file);

$context     = file_get_contents($context_file);
$instruction = file_get_contents($prompt_file);

$fh = fopen($stream_file, 'w');
if (!$fh) fail($error_file, 'Impossible d\'ouvrir le fichier stream : ' . $stream_file);

$full_text  = '';
$api_error  = null;
$buffer     = '';  // tampon inter-chunks pour les lignes SSE coupées

$payload = json_encode([
    'model'      => $model,
    'max_tokens' => 8192,
    'stream'     => true,
    'system'     => $context,
    'messages'   => [['role' => 'user', 'content' => $instruction]],
]);

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_POST          => true,
    CURLOPT_POSTFIELDS    => $payload,
    CURLOPT_HTTPHEADER    => [
        'x-api-key: '         . $api_key,
        'anthropic-version: 2023-06-01',
        'content-type: application/json',
        'accept: text/event-stream',
    ],
    CURLOPT_TIMEOUT       => 600,
    CURLOPT_WRITEFUNCTION => function($ch, $data) use ($fh, &$full_text, &$buffer, &$api_error) {
        $buffer .= $data;
        // Traite les lignes complètes uniquement
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line   = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            if (strpos($line, 'data: ') !== 0) continue;
            $json = substr($line, 6);
            if ($json === '[DONE]') continue;
            $obj = json_decode($json, true);
            if (!$obj || !isset($obj['type'])) continue;

            if ($obj['type'] === 'content_block_delta'
                    && isset($obj['delta']['type'])
                    && $obj['delta']['type'] === 'text_delta') {
                $delta      = $obj['delta']['text'] ?? '';
                $full_text .= $delta;
                fwrite($fh, json_encode(['type' => 'chunk', 'text' => $delta], JSON_UNESCAPED_UNICODE) . "\n");
                fflush($fh);
            } elseif ($obj['type'] === 'message_stop') {
                fwrite($fh, json_encode(['type' => 'done'], JSON_UNESCAPED_UNICODE) . "\n");
                fflush($fh);
            } elseif ($obj['type'] === 'error') {
                $api_error = $obj['error']['message'] ?? 'Erreur API inconnue.';
                fwrite($fh, json_encode(['type' => 'error', 'message' => $api_error], JSON_UNESCAPED_UNICODE) . "\n");
                fflush($fh);
            }
        }
        return strlen($data);
    },
]);

curl_exec($ch);
$http_code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);
fclose($fh);

if ($curl_error) {
    fail($error_file, 'cURL : ' . $curl_error);
}
if ($api_error) {
    fail($error_file, 'API Anthropic : ' . $api_error);
}
if ($http_code !== 200) {
    fail($error_file, 'HTTP ' . $http_code);
}
if (empty(trim($full_text))) {
    fail($error_file, 'Réponse vide (HTTP ' . $http_code . ').');
}

file_put_contents($draft_file, $full_text);
exit(0);
