<?php
$email = "guest255@moratelindo.com";

if (!empty($email)) {
    // Gunakan http_build_query agar format data benar
    $data = http_build_query(array(
        "email" => $email,
        "password" => "Z3Vlc3QyNTU=",
        "deviceId" => "1234567890",
        "deviceType" => "A",
        "deviceModel" => "A21",
        "deviceToken" => "",
        "serial" => "",
        "platformId" => "4028c685635a0c6301635a117a6e0002"
    ));

    $url = "http://apiserver.transvision.co.id/api/account_external/login";

    $headers = array(
        "Origin: https://www.cubmu.com",
        "Referer: https://www.cubmu.com/",
        "User-Agent: Mozilla/5.0 (Linux; Android 10; RMX2030) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Mobile Safari/537.36",
        "Content-Type: application/x-www-form-urlencoded"
    );

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Tambah timeout biar gak nunggu kelamaan
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch); // Ambil pesan error detail

    if ($httpCode == 200) {
        $data_json = json_decode($response, true);
        if (isset($data_json['access_token'])) {
            $formatted_response = array(
                "userId" => $email,
                "sessionId" => $data_json['access_token'],
                "merchant" => "giitd_transvision"
            );
            echo base64_encode(json_encode($formatted_response));
        } else {
            echo "Token tidak ditemukan di JSON.";
        }
    } else {
        // Jika 0, tampilkan error cURL-nya apa
        echo "Gagal: HTTP " . $httpCode . " | Error: " . $curlError;
    }

    curl_close($ch);
} else {
    echo "Email kosong.";
}
?>