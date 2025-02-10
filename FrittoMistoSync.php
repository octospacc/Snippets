<?php
const ITEM_COUNT = 10;
const ITEM_MARGIN = 10;
//const FEED_URL = 'https://octospacc.altervista.org/author/minioctt/feed/';
const INPUT_URL = 'https://octospacc.altervista.org/wp-json/wp/v2/posts?per_page=' . ITEM_COUNT;
const OUTPUT_URL = 'https://public-api.wordpress.com/rest/v1.1/sites/octomediajournal.wordpress.com/posts/?number=' . (ITEM_COUNT * ITEM_MARGIN);

function normalize_date ($date) {
    return explode('+', $date)[0];
}

function post_exists ($source, $destination) {
    if (normalize_date($source->date) === normalize_date($destination->date)) {
        return true;
    } else {
        return false;
    }
}

function post_updated ($source, $destination) {
    // if (normalize_date($source->modified) === normalize_date($destination->modified)) {
    if (normalize_date($source->modified) < normalize_date($destination->modified)) {
        return true;
    } else {
        return false;
    }
}

function make_content ($content, $link) {
    return "<!-- wp:paragraph -->
{$content}
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>...Leggi il post completo su <a href=\"{$link}\">{$link}</a>.</p>
<!-- /wp:paragraph -->

<!-- wp:embed {\"url\":\"{$link}\",\"type\":\"wp-embed\",\"providerNameSlug\":\"fritto-misto-di-octospacc\"} -->
<figure class=\"wp-block-embed is-type-wp-embed is-provider-fritto-misto-di-octospacc wp-block-embed-fritto-misto-di-octospacc\"><div class=\"wp-block-embed__wrapper\">
{$link}
</div></figure>
<!-- /wp:embed -->";
}

// TODO trim body but include media and embeds
function wporg_to_wpcom ($data, $update) {
    $excerpt = $data->excerpt->rendered;
    return [
        'date' => $data->date,
        'title' => ('Post da frittomistocto: ' . $data->title->rendered),
        'content' => make_content(($update ? $excerpt : $data->content->rendered), $data->link), //(/* '<p>Nuovo post dal fritto misto di octospacc!</p>' . */ $data->content->rendered . '<p>...Leggi il post completo su <a href="' . $data->link . '">' . $data->link . '</a>.</p>'),
        'excerpt' => $excerpt,
        'slug' => $data->slug,
        // 'author' => $data->author,
        // 'publicize' => true,
        // 'publicize_message' => '',
        'status' => $data->status,
        'sticky' => $data->sticky,
        //'password' => $data->password,
        // 'parent' => ,
        // 'type' => ,
        // 'terms' => ,
        // 'categories' => $data->categories,
        // 'tags' => $data->tags,
        'format' => $data->format,
        // 'featured_image' => $data->featured_media,
        // 'media' => [],
        // 'media_urls' => [],
        // 'media_attrs' => [],
        // 'metadata' => [],
        'discussion' => [
            'comments_open' => $data->comment_status,
            'pings_open' => $data->ping_status,
        ],
        // 'likes_enabled' => ,
        // 'sharing_enabled' => ,
        // 'menu_order' => 0,
        // 'page_template' => '',
    ];
}

function octomediajournal_post ($data, $id=null) {
    //$url = "https://public-api.wordpress.com/rest/v1.1/sites/82974409/posts/{$id}";
    $url = "https://public-api.wordpress.com/wp/v2/sites/240403429/posts" . ($id ? "/${id}" : '');
    //echo json_encode($data);
    return json_decode(file_get_contents($url, false, stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'method' => 'POST',
            'header' => [
                //('authorization: Bearer ' . OCTOMEDIAJOURNAL_TOKEN),
                ('Authorization: '. WPCOM_AUTH),
                ('Cookie: '. WPCOM_COOKIE),
                //'Content-Type: application/x-www-form-urlencoded',
                'Content-Type: application/json',
            ],
            'content' => json_encode($data), //http_build_query($data), 
        ],
    ])));
}

require (dirname(__FILE__) . '/../../root-secret/FrittoMistoSync.Config.php');
header('Content-Type: text/plain');

$sources = json_decode(file_get_contents(INPUT_URL));
$destinations = json_decode(file_get_contents(OUTPUT_URL))->posts;

// $to_create = [];
// $to_update = [];

foreach ($sources as $source) {
    $destinationid = $result = null;
    $created = $updated = false;

    foreach ($destinations as $destination) {
        if (post_exists($source, $destination)) {
            $created = true;
            //$destinationid = $destination->ID;
            if (post_updated($source, $destination)) {
                $updated = true;
            }
            break;
        }
        unset($destination);
    }

    //$postdata = wporg_to_wpcom($source);
    if (!$created) {
        echo "Creating";
        $result = octomediajournal_post(wporg_to_wpcom($source, false));
    } else if (!$updated || $source->content->rendered === $destination->content /* $destination->content !== make_content($source->excerpt->encoded, $source->link) */) {
        echo "Updating";
        $result = octomediajournal_post(wporg_to_wpcom($source, true), $destination->ID);
    } else {
        echo "Skipping";
    }
    echo ': ' . $source->id . '->' . ($destination->ID ?? 'new') . ' : ' . json_encode($result) . "\n";
    //echo json_encode($postdata) . "\n";
}

echo 'Done!';

// foreach ($to_create as $source) {
//     echo $source->id . ' ';
// }