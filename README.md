# Example PHP Activation Server
This is an example of creating a license activation server using PHP. This
server allows you to utilize Keygen 100% behind-the-scenes, so your users
will never interact with Keygen's API directly—only through this server.

> **This example application is not 100% production-ready**, but it should
> get you 90% of the way there. You may need to add additional logging,
> error handling, integration with your payment provider, delivering
> license keys via email, etc.

## Running the example

First up, configure a few environment variables:
```bash
# Keygen product token (don't share this!)
export KEYGEN_PRODUCT_TOKEN="YOUR_KEYGEN_PRODUCT_TOKEN"

# Your Keygen account ID
export KEYGEN_ACCOUNT_ID="YOUR_KEYGEN_ACCOUNT_ID"

# The Keygen policy to use when creating licenses for new users
export KEYGEN_POLICY_ID="YOUR_KEYGEN_POLICY_ID"
```

You can either run each line above within your terminal session before
starting the app, or you can add the above contents to your `~/.bashrc`
file and then run `source ~/.bashrc` after saving the file.

Next, start a local PHP server:
```
php -S localhost:3000
```

## Configuring a license policy

Visit [your dashboard](https://app.keygen.sh/policies) and create a new
policy with the following attributes:

```javascript
{
  requireFingerprintScope: true,
  maxMachines: 1,
  concurrent: false,
  floating: false,
  protected: true,
  strict: true
}
```

You can leave all other attributes to their defaults, but feel free to
modify them if needed for your particular licensing model, e.g. change
the `maxMachines` limit, set it to `floating = true`, etc.

## Creating a license key

From a web browser, access http://localhost:3000/generate.php. You will need
to provide an `order` query parameter for license creation to succeed. The
response will contain the new license key in plaintext.

For example,
```
http://localhost:3000/generate.php?order=1
```

**Note:** in production, you should verify that the order ID actually exists
and has not already been used to generate a new license key. Ideally, the
`generate.php` page should only be accessed by your payment provider after
a successful order.

## Activating a machine

From a web browser, access http://localhost:3000/activate.php. You will need
to provide a `fingerprint` query parameter, as well as a `key` query parameter
for machine activation to succeed.

For example,
```
http://localhost:3000/activate.php?fingerprint=ab:cd:ef:gh&key=PhiZ-FXkl-fnEL-sIrN
```

The `fingerprint` query parameter is what will identify an individual machine
to determine whether or not it is allowed to run your product.

**Note:** in production, you would perform this step from within your product.

## Validating a license key

Once a user's license key has been activated, they can validate it by accessing
http://localhost:3000/validate.php. You will need to provide a `fingerprint`
query parameter, as well as a `key` query parameter for license validation
to succeed. A valid license will return a HTTP status code of `200`, while
a failed validation will return `422`, along with a reason for the failure.

For example,
```
http://localhost:3000/validate.php?fingerprint=ab:cd:ef:gh&key=PhiZ-FXkl-fnEL-sIrN
```

**Note:** in production, you would perform this step from within your product.

## Questions?

Reach out at [support@keygen.sh](mailto:support@keygen.sh) if you have any
questions or concerns!
