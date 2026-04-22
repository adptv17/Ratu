<?php
header('Content-Type: text/plain; charset=utf-8');

// Set timezone ke Jakarta biar jamnya sesuai WIB
date_default_timezone_set('Asia/Jakarta');

$url = "https://api.vidio.com/sections/3787-pertandingan-hari-ini";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_ENCODING, ""); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);  
curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2); 
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Accept: application/json",
    "Accept-Language: id",
    "Content-Type: application/vnd.api+json",
    "Origin: https://m.vidio.com",
    "Referer: https://m.vidio.com/",
    "User-Agent: Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36",
    "X-API-App-Info: js/m.vidio.com",
    "X-API-Platform: web-mobile",
    "X-Secure-Level: 2",
    "X-Api-Key: CH1ZFsN4N/MIfAds1DL9mP151CNqIpWHqZGRr+LkvUyiq3FRPuP1Kt6aK+pG3nEC1FXt0ZAAJ5FKP8QU8CZ5/va/6giB8PAe2UnCHHUh2E7581O1qLD8yHHfoKBhSOnFoQz6z1+9d203IGG2agEjmOQ/E4J4r7W2VSlOwcwi1Is="
]);

$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

echo "#EXTM3U\n\n";

if (isset($data['included']) && is_array($data['included'])) {
    $now = time();

    foreach ($data['included'] as $item) {
        if ($item['type'] !== 'content') continue;

        $attr = $item['attributes'];
        $title = isset($attr['title']) ? $attr['title'] : 'No Title';
        $logo  = isset($attr['cover_url']) ? $attr['cover_url'] : '';
        $stream_url = isset($attr['stream_url']) ? $attr['stream_url'] : '';

        // Ambil ID Streaming
        $stream_id = '';
        if (isset($item['links']['self']['meta']['livestreaming_id'])) {
            $stream_id = $item['links']['self']['meta']['livestreaming_id'];
        } else {
            preg_match('/(\d+)$/', $item['id'], $matches);
            $stream_id = isset($matches[1]) ? $matches[1] : '';
        }

        if (empty($stream_id)) continue;

        // Logika Waktu dan Jam (Konversi ke WIB)
        $start_time_raw = isset($attr['start_time']) ? strtotime($attr['start_time']) : 0;
        $end_time_raw   = isset($attr['end_time']) ? strtotime($attr['end_time']) : 0;
        
        // Format Tgl dan Jam: 01/01/2016 - 20:00 WIB
        $jadwal_wib = date('d/m/Y - H:i', $start_time_raw) . " WIB";
        
        // Gabungkan Judul dengan Jadwal
        $full_title = "{$title} | {$jadwal_wib}";

        // Cek Grup (@LIVE/@UPCOMING) dan tentukan group-logo
        if ($now >= $start_time_raw && $now <= $end_time_raw) {
            $group = "@LIVE";
            $group_logo = "https://i.ibb.co.com/5hPWP8XM/20260215-235442.png";
        } else {
            $group = "@UPCOMING";
            $group_logo = "https://i.ibb.co.com/KcN6s0Y4/20260215-235315.png";
        }

        // --- LOGIKA PAKSA DASH JIKA ADA DRM ---
        if (strpos($stream_url, 'drm') !== false || strpos($stream_url, '.mpd') !== false) {
            echo "#EXTINF:-1 tvg-id=\"{$stream_id}\" tvg-logo=\"{$logo}\" group-title=\"{$group}\" group-logo=\"{$group_logo}\", {$full_title}\n";
            echo "#EXTVLCOPT:http-referrer=https://m.vidio.com\n";
            echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36\n";
            echo "#KODIPROP:inputstream.adaptive.license_type=com.widevine.alpha\n";
            echo "#KODIPROP:inputstream.adaptive.license_key=https://tvratu.my.id/vid/index.drm?id={$stream_id}&type=drm\n";
            echo "https://tvratu.my.id/vid/index.mpd?id={$stream_id}&type=dash\n\n";
        } else {
            echo "#EXTINF:-1 tvg-id=\"{$stream_id}\" tvg-logo=\"{$logo}\" group-title=\"{$group}\" group-logo=\"{$group_logo}\", {$full_title}\n";
            echo "#EXTVLCOPT:http-user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleCoreMedia/537.36 (KHTML, like Gecko) Chrome/109.0.0.0 Safari/537.36\n";
            echo "#KODIPROP:inputstreamaddon=inputstream.adaptive\n";
            echo "#KODIPROP:inputstream.adaptive.manifest_type=hls\n";
            echo "https://tvratu.my.id/vid/index.m3u8?id={$stream_id}&type=hls\n\n";
        }
    }
}
?>