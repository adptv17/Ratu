<?php
header("Content-Type: text/plain; charset=UTF-8");
date_default_timezone_set("UTC");

$portalVB = "https://tv.volleyballworld.com/api/client-feed?feed-url=https://zapp-5434-volleyball-tv.web.app/jw/playlists/eMqXVhhW&language=en&_data=routes/api.client-feed";
$cdnUpcoming = "https://adptv17.github.io/nissa_channel/upcoming.m3u8";

$slebeww = curl_init();
curl_setopt_array($slebeww, [
    CURLOPT_URL => $portalVB,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "User-Agent: Mozilla/5.0",
        "Accept: application/json"
    ],
    CURLOPT_TIMEOUT => 20,
]);

$tvratu = curl_exec($slebeww);

if (curl_errno($slebeww)) {
    http_response_code(500);
    exit("API ERROR");
}

curl_close($slebeww);

$decodeSakti = json_decode($tvratu, true);

if (!isset($decodeSakti['entry']) || !is_array($decodeSakti['entry'])) {
    http_response_code(500);
    exit("DATA ENTRY KOSONG");
}

$jamSekarang = new DateTime("now", new DateTimeZone("UTC"));
$listLiveGacor = [];
$listUpcomingSantuy = [];

foreach ($decodeSakti['entry'] as $itemAjaib) {

    $idMediaUnik = $itemAjaib['id'] ?? null;
    if (!$idMediaUnik) continue;

    $judulSakti = "VBTV Event";
    $deskripsiRahasia = $itemAjaib['extensions']['description'] ?? "";

    if ($deskripsiRahasia && preg_match('/Watch\s+(.*?)\s*-\s*(.*?)\s*\|/i', $deskripsiRahasia, $pecah)) {
        $judulSakti = trim($pecah[1]) . " vs " . trim($pecah[2]);
    }

    $gambarMantap = "https://via.placeholder.com/320x180?text=VBTV";
    $koleksiMedia = $itemAjaib['media_group'][0]['media_item'] ?? [];

    if (is_array($koleksiMedia)) {
        $urutanResolusi = ["1920","1280","720","640","480","320"];
        foreach ($urutanResolusi as $resolusiKeren) {
            foreach ($koleksiMedia as $subItem) {
                if (($subItem['key'] ?? "") === $resolusiKeren && !empty($subItem['src'])) {
                    $gambarMantap = $subItem['src'];
                    break 2;
                }
            }
        }
    }

    $statusLive = false;
    if (!empty($itemAjaib['extensions']['scheduled_start'])) {
        $waktuMulai = new DateTime($itemAjaib['extensions']['scheduled_start'], new DateTimeZone("UTC"));
        if ($jamSekarang >= $waktuMulai) {
            $statusLive = true;
        }
    }

    if ($statusLive) {
        $listLiveGacor[] = [
            "judul" => $judulSakti,
            "poster" => $gambarMantap,
            "mediaId" => $idMediaUnik
        ];
    } else {
        $listUpcomingSantuy[] = [
            "judul" => $judulSakti,
            "poster" => $gambarMantap
        ];
    }
}

echo "#EXTM3U\n\n";

foreach ($listLiveGacor as $siaranGila) {
    echo '#EXTINF:-1 tvg-logo="' . $siaranGila['poster'] . '" group-title="LIVE EVENT",' . $siaranGila['judul'] . "\n";
    echo "http://stream-vbtv.adptv.workers.dev/?id=" . $siaranGila['mediaId'] . "\n\n";
}

foreach ($listUpcomingSantuy as $jadwalNanti) {
    echo '#EXTINF:-1 tvg-logo="' . $jadwalNanti['poster'] . '" group-title="UPCOMING",' . $jadwalNanti['judul'] . "\n";
    echo $cdnUpcoming . "\n\n";
}
?>
