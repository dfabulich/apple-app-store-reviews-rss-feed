<?php

require_once ('vendor/autoload.php');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

const issuerId = "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx";
const apiKey = "XXXXXXXXXX";
const privateKey = "
----- BEGIN PRIVATE KEY-----
    XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
XXXXXXXX
----- END PRIVATE KEY-----
";

if (!isset($_GET['app_id'])) {
    header("HTTP/1.1 400 Bad Request");
    echo "missing app_id";
    exit;
}

$app_id = $_GET['app_id'];

$payload = [
    'iss' => $issuerId,
    'aud' => 'appstoreconnect-v1',
    'iat' => time(),
    'exp' => (time()+(60*19))
];

$payload_header = [
    "kid" => "'.$apiKey.'",
    "typ" => "JWT"
];

$token = JWT::encode($payload, $privateKey, 'ES256', $apiKey, $payload_header);

function fetch($url) {
    global $token;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Authorization: Bearer $token",
    ));
    curl_setopt($ch, CURLOPT_URL, $url);

    // Receive server response ...
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $server_output = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close ($ch);
    if ($status !== 200) {
        throw new Exception("Fetch error: " . $status . " " . $server_output);
    }
    // error_log("server_output " . $server_output);
    return json_decode($server_output, true);
}

function tryTryAgain($tries, $url) {
    for ($i = 0; $i < $tries; $i++) {
        try {
            return fetch($url);
        } catch (Exception $e) {
            if ($i < $tries) {
                //error_log('retrying ' + $i);
            } else {
                throw $e;
            }
        }
    }
}

$url = "https://api.appstoreconnect.apple.com/v1/apps/$app_id";
$result = tryTryAgain(10, $url);

function x($x) { return htmlspecialchars($x, ENT_XML1, 'UTF-8'); };

$app_title = x($result['data']['attributes']['name']);

$url = "https://api.appstoreconnect.apple.com/v1/apps/$app_id/customerReviews?sort=-createdDate";
$result = tryTryAgain(10, $url);

$data = $result['data'];

$updated = $data[0]['attributes']['createdDate'];

header('Content-Type: application/atom+xml; charset=utf-8');
header('Cache-Control: private, max-age=0');

$link = x("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");

// render literally to avoid triggering php short tags
echo '<?xml version="1.0" encoding="utf-8"?>';
?>
<feed xmlns="http://www.w3.org/2005/Atom">

  <title>iOS Reviews for <?= "$app_title (App ID $app_id)" ?></title>
  <id><?=$link?></id>
  <link rel="self" href="<?=$link?>" />
  <updated><?=$updated?></updated>

<?php
foreach ($data as $review) {
    $attributes = $review['attributes'];
    $solid_star = "&#x2605;";
	$empty_star = "&#x2606;";

    $rating = $attributes['rating'];
    $star_rating = str_repeat($solid_star, $rating) . str_repeat($empty_star, 5 - $rating);

    $title = $star_rating . ": " . x($attributes['title']);
    $author = x($attributes['reviewerNickname']);
    // The Atom feed validator recommends this for maximum compatibility
    $author = str_replace("&amp;", "&#x26;", $author);
    $author = str_replace("&lt;", "&#x3C;", $author);
    $body = x($attributes['body']);
    $content = x("by $author\n$body");
    ?>
  <entry>
    <title><?=$title?></title>
    <id>https://api.appstoreconnect.apple.com/v1/customerReviews/<?=$review['id']?></id>
    <updated><?=$attributes['createdDate']?></updated>
    <author><name><?=$author?></name></author>
    <content type="html"><?=$content?></content>
  </entry>
<?php
}

echo "</feed>";