<?php
function getDataBPSFull() {
    $apiKey = "816bb1db96afe0cac7af1b302e1c3050"; 
    $url = "https://webapi.bps.go.id/v1/api/list/model/subject/lang/ind/domain/0000/key/" . $apiKey;

    $response = @file_get_contents($url);
    if ($response === FALSE) return false;

    $data = json_decode($response, true);

    if (isset($data['status']) && $data['status'] == 'OK') {
        // Coba ambil dari indeks [1], jika kosong coba [0], jika tidak ada ambil ['data'] langsung
        if (!empty($data['data'][1])) return $data['data'][1];
        if (!empty($data['data'][0])) return $data['data'][0];
        if (!empty($data['data'])) return $data['data'];
    }
    
    return false;
}
?>