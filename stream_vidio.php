<?php
header('Access-Control-Allow-Origin: *'); 
header('Content-Type: application/json');

$id = $_GET['id'] ?? null; 
$type = $_GET['type'] ?? null; 

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Stream ID is required']);
    exit;
}

function getDynamicApiKey() {
    $url = null; 
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);

    return ($response) ? trim($response) : "";
}

$jsonResponse = getDynamicApiKey();
$parsedData = json_decode($jsonResponse, true);
$dynamicApiKey = $parsedData['api_key'] ?? "";

$accounts = [
    [
        'email' => 'isi email di sini',
        'token' => 'isi token di sini'
    ],
];

$rotationTime = 180; 
$accountIndex = floor(time() / $rotationTime) % count($accounts);
$activeAccount = $accounts[$accountIndex];

$email = $activeAccount['email'];
$userToken = $activeAccount['token'];

$t = time(); 
$secretKey = "V1d10D3v:" . $t;
$signature = hash_hmac('sha256', (string)$t, $secretKey);

function generateIndoEduIP() {
    $prefixes = ['103.23', '167.205', '152.118', '202.46', '103.10', '118.98', '202.134', '180.250', '36.64', '114.4', '125.160'];
    $randPrefix = $prefixes[array_rand($prefixes)];
    return $randPrefix . "." . mt_rand(1, 255) . "." . mt_rand(1, 255);
}
$randomIP = generateIndoEduIP();

$ipPoolHeaders = [
    "X-Forwarded-For: " . $randomIP,
    "Client-IP: " . $randomIP,
    "X-Real-IP: " . $randomIP,
    "X-Client-IP: " . $randomIP,
    "X-Forwarded: " . $randomIP,
    "Forwarded-For: " . $randomIP,
    "Forwarded: " . $randomIP,
    "Via: " . $randomIP,
    "True-Client-IP: " . $randomIP,
    "X-Originating-IP: " . $randomIP,
    "X-Remote-IP: " . $randomIP,
    "X-Remote-Addr: " . $randomIP,
    "X-Proxy-User-IP: " . $randomIP,
    "CF-Connecting-IP: " . $randomIP,
    "X-Cluster-Client-IP: " . $randomIP
];

$baseHeaders = [
    "Referer: https://vidio.com/",
    "User-Agent: tv-android/2.56.10 (852)",
    "X-Api-Platform: tv-android",
    "X-Secure-Level: 2",
    "X-Api-Auth: laZOmogezono5ogekaso5oz4Mezimew1",
    "X-User-Token: " . $userToken, 
    "X-Client: " . $t,
    "X-Signature: " . $signature,
    "X-User-Email: " . $email 
];

$apiHeaders = array_merge($baseHeaders, $ipPoolHeaders);

if (!empty($dynamicApiKey)) {
    $apiHeaders[] = "X-Api-Key: " . $dynamicApiKey;
}

function fetchVidio($streamId, $headers) {
    $apiUrl = "https://api.vidio.com/livestreamings/{$streamId}/stream?initialize=true";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_ENCODING, ''); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        return ['error' => curl_error($ch), 'code' => 502];
    }
    
    curl_close($ch);
    return ['body' => $response, 'code' => $httpCode];
}

$result = fetchVidio($id, $apiHeaders);
$data = json_decode($result['body'], true);

if ($result['code'] === 403 && isset($data['errors'][0]['meta']['blocking_banner']['url'])) {
    $redirectUrl = $data['errors'][0]['meta']['blocking_banner']['url'];
    if (preg_match('/\/(?:live|watch)\/(\d+)/', $redirectUrl, $matches)) {
        $newId = $matches[1];
        $result = fetchVidio($newId, $apiHeaders);
        $data = json_decode($result['body'], true);
    }
}

$httpCode = $result['code'];

if ($httpCode === 200 && isset($data['data']) && $type) {
    $attributes = $data['data']['attributes'] ?? null;
    
    if ($attributes) {
        switch ($type) {
            case 'hls':
                $url = $attributes['m3u8'] ?? $attributes['hls'] ?? null;
                if ($url) {
                    header("Location: " . $url, true, 302);
                    exit;
                }
                break;
                
            case 'mpd':
            case 'dash': 
                if (isset($attributes['dash'])) {
                    header("Location: " . $attributes['dash'], true, 302);
                    exit;
                }
                break;
                
            case 'drm':
                $licenseUrl = $attributes['license_servers']['drm_license_url'] ?? null;
                $widevineData = $attributes['custom_data']['widevine'] ?? null;
                
                if ($licenseUrl && $widevineData) {
                    header("Location: " . $licenseUrl . '?pallycon-customdata-v2=' . urlencode($widevineData), true, 307);
                    exit;
                }
                break;
        }
    }
}

http_response_code($httpCode);
echo isset($result['body']) ? $result['body'] : json_encode($data);
?>