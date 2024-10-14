<!DOCTYPE html>
<?php
$APP_NAME = 'HyperTextMetaLister';
$APP_PATH = "./{$APP_NAME}.php";
?>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $APP_NAME; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            flex-direction: column;
        }
        .container {
            background-color: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: auto;
            min-width: 400px;
            /* max-width: 100%; */
            margin-bottom: 40px;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        input[type="url"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        input[type="submit"] {
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        table {
            table-layout: fixed;
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
            word-break: break-word;
        }
        th {
            background-color: #f4f4f4;
        }

/* Ensure proper box-sizing for all elements */
* {
    box-sizing: border-box;
}

/* Container styling for overall layout */
.container {
    /* max-width: 1200px; */ /* Limit max width for larger screens */
    margin: 0 auto; /* Center the container */
    padding: 20px;
}

/* Meta table styling */
.meta-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 16px;
    text-align: left;
}

.meta-table th, .meta-table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
}

.meta-table th {
    background-color: #f2f2f2;
}

.meta-icon {
    margin-right: 8px;
}

/* Title styling with hover effect for description */
.meta-title {
    position: relative;
    cursor: pointer;
}

.meta-title > .title {
    font-weight: bold;
}

.meta-title:hover::after {
    content: attr(title);
    position: absolute;
    background-color: #f9f9f9;
    border: 1px solid #ccc;
    padding: 5px;
    border-radius: 4px;
    left: 0;
    top: 100%;
    white-space: nowrap;
    z-index: 1;
    font-size: 14px;
    color: #333;
}

/* Styles for both meta and link tables */
.meta-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 16px;
    text-align: left;
}

.meta-table th, .meta-table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
}

.meta-table th {
    background-color: #f2f2f2;
}

.meta-table a {
    color: #1a0dab;
    text-decoration: none;
}

.meta-table a:hover {
    text-decoration: underline;
}

/* Responsive design for larger screens */
@media (min-width: 1024px) {
    .container {
        padding: 20px;
    }

    .preview-cards {
        display: grid;
        grid-template-columns: 1fr; /*repeat(auto-fit, minmax(300px, 1fr));*/
        gap: 20px;
        max-width: 100vw;
    }
}

/* Responsive design for smaller screens */
@media (max-width: 1024px) {
    .container {
        padding: 10px;
    }

    .preview-cards {
        grid-template-columns: 1fr; /* Stacks cards vertically on small screens */
    }

    .preview-card {
        flex-direction: column; /* Align content vertically on smaller screens */
        padding: 15px;
    }

    .preview-card img, .preview-card video {
        width: 100%;
        height: auto; /* Allow media to scale with the card */
        margin-bottom: 10px;
    }
}

/* Preview card style */
.preview-card {
    display: flex;
    align-items: flex-start;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 10px;
    background-color: #fafafa;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
    cursor: pointer;
    width: 100%;
}

.preview-card img, .preview-card video {
    width: 100px;
    height: 100px;
    margin-right: 20px;
    border-radius: 10px;
    object-fit: cover;
}

/* Content area of the card */
.preview-card-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.platform {
    font-size: 14px;
    font-weight: bold;
    margin-bottom: 5px;
}

.preview-title {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}

.preview-description {
    font-size: 14px;
    color: #555;
    margin-top: 10px;
}

/* Hover effect for cards */
.preview-card:hover {
    transform: scale(1.03);
}
    </style>
</head>
<body>
    <div class="container">
        <h2><a style="color: initial; text-decoration: none;" href="<?php echo $APP_PATH; ?>"><?php echo $APP_NAME; ?></a></h2>

        <form method="GET">
            <input type="url" name="url" placeholder="Enter URL" required="required" value="<?php echo $_GET['url']; ?>">
            <input type="submit" value="Get Meta Details">
        </form>

        <?php
