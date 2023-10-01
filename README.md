Generate an Atom RSS feed from Apple App Store app reviews.

It uses Apple's [App Store Connect API](https://developer.apple.com/documentation/appstoreconnectapi) to generate the feed. (It does _not_ work with apps you don't control; this is Apple's restriction.)

# Installation

1. Download [Composer](https://getcomposer.org/) if you don't have it already.
2. Run `composer install` to install the JWT library.
3. Follow Apple's [guide to creating API Keys](https://developer.apple.com/documentation/appstoreconnectapi/creating_api_keys_for_app_store_connect_api) to create an API key. When you're done, you should have three pieces of information.
  * Issuer ID, like `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
  * API Key, like `XXXXXXXXXX`
  * Private Key, starting with `----- BEGIN PRIVATE KEY-----`
4. Edit `index.php` and set the `$issuerId`, `$apiKey`, and `$privateKey` to your data.
5. View index.php in your RSS reader, with an URL parameter specifying the app ID you want to follow, e.g.:

  ```
  index.php?app_id=1363309257
  ```
