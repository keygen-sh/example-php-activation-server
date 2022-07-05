<?php

// These are required!
$product_token = getenv('KEYGEN_PRODUCT_TOKEN');
$account_id = getenv('KEYGEN_ACCOUNT_ID');
$policy_id = getenv('KEYGEN_POLICY_ID');

// This page is accessed from within the product, and is used to activate
// a single machine for a given license key. We'll be identifying each
// machine by a "fingerprint" -- which can be anything from a user's
// hash of the MAC address, to a randomly generated UUID stored in
// an easily accessible location i.e. file-system, registry, etc.
$fingerprint = $_GET['fingerprint'];

if (!isset($fingerprint)) {
  http_response_code(400);

  echo "Machine fingerprint is required for license activation";

  exit(1);
}

// First, we need to validate the provided license key within the scope
// of the provided fingerprint.
$key = $_GET['key'];

if (!isset($key)) {
  http_response_code(400);

  echo "License key is required for license activation";

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
$license = json_decode($json);

// Check if we received an error from Keygen during validation.
if (isset($license->errors)) {
  http_response_code(500);

  $messages = join(', ', array_map(function($err) { return $err->detail; }, $license->errors));

  echo "Error: $messages\n";

  exit(1);
}

// If the license is invalid, exit early, *unless* the license is only
// invalid because it does not have any machine activations yet.
if (!$license->meta->valid) {
  switch ($license->meta->code) {
    case 'FINGERPRINT_SCOPE_MISMATCH': // Allow more than 1 activation if our license isn't node-locked
    case 'NO_MACHINES':
    case 'NO_MACHINE': {
      break;
    }
    default: {
      http_response_code(422);

      echo "The license {$license->meta->detail}";

      exit(1);
    }
  }
}

// License is valid -- activate the current machine.
$url = "https://api.keygen.sh/v1/accounts/{$account_id}/machines";
$ch = curl_init($url);

$body = json_encode([
  'data' => [
    'type' => 'machines',
    'attributes' => ['fingerprint' => $fingerprint],
    'relationships' => [
      'license' => [
        'data' => ['type' => 'licenses', 'id' => $license->data->id]
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
$machine = json_decode($json);

// Check if we received an error from Keygen during activation.
if (isset($machine->errors)) {
  http_response_code(500);

  $messages = join(', ', array_map(function($err) { return $err->detail; }, $machine->errors));

  echo "Error: $messages\n";

  exit(1);
}

// All is good -- machine was successfully activated. Here, you may
// want to adjust the response below to meet your needs.
http_response_code(200);

echo "Machine {$machine->data->attributes->fingerprint} activated!";
