addEventListener("fetch", event => {
  event.respondWith(handleRequest(event.request))
})

async function handleRequest(request) {
  try {
    const url = new URL(request.url);
    
    // Konfigurasi Target DRMToday
    const targetHost = "lic.drmtoday.com";
    const targetPath = "/license-proxy-widevine/cenc/";
    const targetSearch = "?specConform=true";
    const targetURL = `https://${targetHost}${targetPath}${targetSearch}`;
    
    // Fungsi untuk menambah header CORS agar Player tidak memblokir response
    function addHeaders(response) {
      response.headers.set("Access-Control-Allow-Origin", "*");
      response.headers.set("Access-Control-Allow-Credentials", "true");
      response.headers.set("Access-Control-Allow-Methods", "GET,HEAD,OPTIONS,POST,PUT");
      response.headers.set("Access-Control-Allow-Headers", "Origin, X-Requested-With, Content-Type, Accept, Authorization, dt-custom-data");
    }
    
    // Handle request OPTIONS (Preflight)
    if (request.method === "OPTIONS") {
      const res = new Response(null, { status: 204 });
      addHeaders(res);
      return res;
    }

    // 1. Ambil Token Base64 dari link PHP kamu
    const phpUrl = "http://all.diskon.cloud/cubmu.php"; 
    const externalResponse = await fetch(phpUrl);
    const dataToken = await externalResponse.text();
    
    // 2. Siapkan Header untuk ke DRMToday
    let newHeaders = new Headers(request.headers);
    // Masukkan token hasil PHP ke header 'dt-custom-data'
    newHeaders.set('dt-custom-data', dataToken.trim()); 
    newHeaders.set('Host', targetHost);

    // 3. Request Lisensi ke DRMToday
    const drmResponse = await fetch(targetURL, {
        method: request.method,
        headers: newHeaders,
        body: request.body
    });

    // 4. Buat response baru untuk dikirim balik ke Player
    const finalResponse = new Response(drmResponse.body, {
      status: drmResponse.status,
      statusText: drmResponse.statusText,
      headers: drmResponse.headers
    });
    
    addHeaders(finalResponse);
    
    return finalResponse;

  } catch (e) {
    return new Response(e.message || e, { status: 500 });
  }
}