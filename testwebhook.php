<?php
$webhook = "https://discord.com/api/webhooks/1394493201594712277/A87AePfjoUrUlRrNWM88ympX98E-oVpLzQJuuAdk1IsJF0bcPl3gYHfKjkQkxcAdfVOV";
$data = json_encode(["content" => "✅ Webhook test từ host"]);

$ch = curl_init($webhook);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP CODE: $httpCode<br>";
echo "Response: $response";
