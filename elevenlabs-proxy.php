<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['text']) || !isset($data['voiceId']) || !isset($data['apiKey'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$text = $data['text'];
$voiceId = $data['voiceId'];
$apiKey = $data['apiKey'];

// Make request to ElevenLabs
$url = "https://api.elevenlabs.io/v1/text-to-speech/{$voiceId}";

$postData = json_encode([
    'text' => $text,
    'model_id' => 'eleven_monolingual_v1',
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.75
    ]
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: audio/mpeg',
    'Content-Type: application/json',
    'xi-api-key: ' . $apiKey
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'ElevenLabs API error', 'code' => $httpCode]);
    exit;
}

// Return the audio data as base64
header('Content-Type: application/json');
echo json_encode([
    'audio' => base64_encode($response)
]);
?>
