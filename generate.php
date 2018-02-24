<?php

// These are required!
$product_token = getenv('KEYGEN_PRODUCT_TOKEN');
$account_id = getenv('KEYGEN_ACCOUNT_ID');
$policy_id = getenv('KEYGEN_POLICY_ID');

// Since this page should only be accessed after a successful order,
// we should verify that the passed order is valid -- this helps defend
// against somebody accessing this page directly to generate free
// license keys.
$order_id = $_GET['order'];

if (!isset($order_id)) {
  http_response_code(400);

  echo "Order ID is required for generating new licenses";

  exit(1);
}

// TODO: Verify the order actually happened, and that this request is
//       coming from our payment provider.

// Create a new license key.
$url = "https://api.keygen.sh/v1/accounts/{$account_id}/licenses";
$ch = curl_init($url);

// Generate a short license key that can be easily input by-hand.
function generate_license_key($length = 16, $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ") {
  return join('-', str_split(substr(str_shuffle($chars), 0, $length), 4));
}

$key = generate_license_key();
$body = json_encode([
  'data' => [
    'type' => 'licenses',
    'attributes' => ['key' => $key],
    'relationships' => [
      'policy' => [
        'data' => ['type' => 'policies', 'id' => $policy_id]
      ]
    ]
  ]
]);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  "Authorization: Bearer $product_token",
  'Content-Type: application/json',
  'Accept: application/json'
]);

$json = curl_exec($ch);
$res = json_decode($json);

// Check if we received an error from Keygen.
if (isset($res->errors)) {
  http_response_code(500);

  $messages = join(', ', array_map(function($err) { return $err->detail; }, $res->errors));

  echo "Error: $messages\n";

  exit(1);
}

// All is good -- license was successfully created. Here, you may
// want to adjust the response below to meet your payment provider's
// requirements, i.e. plaintext vs JSON response, status code, etc.
// You may also want to email the user a copy of their license key,
// so that they can move onto the activation step.
http_response_code(200);

echo "{$res->data->attributes->key}";