function fetch_page($url, $maxRedirects = 5) {
    $currentUrl = $url;
    $redirectCount = 0;

    while ($redirectCount < $maxRedirects) {
        // Initialize cURL to fetch the content
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $currentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false); // Disable auto-following redirects by cURL
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the response
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout after 30 seconds
        curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirects); // Set a limit for redirects

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Ensure that non-ASCII characters are correctly interpreted as UTF-8
        $body = mb_convert_encoding($body, 'HTML-ENTITIES', 'UTF-8');

        // Get the final URL after HTTP redirection
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        curl_close($ch);

        // Check for HTTP redirect (status codes 3xx)
        if (preg_match('/^HTTP\/\d\.\d\s+3\d{2}/', $headers)) {
            // Parse 'Location' header for the new URL
            if (preg_match('/Location:\s*(.*?)\s*$/mi', $headers, $matches)) {
                $newUrl = trim($matches[1]);

                // Resolve relative URLs
                if (!filter_var($newUrl, FILTER_VALIDATE_URL)) {
                    $newUrl = rtrim($currentUrl, '/') . '/' . ltrim($newUrl, '/');
                }

                $currentUrl = $newUrl;
                $redirectCount++;
                continue; // Follow the HTTP redirect
            }
        }

        // Load HTML content into DOMDocument to check for meta refresh
        $doc = new DOMDocument();
        libxml_use_internal_errors(true); // Ignore HTML warnings
        $doc->loadHTML($body);
        libxml_clear_errors();

        // Check for meta refresh redirects in HTML
        $metaTags = $doc->getElementsByTagName('meta');
        foreach ($metaTags as $meta) {
            if (strtolower($meta->getAttribute('http-equiv')) === 'refresh') {
                $content = $meta->getAttribute('content');
                if (preg_match('/\d+;\s*url=(.*)/i', $content, $matches)) {
                    $metaRedirectUrl = trim($matches[1], '"');

                    // Resolve relative URLs for meta refresh
                    if (!filter_var($metaRedirectUrl, FILTER_VALIDATE_URL)) {
                        $metaRedirectUrl = rtrim($currentUrl, '/') . '/' . ltrim($metaRedirectUrl, '/');
                    }

                    $currentUrl = $metaRedirectUrl;
                    $redirectCount++;
                    continue 2; // Follow the meta redirect
                }
            }
        }

        // If no more redirects, return the final URL and content
        return ['url' => $currentUrl, 'content' => $body, 'error' => $error];
    }

    // If max redirects reached, return an error
    return ['url' => $currentUrl, 'content' => null, 'error' => 'Too many redirects'];
}

