<?php

// These are required!
$product_token = getenv('KEYGEN_PRODUCT_TOKEN');
$account_id = getenv('KEYGEN_ACCOUNT_ID');
$policy_id = getenv('KEYGEN_POLICY_ID');

// Validate the provided license key within the scope of the user's
// current machine.
$fingerprint = $_GET['fingerprint'];
$key = $_GET['key'];

if (!isset($key)) {
  http_response_code(400);

  echo "License key is required for license validation";

  exit(1);
}

$url = "https://api.keygen.sh/v1/accounts/{$account_id}/licenses/actions/validate-key";
$ch = curl_init($url);

$body = json_encode([
  'meta' => [
    'scope' => ['fingerprint' => $fingerprint],
    'key' => $key
  ]
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Content-Type: application/json',
  'Accept: application/json'
]);


$json = curl_exec($ch);
$res = json_decode($json);

// Check if we received an error from Keygen during validation.
if (isset($res->errors)) {
  http_response_code(500);

  $messages = join(', ', array_map(function($err) { return $err->detail; }, $res->errors));

  echo "Error: $messages\n";

  exit(1);
}

if ($res->meta->valid) {
  http_response_code(200);
} else {
  http_response_code(422);
}

echo "The license {$res->meta->detail}";