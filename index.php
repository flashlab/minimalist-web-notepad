<?php
require_once( dirname(__FILE__).'/vendor/autoload.php' );
use Spatie\UrlSigner\Sha256UrlSigner;

require_once( dirname( dirname(__FILE__) ).'/u/includes/load-yourls.php' );

// Base URL of the website, without trailing slash.
$base_url = "https://" . strtolower($_SERVER["HTTP_HOST"]) . (preg_match('/^\/n\//', $_SERVER["REQUEST_URI"]) ?  "/n" : "");

// Path to the directory to save the notes in, without trailing slash.
// Should be outside the document root, if possible.
$save_path = '_tmp';

// Disable caching.
header('Cache-Control: no-store');

// If no note name is provided, or if the name is too long, or if it contains invalid characters.
if (!isset($_GET['note']) || strlen($_GET['note']) > 64 || !preg_match('/^[a-zA-Z0-9_-]+$/', $_GET['note'])) {
    yourls_maybe_require_auth();
    // Generate a name with 5 random unambiguous characters. Redirect to it.
    header("Location: $base_url/" . substr(str_shuffle('234579abcdefghjkmnpqrstwxyz'), -5));
    die;
}

$path = $save_path . '/' . $_GET['note'];

// generate or validate signature.
if (isset($_GET['signature'])) {
    $urlSigner = new Sha256UrlSigner('FHUARG5895FTGFDAGNUFDB');
    $expirationDate = (new DateTime())->modify('+1 day');
    if ($_GET['signature'] === '1') {
        yourls_maybe_require_auth();
        if (isset($_GET['expires']) && strlen($_GET['expires']) < 4 && preg_match('/^[0-9]+$/', $_GET['expires'])) {
            $expirationDate = (new DateTime())->modify('+'.$_GET['expires'].' day');
        }
        header("Location: $base_url/" . preg_replace('/^\?note=(.*?)&/' , '$1?', $urlSigner->sign('?'.$_SERVER['QUERY_STRING'], $expirationDate)) . "#md");
        die;
    } else {
        if (!$urlSigner->validate('?' . $_SERVER['QUERY_STRING'])) {
            yourls_maybe_require_auth();
        }
    }
} else {
    yourls_maybe_require_auth();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST')  {
    if (isset($_POST['readonly'])) {
        header('HTTP/1.0 403 Forbidden');
        die;
    }
    if (isset($_POST['append'])) {
        // append file.
        file_put_contents($path, $_POST['append'].PHP_EOL, FILE_APPEND | LOCK_EX);
    } else {
        $text = isset($_POST['text']) ? $_POST['text'] : file_get_contents("php://input");
        // If provided input is empty, delete file.
        if (!strlen($text)) {
            unlink($path);
        } else {
            // Update file.
            file_put_contents($path, $text, LOCK_EX);
        }
    }
    die;
}

// Print raw file when explicitly requested, or if the client is curl or wget.
if (isset($_GET['raw']) || (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/^(curl|wget)/i', $_SERVER['HTTP_USER_AGENT']))) {
    if (is_file($path)) {
        header('Content-type: text/plain');
        readfile($path);
    } else {
        header('HTTP/1.0 404 Not Found');
    }
    die;
}
?><!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta content="IE=edge" http-equiv="X-UA-Compatible">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php print $_GET['note']; ?></title>
    <link rel="icon" href="<?php print $base_url; ?>/favicon.ico" sizes="any">
    <link rel="icon" href="<?php print $base_url; ?>/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?php print $base_url; ?>/styles.css?v=1">
</head>
<body>
    <div class="container"><?php if ( $_GET['note'] === 'dir' ) { ?> 
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Size</th>
                    <th>Modify Time</th>
                </tr>
            </thead>
            <tbody><?php
        $ignored = array('.', '..', '.htaccess');
        $files = array();
        function human_filesize($bytes, $dec = 2): string {
            $size   = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
            $factor = floor((strlen($bytes) - 1) / 3);
            if ($factor == 0) $dec = 0;
            return sprintf("%.{$dec}f %s", $bytes / (1024 ** $factor), $size[$factor]);
        }
        foreach (scandir($save_path) as $file) {
            if (in_array($file, $ignored)) continue;
            $files[$file] = filemtime($save_path . '/' . $file);
        }
        arsort($files);
        foreach($files as $name => $time) {
            print '<tr><td>' . '<a href="./' . $name . '">' . $name .
                  '</a></td><td>' . human_filesize(filesize($save_path . '/' . $name)).
                  '</td><td>' . date('Y-m-d H:i:s', $time) . '</td></tr>';
        } ?>
            </tbody>
        </table></div><?php } else { ?>
        <textarea id="content"><?php
            //print $_GET['note'].'?'.$_SERVER['QUERY_STRING'];
            if (is_file($path)) {
                print htmlspecialchars(file_get_contents($path), ENT_QUOTES, 'UTF-8');
            }
        ?></textarea>
        <div id="printable" class="hide"></div>
    </div>
    <script src="//cdn.jsdelivr.net/npm/markdown-it@14.0.0/dist/markdown-it.min.js"></script>
    <script src="<?php print $base_url; ?>/script.js?v=1.1"></script>
    <?php } ?>
</body>
</html>