function extractLinkTags($html) {
    $doc = new DOMDocument();
    @$doc->loadHTML($html); // Suppress warnings due to malformed HTML

    $linkTags = [];
    foreach ($doc->getElementsByTagName('link') as $link) {
        $rel = $link->getAttribute('rel') ?: $link->getAttribute('itemprop');
        $href = $link->getAttribute('href') ?: $link->getAttribute('content');
        
        if ($rel && $href) {
            if (!array_key_exists($rel, $linkTags)) {
                $linkTags[$rel] = $href;
            } elseif (!in_array($content, explode('<br />', $metaData[$name]))) {
                $linkTags[$rel] .= '<br />' . $href;
            }
        }
    }

    return $linkTags;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['url'])) {
    // Get the submitted URL
    $url = filter_input(INPUT_GET, 'url', FILTER_VALIDATE_URL);

    if (!$url) {
        echo "<p style='color: red;'>Invalid URL. Please enter a valid URL.</p>";
    } else {
        $url_tokens = parse_url($url);
        if ($url_tokens['scheme'] !== 'https' || filter_var($url_tokens['host'], FILTER_VALIDATE_IP)) {
            echo "<p style='color: red;'>Invalid URL. Please enter a valid URL.</p>";
        } else {
        
        // Fetch the page and follow redirects
        $result = fetch_page($url);
        $finalUrl = $result['url'];
        $htmlContent = $result['content'];
        $error = $result['error'];

        if ($htmlContent === null) {
            echo "<p style='color: red;'>Error: $error</p>";
        } else {
            // Notify the user of the final URL after following redirects
            if ($url !== $finalUrl) {
                $finalUrlEncoded = urlencode($finalUrl);
                echo "<p style='color: orange;'>Note: The request was redirected to <strong><a href='{$APP_PATH}?url={$finalUrlEncoded}'>{$finalUrl}</a></strong>.</p>";
            }

            // Load HTML content into DOMDocument
            $doc = new DOMDocument();
            libxml_use_internal_errors(true); // Ignore HTML warnings
            $doc->loadHTML($htmlContent);
            libxml_clear_errors();

                    // Look for <meta http-equiv="refresh"> tag
                    $metaTags = $doc->getElementsByTagName('meta');
                    $metaData = [];

                    foreach ($metaTags as $meta) {
                        $name = $meta->getAttribute('name') ?: $meta->getAttribute('property') ?: $meta->getAttribute('itemprop');
                        $content = $meta->getAttribute('content');
                        if ($name && $content) {
                            if (!array_key_exists($name, $metaData)) {
                                $metaData[$name] = $content;
                            } elseif (!in_array($content, explode('<br />', $metaData[$name]))) {
                                //$metaData["item-{$name}"] = $content;
                                $metaData[$name] .= '<br />' . $content;
                            }
                        }
                    }

                    if ($metaData) {
// Define names, emoji icons, and descriptions for common meta tags
$metaFriendlyNames = [
    // HTML Standard Meta Tags
    'title' => ['name' => 'Page Title', 'icon' => '📄', 'description' => 'The title of the webpage, shown on the browser tab.'],
    'description' => ['name' => 'Description', 'icon' => '📝', 'description' => 'A short summary of the page content.'],
    'keywords' => ['name' => 'Keywords', 'icon' => '🔑', 'description' => 'A list of keywords relevant to the page, used for SEO.'],
    'author' => ['name' => 'Author', 'icon' => '✍️', 'description' => 'The author of the webpage.'],
    'robots' => ['name' => 'Robots', 'icon' => '🤖', 'description' => 'Instructions for search engines on whether to index the page.'],
    'viewport' => ['name' => 'Viewport', 'icon' => '📱', 'description' => 'Defines the visible area of the webpage on different devices.'],
    'charset' => ['name' => 'Charset', 'icon' => '🔠', 'description' => 'Character encoding used by the page.'],

    // Open Graph Meta Tags
    'og:title' => ['name' => 'OG Title', 'icon' => '📄', 'description' => 'The title of the page when shared on social platforms.'],
    'og:description' => ['name' => 'OG Description', 'icon' => '📝', 'description' => 'A brief description for social sharing.'],
    'og:image' => ['name' => 'OG Image', 'icon' => '🖼️', 'description' => 'The image displayed when the page is shared.'],
    'og:url' => ['name' => 'OG URL', 'icon' => '🔗', 'description' => 'Canonical URL of the page being shared.'],
    'og:type' => ['name' => 'OG Type', 'icon' => '🔗', 'description' => 'The type of content (e.g., article, website, video).'],
    'og:site_name' => ['name' => 'OG Site Name', 'icon' => '🏠', 'description' => 'The name of the website.'],
    'og:locale' => ['name' => 'OG Locale', 'icon' => '🌍', 'description' => 'The locale of the page (e.g., en_US).'],

    // Twitter Meta Tags
    'twitter:title' => ['name' => 'Twitter Title', 'icon' => '🐦', 'description' => 'The title of the page when shared on Twitter.'],
    'twitter:description' => ['name' => 'Twitter Description', 'icon' => '🐦📝', 'description' => 'A brief description for Twitter sharing.'],
    'twitter:image' => ['name' => 'Twitter Image', 'icon' => '🐦🖼️', 'description' => 'The image displayed when the page is shared on Twitter.'],
    'twitter:card' => ['name' => 'Twitter Card', 'icon' => '🐦🃏', 'description' => 'The type of Twitter card to display (e.g., summary, player).'],

    // Other common meta tags
    'canonical' => ['name' => 'Canonical URL', 'icon' => '🔗', 'description' => 'The preferred URL for the page, used to avoid duplicate content.'],
];

// Display meta tags in table format with names, icons, and raw names
echo "<table class='meta-table'>";
echo "<tr><th>Meta Tag</th><th>Value</th></tr>";

foreach ($metaData as $key => $value) {
    $friendlyName = $metaFriendlyNames[$key]['name'] ?? ''; // Use friendly name if available
    $icon = $metaFriendlyNames[$key]['icon'] ?? 'ℹ️'; // Use icon if available

    // Update table row to include icon, friendly name, and raw name
    echo "<tr>
            <td>
                <span class='meta-icon'>$icon</span>
                <span class='meta-title' title='{$metaFriendlyNames[$key]['description']}'><span class='title'>{$friendlyName}</span> (<code>$key</code>)</span>
            </td>
            <td>$value</td>
          </tr>";
}

echo "</table>";
                    } else {
                        echo "<p>No meta tags found on the provided URL.</p>";
                    }

$linkTags = extractLinkTags($htmlContent);

if ($linkTags) {
// Display the table with <link> elements
echo "<table class='meta-table'>";
echo "<tr><th>Link Reference (rel)</th><th>Link Href</th></tr>";

foreach ($linkTags as $key => $value) {
    echo "<tr>
            <td>{$key}</td>
            <td>{$value}</td>
          </tr>";
}

echo "</table>";
}

                    // Extract important meta information for social media previews
$title = $metaData['og:title'] ?? $metaData['twitter:title'] ?? "Untitled";
$description = $metaData['og:description'] ?? $metaData['twitter:description'] ?? "No description available.";
$image = $metaData['og:image'] ?? $metaData['twitter:image'] ?? "";
$ogType = $metaData['og:type'] ?? '';

// Only show video if `og:type` is not `video.other`
if ($ogType !== 'video.other') {
    $video = $metaData['og:video'] ?? $metaData['twitter:player'] ?? "";
} else {
    $video = null; // Ignore video.other type
}

echo "<div class='preview-cards'>
        <h3>Link Preview on Social Platforms</h3>";

// Display cards for different platforms
$platforms = [
        ['name' => 'WhatsApp', 'color' => '#25D366'],
        ['name' => 'Telegram', 'color' => '#0088cc'],
        ['name' => 'Facebook', 'color' => '#3b5998'],
        ['name' => 'Instagram', 'color' => '#e4405f'],
        ['name' => 'Pinterest', 'color' => '#bd081c'],
        ['name' => 'Discord', 'color' => '#7289DA'],
        ['name' => 'X (formerly Twitter)', 'color' => '#1DA1F2'],
        ['name' => 'iMessage', 'color' => '#34C759'],
];

foreach ($platforms as $platform) {
    echo "<div class='preview-card' style='border-left: 5px solid {$platform['color']}'>";
    
    // Display video if present and og:type is allowed
    if ($video) {
        echo "<video controls width='100' height='100'>
                <source src='$video' type='video/mp4'>
                Your browser does not support the video tag.
              </video>";
    }
    // Display image if available
    elseif ($image) {
        echo "<img src='$image' alt='Preview Image' width='100' height='100' style='border-radius: 5px; object-fit: cover;'>";
    }
    // Graceful fallback to placeholder
    else {
        echo "<img src='https://via.placeholder.com/100' alt='Placeholder Image' width='100' height='100' style='border-radius: 5px;'>";
    }

    // Display title and description
    echo "<div class='preview-card-content'>
            <span class='platform' style='color: {$platform['color']}; font-weight: bold;'>{$platform['name']}</span>
            <span class='preview-title'>$title</span>
            <span class='preview-description'>$description</span>
          </div>";

    echo "</div>"; // End of preview card
}

echo "</div>";
                }
                }
            }
        }
        ?>
    </div>
</body>
</html>
