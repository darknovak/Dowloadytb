<?php

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';

$htmlBody = <<<END
<form method="GET">
  <div>
    Search Term: <input type="search" id="q" name="q" placeholder="Enter Search Term">
  </div>
  <div>
    Max Results: <input type="number" id="maxResults" name="maxResults" min="1" max="50" step="1" value="25">
  </div>
  <input type="submit" value="Search">
</form>
END;

// This code will execute if the user entered a search query in the form
// and submitted the form. Otherwise, the page displays the form above.
if (isset($_GET['q']) && isset($_GET['maxResults'])) {
  /*
   * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
   * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
   * Please ensure that you have enabled the YouTube Data API for your project.
   */
  $DEVELOPER_KEY = 'REPLACE_ME';

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);

  // Define an object that will be used to make all API requests.
  $youtube = new Google_Service_YouTube($client);

  $htmlBody = '';
  try {

    // Call the search.list method to retrieve results matching the specified
    // query term.
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
      'q' => $_GET['q'],
      'maxResults' => $_GET['maxResults'],
    ));

    $videos = '';
    $channels = '';
    $playlists = '';

    // Add each result to the appropriate list, and then display the lists of
    // matching videos, channels, and playlists.
    foreach ($searchResponse['items'] as $searchResult) {
      switch ($searchResult['id']['kind']) {
        case 'youtube#video':
          $videos .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['videoId']);
          break;
        case 'youtube#channel':
          $channels .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['channelId']);
          break;
        case 'youtube#playlist':
          $playlists .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['playlistId']);
          break;
      }
    }

    $htmlBody .= <<<END
    <h3>Videos</h3>
    <ul>$videos</ul>
    <h3>Channels</h3>
    <ul>$channels</ul>
    <h3>Playlists</h3>
    <ul>$playlists</ul>
END;
  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }
}
?>

<!doctype html>
<html>
  <head>
    <title>YouTube Search</title>
  </head>
  <body>
    <?=$htmlBody?>
  </body>
</html>
<?php
// Defina sua chave de API do YouTube
$apiKey = 'SUA_CHAVE_API';

// Defina a URL da API para buscar vídeos. Isso pode variar dependendo do que você quer buscar.
$searchQuery = 'Angola news';  // Consulta de pesquisa
$maxResults = 10;  // Número máximo de vídeos que você quer buscar

// URL da API do YouTube para buscar vídeos com base na pesquisa
$apiUrl = 'https://www.googleapis.com/youtube/v3/search?part=snippet&q=' . urlencode($searchQuery) . '&maxResults=' . $maxResults . '&key=' . $apiKey;

// Fazendo a requisição à API
$response = file_get_contents($apiUrl);

// Verifica se a resposta foi bem-sucedida
if ($response === FALSE) {
    die('Erro ao buscar vídeos do YouTube.');
}

// Decodifica a resposta JSON
$data = json_decode($response, true);

// Exibe os vídeos encontrados
foreach ($data['items'] as $item) {
    $videoId = $item['id']['videoId'];
    $title = $item['snippet']['title'];
    $thumbnail = $item['snippet']['thumbnails']['medium']['url'];

    echo '<div>';
    echo '<h3>' . $title . '</h3>';
    echo '<a href="https://www.youtube.com/watch?v=' . $videoId . '" target="_blank">';
    echo '<img src="' . $thumbnail . '" alt="' . $title . '">';
    echo '</a>';
    echo '</div>';
}
?>
<?php

/**
 * This sample adds new tags to a YouTube video by:
 *
 * 1. Retrieving the video resource by calling the "youtube.videos.list" method
 *    and setting the "id" parameter
 * 2. Appending new tags to the video resource's snippet.tags[] list
 * 3. Updating the video resource by calling the youtube.videos.update method.
 *
 * @author Ibrahim Ulukaya
*/

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE_ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE_ME';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  $htmlBody = '';
  try{

    // REPLACE this value with the video ID of the video being updated.
    $videoId = "VIDEO_ID";

    // Call the API's videos.list method to retrieve the video resource.
    $listResponse = $youtube->videos->listVideos("snippet",
        array('id' => $videoId));

    // If $listResponse is empty, the specified video was not found.
    if (empty($listResponse)) {
      $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
    } else {
      // Since the request specified a video ID, the response only
      // contains one video resource.
      $video = $listResponse[0];
      $videoSnippet = $video['snippet'];
      $tags = $videoSnippet['tags'];

      // Preserve any tags already associated with the video. If the video does
      // not have any tags, create a new list. Replace the values "tag1" and
      // "tag2" with the new tags you want to associate with the video.
      if (is_null($tags)) {
        $tags = array("tag1", "tag2");
      } else {
        array_push($tags, "tag1", "tag2");
      }

      // Set the tags array for the video snippet
      $videoSnippet['tags'] = $tags;

      // Update the video resource by calling the videos.update() method.
      $updateResponse = $youtube->videos->update("snippet", $video);

      $responseTags = $updateResponse['snippet']['tags'];

      $htmlBody .= "<h3>Video Updated</h3><ul>";
      $htmlBody .= sprintf('<li>Tags "%s" and "%s" added for video %s (%s) </li>',
          array_pop($responseTags), array_pop($responseTags),
          $videoId, $video['snippet']['title']);

      $htmlBody .= '</ul>';
    }
  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  }

    $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {
  // If the user hasn't authorized the app, initiate the OAuth flow
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
<h3>Authorization Required</h3>
<p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>

<!doctype html>
<html>
<head>
<title>Video Updated</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
<?php
// Verifique se uma consulta de busca foi enviada
if (isset($_POST['searchQuery'])) {
    $searchQuery = $_POST['searchQuery'];
} else {
    // Palavra-chave padrão para a primeira exibição de vídeos
    $searchQuery = 'Angola news';
}

$apiKey = 'SUA_CHAVE_DE_API'; // Substitua pela sua chave de API do YouTube
$maxResults = 8; // Número de vídeos a exibir
$apiUrl = "https://www.googleapis.com/youtube/v3/search?part=snippet&q={$searchQuery}&maxResults={$maxResults}&key={$apiKey}";

// Fazendo a requisição HTTP para a API do YouTube
$response = file_get_contents($apiUrl);
$data = json_decode($response);

// Verifica se foram encontrados vídeos
if (!empty($data->items)) {
    foreach ($data->items as $item) {
        // Verifica se o item é um vídeo (evitar playlists e canais)
        if (isset($item->id->videoId)) {
            $videoId = $item->id->videoId;
            $title = $item->snippet->title;
            $thumbnail = $item->snippet->thumbnails->medium->url;
            $videoUrl = "https://www.youtube.com/watch?v={$videoId}";

            // Exibindo os vídeos no formato HTML
            echo "<div class='video'>";
            echo "<img src='{$thumbnail}' alt='{$title}' />";
            echo "<h3>{$title}</h3>";
            echo "<a href='{$videoUrl}' target='_blank'>Assistir no YouTube</a>";
            echo "</div>";
        }
    }
} else {
    echo "Nenhum vídeo encontrado.";
}
?>
<?php

// Call set_include_path() as needed to point to your client library.
if (!file_exists($file = __DIR__ . '/vendor/autoload.php')) {
    throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}
require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * This variable specifies the location of a file where the access and
 * refresh tokens will be written after successful authorization.
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
define('CREDENTIALS_PATH', '~/php-yt-oauth2.json');

function getClient() {
  $client = new Google_Client();
  // Set to name/location of your client_secret.json file.
  $client->setAuthConfigFile('client_secret.json');
  // Set to valid redirect URI for your project.
  $client->setRedirectUri('http://localhost');

  $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Define an object that will be used to make all API requests.
$client = getClient();
$service = new Google_Service_YouTube($client);

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if (!$client->getAccessToken()) {
  print("no access token, whaawhaaa");
  exit;
}

// Call channels.list to retrieve information 

function channelsListByUsername($service, $part, $params) {
    $params = array_filter($params);
    $response = $service->channels->listChannels(
        $part,
        $params
    );

    $description = sprintf(
        'This channel\'s ID is %s. Its title is %s, and it has %s views.',
        $response['items'][0]['id'],
        $response['items'][0]['snippet']['title'],
        $response['items'][0]['statistics']['viewCount']);
    print $description . "\n";
}

channelsListByUsername($service, 'snippet,contentDetails,statistics', array('forUsername' => 'GoogleDevelopers'));
?>
<?php

/**
 * This sample sets and retrieves localized metadata for a channel by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a channel via "channels.update" method.
 * 2. Getting the localized metadata for a channel in a selected language using the
 *   "channels.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a channel using "channels.list" method and
 *   including "localizations" in the "part" parameter.
 *
 * @author Ibrahim Ulukaya
 */

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

$htmlBody = <<<END
<form method="GET">
  <div>
    Action:
    <select id="action" name="action">
      <option value="set">Set Localization - Fill in: channel ID, default language, language, description</option>
      <option value="get">Get Localization- Fill in: channel ID, language</option>
      <option value="list">List Localizations - Fill in: channel ID, language</option>
    </select>
  </div>
  <br>
  <div>
    Channel ID: <input type="text" id="channelId" name="channelId" placeholder="Enter Channel ID">
  </div>
  <br>
  <div>
    Default Language: <input type="text" id="defaultLanguage" name="defaultLanguage" placeholder="Enter Default Language (BCP-47 language code)">
  </div>
  <br>
  <div>
    Language: <input type="text" id="language" name="language" placeholder="Enter Local Language (BCP-47 language code)">
  </div>
  <br>
  <div>
    Description: <input type="text" id="description" name="description" placeholder="Enter Description">
  </div>
  <br>
  <input type="submit" value="GO!">
</form>
END;

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE_ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE_ME';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);

/*
 * This OAuth 2.0 access scope allows for full read/write access to the
 * authenticated user's account.
 */
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  // This code executes if the user enters an action in the form
  // and submits the form. Otherwise, the page displays the form above.
  if (isset($_GET['action'])) {
    $htmlBody = '';
    $resource = $_GET['resource'];
    $channelId = $_GET['channelId'];
    $language = $_GET['language'];
    $defaultLanguage = $_GET['defaultLanguage'];
    $description = $_GET['description'];
    try {
      switch ($_GET['action']) {
        case 'set':
          setChannelLocalization($youtube, $channelId, $defaultLanguage,
              $language, $description, $htmlBody);
          break;
        case 'get':
          getChannelLocalization($youtube, $channelId, $language, $htmlBody);
          break;
        case 'list':
          listChannelLocalizations($youtube, $channelId, $htmlBody);
          break;
      }
    } catch (Google_Service_Exception $e) {
      $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
      $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    }
  }
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {
  // If the user hasn't authorized the app, initiate the OAuth flow
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}


/**
 * Updates a channel's default language and sets its localized metadata.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The id parameter specifies the channel ID for the resource
 * that is being updated.
 * @param string $defaultLanguage The language of the channel's default metadata
 * @param string $language The language of the localized metadata
 * @param string $description The localized description to be set
 * @param $htmlBody - html body.
 */
function setChannelLocalization(Google_Service_YouTube $youtube, $channelId, $defaultLanguage,
    $language, $description, &$htmlBody) {
  // Call the YouTube Data API's channels.list method to retrieve channels.
  $channels = $youtube->channels->listChannels("brandingSettings,localizations", array(
      'id' => $channelId
  ));

  // If $channels is empty, the specified channel was not found.
  if (empty($channels)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel with channel id: %s</h3>', $channelId);
  } else {
    // Since the request specified a channel ID, the response only
    // contains one channel resource.
    $updateChannel = $channels[0];

    // Modify channel's default language and localizations properties.
    // Ensure that a value is set for the resource's snippet.defaultLanguage property.
    // To set the snippet.defaultLanguage property for a channel resource,
    // you actually need to update the brandingSettings.channel.defaultLanguage property.
    $updateChannel['brandingSettings']['channel']['defaultLanguage'] = $defaultLanguage;
    $localizations = $updateChannel['localizations'];

    if (is_null($localizations)) {
      $localizations = array();
    }
    $localizations[$language] = array('description' => $description);
    $updateChannel['localizations'] = $localizations;

    // Call the YouTube Data API's channels.update method to update an existing channel.
    $channelUpdateResponse = $youtube->channels->update("brandingSettings,localizations",
        $updateChannel);

    $htmlBody .= "<h2>Updated channel</h2><ul>";
    $htmlBody .= sprintf('<li>(%s) default language: %s</li>', $channelId,
        $channelUpdateResponse['brandingSettings']['channel']['defaultLanguage']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language,
        $channelUpdateResponse['localizations'][$language]['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns localized metadata for a channel in a selected language.
 * If the localized text is not available in the requested language,
 * this method will return text in the default language.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The channelId parameter instructs the API to return the
 * localized metadata for the channel specified by the channel id.
 * @param string language The language of the localized metadata.
 * @param $htmlBody - html body.
 */
function getChannelLocalization(Google_Service_YouTube $youtube, $channelId,
    $language, &$htmlBody) {
  // Call the YouTube Data API's channels.list method to retrieve channels.
  $channels = $youtube->channels->listChannels("snippet", array(
      'id' => $channelId,
      'hl' => $language
  ));

  // If $channels is empty, the specified channel was not found.
  if (empty($channels)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel with channel id: %s</h3>', $channelId);
  } else {
    // Since the request specified a channel ID, the response only
    // contains one channel resource.
    $localized = $channels[0]["snippet"]["localized"];

    $htmlBody .= "<h3>Channel</h3><ul>";
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localized['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns a list of localized metadata for a channel.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $channelId The channelId parameter instructs the API to return the
 * localized metadata for the channel specified by the channel id.
 * @param $htmlBody - html body.
 */
function listChannelLocalizations(Google_Service_YouTube $youtube, $channelId, &$htmlBody) {
  // Call the YouTube Data API's channels.list method to retrieve channels.
  $channels = $youtube->channels->listChannels("snippet,localizations", array(
      'id' => $channelId
  ));

  // If $channels is empty, the specified channel was not found.
  if (empty($channels)) {
    $htmlBody .= sprintf('<h3>Can\'t find a channel with channel id: %s</h3>', $channelId);
  } else {
    // Since the request specified a channel ID, the response only
    // contains one channel resource.
    $localizations = $channels[0]["localizations"];

    $htmlBody .= "<h3>Channel</h3><ul>";
    foreach ($localizations as $language => $localization) {
      $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localization['description']);
    }
    $htmlBody .= '</ul>';
  }
}
?>

<!doctype html>
<html>
<head>
<title>Set and retrieve localized metadata for a channel</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
    <?php

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE_ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE_ME';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  try {
    // This code creates a new, private playlist in the authorized user's
    // channel and adds a video to the playlist.

    // 1. Create the snippet for the playlist. Set its title and description.
    $playlistSnippet = new Google_Service_YouTube_PlaylistSnippet();
    $playlistSnippet->setTitle('Test Playlist  ' . date("Y-m-d H:i:s"));
    $playlistSnippet->setDescription('A private playlist created with the YouTube API v3');

    // 2. Define the playlist's status.
    $playlistStatus = new Google_Service_YouTube_PlaylistStatus();
    $playlistStatus->setPrivacyStatus('private');

    // 3. Define a playlist resource and associate the snippet and status
    // defined above with that resource.
    $youTubePlaylist = new Google_Service_YouTube_Playlist();
    $youTubePlaylist->setSnippet($playlistSnippet);
    $youTubePlaylist->setStatus($playlistStatus);

    // 4. Call the playlists.insert method to create the playlist. The API
    // response will contain information about the new playlist.
    $playlistResponse = $youtube->playlists->insert('snippet,status',
        $youTubePlaylist, array());
    $playlistId = $playlistResponse['id'];

    // 5. Add a video to the playlist. First, define the resource being added
    // to the playlist by setting its video ID and kind.
    $resourceId = new Google_Service_YouTube_ResourceId();
    $resourceId->setVideoId('SZj6rAYkYOg');
    $resourceId->setKind('youtube#video');

    // Then define a snippet for the playlist item. Set the playlist item's
    // title if you want to display a different value than the title of the
    // video being added. Add the resource ID and the playlist ID retrieved
    // in step 4 to the snippet as well.
    $playlistItemSnippet = new Google_Service_YouTube_PlaylistItemSnippet();
    $playlistItemSnippet->setTitle('First video in the test playlist');
    $playlistItemSnippet->setPlaylistId($playlistId);
    $playlistItemSnippet->setResourceId($resourceId);

    // Finally, create a playlistItem resource and add the snippet to the
    // resource, then call the playlistItems.insert method to add the playlist
    // item.
    $playlistItem = new Google_Service_YouTube_PlaylistItem();
    $playlistItem->setSnippet($playlistItemSnippet);
    $playlistItemResponse = $youtube->playlistItems->insert(
        'snippet,contentDetails', $playlistItem, array());

    $htmlBody .= "<h3>New Playlist</h3><ul>";
    $htmlBody .= sprintf('<li>%s (%s)</li>',
        $playlistResponse['snippet']['title'],
        $playlistResponse['id']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h3>New PlaylistItem</h3><ul>";
    $htmlBody .= sprintf('<li>%s (%s)</li>',
        $playlistItemResponse['snippet']['title'],
        $playlistItemResponse['id']);
    $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
    $htmlBody = sprintf('<p>A service error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody = sprintf('<p>An client error occurred: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  }

  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {
  // If the user hasn't authorized the app, initiate the OAuth flow
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}
?>

<!doctype html>
<html>
<head>
<title>New Playlist</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
    <?php

/**
 * This sample sets and retrieves localized metadata for a video by:
 *
 * 1. Updating language of the default metadata and setting localized metadata
 *   for a video via "videos.update" method.
 * 2. Getting the localized metadata for a video in a selected language using the
 *   "videos.list" method and setting the "hl" parameter.
 * 3. Listing the localized metadata for a video using the "videos.list" method and
 *   including "localizations" in the "part" parameter.
 *
 * @author Ibrahim Ulukaya
 */

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

$htmlBody = <<<END
<form method="GET">
  <div>
    Action:
    <select id="action" name="action">
      <option value="set">Set Localization - Fill in: video ID, default language, language, title and description</option>
      <option value="get">Get Localization- Fill in: video ID, language</option>
      <option value="list">List Localizations - Fill in: video ID, language</option>
    </select>
  </div>
  <br>
  <div>
    Video ID: <input type="text" id="videoId" name="videoId" placeholder="Enter Video ID">
  </div>
  <br>
  <div>
    Default Language: <input type="text" id="defaultLanguage" name="defaultLanguage" placeholder="Enter Default Language (BCP-47 language code)">
  </div>
  <br>
  <div>
    Language: <input type="text" id="language" name="language" placeholder="Enter Local Language (BCP-47 language code)">
  </div>
  <br>
  <div>
    Title: <input type="text" id="title" name="title" placeholder="Enter Title">
  </div>
  <br>
  <div>
    Description: <input type="text" id="description" name="description" placeholder="Enter Description">
  </div>
  <br>
  <input type="submit" value="GO!">
</form>
END;

/*
 * You can acquire an OAuth 2.0 client ID and client secret from the
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * For more information about using OAuth 2.0 to access Google APIs, please see:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
$OAUTH2_CLIENT_ID = 'REPLACE_ME';
$OAUTH2_CLIENT_SECRET = 'REPLACE_ME';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);

/*
 * This OAuth 2.0 access scope allows for full read/write access to the
 * authenticated user's account.
 */
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define an object that will be used to make all API requests.
$youtube = new Google_Service_YouTube($client);

// Check if an auth token exists for the required scopes
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Check to ensure that the access token was successfully acquired.
if ($client->getAccessToken()) {
  // This code executes if the user enters an action in the form
  // and submits the form. Otherwise, the page displays the form above.
  if (isset($_GET['action'])) {
    $htmlBody = '';
    $videoId = $_GET['videoId'];
    $language = $_GET['language'];
    $defaultLanguage = $_GET['defaultLanguage'];
    $title = $_GET['title'];
    $description = $_GET['description'];
    try {
      switch ($_GET['action']) {
        case 'set':
          setVideoLocalization($youtube, $videoId, $defaultLanguage,
              $language, $title, $description, $htmlBody);
          break;
        case 'get':
          getVideoLocalization($youtube, $videoId, $language, $htmlBody);
          break;
        case 'list':
          listVideoLocalizations($youtube, $videoId, $htmlBody);
          break;
      }
    } catch (Google_Service_Exception $e) {
      $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    } catch (Google_Exception $e) {
      $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
          htmlspecialchars($e->getMessage()));
    }
  }
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REPLACE_ME') {
  $htmlBody = <<<END
  <h3>Client Credentials Required</h3>
  <p>
    You need to set <code>\$OAUTH2_CLIENT_ID</code> and
    <code>\$OAUTH2_CLIENT_ID</code> before proceeding.
  <p>
END;
} else {
  // If the user hasn't authorized the app, initiate the OAuth flow
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Authorization Required</h3>
  <p>You need to <a href="$authUrl">authorize access</a> before proceeding.<p>
END;
}


/**
 * Updates a video's default language and sets its localized metadata.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The id parameter specifies the video ID for the resource
 * that is being updated.
 * @param string $defaultLanguage The language of the video's default metadata
 * @param string $language The language of the localized metadata
 * @param string $title The localized title to be set
 * @param string $description The localized description to be set
 * @param $htmlBody - html body.
 */
function setVideoLocalization(Google_Service_YouTube $youtube, $videoId, $defaultLanguage,
    $language, $title, $description, &$htmlBody) {
  // Call the YouTube Data API's videos.list method to retrieve videos.
  $videos = $youtube->videos->listVideos("snippet,localizations", array(
      'id' => $videoId
  ));

  // If $videos is empty, the specified video was not found.
  if (empty($videos)) {
    $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
  } else {
    // Since the request specified a video ID, the response only
    // contains one video resource.
    $updateVideo = $videos[0];

    // Modify video's default language and localizations properties.
    // Ensure that a value is set for the resource's snippet.defaultLanguage property.
    $updateVideo['snippet']['defaultLanguage'] = $defaultLanguage;
    $localizations = $updateVideo['localizations'];

    if (is_null($localizations)) {
      $localizations = array();
    }
    $localizations[$language] = array('title' => $title, 'description' => $description);
    $updateVideo['localizations'] = $localizations;

    // Call the YouTube Data API's videos.update method to update an existing video.
    $videoUpdateResponse = $youtube->videos->update("snippet,localizations", $updateVideo);

    $htmlBody .= "<h2>Updated video</h2><ul>";
    $htmlBody .= sprintf('<li>(%s) default language: %s</li>', $videoId,
        $videoUpdateResponse['snippet']['defaultLanguage']);
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language,
        $videoUpdateResponse['localizations'][$language]['title']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language,
        $videoUpdateResponse['localizations'][$language]['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns localized metadata for a video in a selected language.
 * If the localized text is not available in the requested language,
 * this method will return text in the default language.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The videoId parameter instructs the API to return the
 * localized metadata for the video specified by the video id.
 * @param string language The language of the localized metadata.
 * @param $htmlBody - html body.
 */
function getVideoLocalization(Google_Service_YouTube $youtube, $videoId, $language, &$htmlBody) {
  // Call the YouTube Data API's videos.list method to retrieve videos.
  $videos = $youtube->videos->listVideos("snippet", array(
      'id' => $videoId,
      'hl' => $language
  ));

  // If $videos is empty, the specified video was not found.
  if (empty($videos)) {
    $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
  } else {
    // Since the request specified a video ID, the response only
    // contains one video resource.
    $localized = $videos[0]["snippet"]["localized"];

    $htmlBody .= "<h3>Video</h3><ul>";
    $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localized['title']);
    $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localized['description']);
    $htmlBody .= '</ul>';
  }
}

/**
 * Returns a list of localized metadata for a video.
 *
 * @param Google_Service_YouTube $youtube YouTube service object.
 * @param string $videoId The videoId parameter instructs the API to return the
 * localized metadata for the video specified by the video id.
 * @param $htmlBody - html body.
 */
function listVideoLocalizations(Google_Service_YouTube $youtube, $videoId, &$htmlBody) {
  // Call the YouTube Data API's videos.list method to retrieve videos.
  $videos = $youtube->videos->listVideos("snippet,localizations", array(
      'id' => $videoId
  ));

  // If $videos is empty, the specified video was not found.
  if (empty($videos)) {
    $htmlBody .= sprintf('<h3>Can\'t find a video with video id: %s</h3>', $videoId);
  } else {
    // Since the request specified a video ID, the response only
    // contains one video resource.
    $localizations = $videos[0]["localizations"];

    $htmlBody .= "<h3>Video</h3><ul>";
    foreach ($localizations as $language => $localization) {
      $htmlBody .= sprintf('<li>title(%s): %s</li>', $language, $localization['title']);
      $htmlBody .= sprintf('<li>description(%s): %s</li>', $language, $localization['description']);
    }
    $htmlBody .= '</ul>';
  }
}

?>

<!doctype html>
<html>
<head>
<title>Set and retrieve localized metadata for a video</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
    <?php

/**
 * Library Requirements
 *
 * 1. Install composer (https://getcomposer.org)
 * 2. On the command line, change to this directory (api-samples/php)
 * 3. Require the google/apiclient library
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';

$htmlBody = <<<END
<form method="GET">
  <div>
    Search Term: <input type="search" id="q" name="q" placeholder="Enter Search Term">
  </div>
  <div>
    Max Results: <input type="number" id="maxResults" name="maxResults" min="1" max="50" step="1" value="25">
  </div>
  <input type="submit" value="Search">
</form>
END;

// This code will execute if the user entered a search query in the form
// and submitted the form. Otherwise, the page displays the form above.
if (isset($_GET['q']) && isset($_GET['maxResults'])) {
  /*
   * Set $DEVELOPER_KEY to the "API key" value from the "Access" tab of the
   * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
   * Please ensure that you have enabled the YouTube Data API for your project.
   */
  $DEVELOPER_KEY = 'REPLACE_ME';

  $client = new Google_Client();
  $client->setDeveloperKey($DEVELOPER_KEY);

  // Define an object that will be used to make all API requests.
  $youtube = new Google_Service_YouTube($client);

  $htmlBody = '';
  try {

    // Call the search.list method to retrieve results matching the specified
    // query term.
    $searchResponse = $youtube->search->listSearch('id,snippet', array(
      'q' => $_GET['q'],
      'maxResults' => $_GET['maxResults'],
    ));

    $videos = '';
    $channels = '';
    $playlists = '';

    // Add each result to the appropriate list, and then display the lists of
    // matching videos, channels, and playlists.
    foreach ($searchResponse['items'] as $searchResult) {
      switch ($searchResult['id']['kind']) {
        case 'youtube#video':
          $videos .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['videoId']);
          break;
        case 'youtube#channel':
          $channels .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['channelId']);
          break;
        case 'youtube#playlist':
          $playlists .= sprintf('<li>%s (%s)</li>',
              $searchResult['snippet']['title'], $searchResult['id']['playlistId']);
          break;
      }
    }

    $htmlBody .= <<<END
    <h3>Videos</h3>
    <ul>$videos</ul>
    <h3>Channels</h3>
    <ul>$channels</ul>
    <h3>Playlists</h3>
    <ul>$playlists</ul>
END;
  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>A service error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>An client error occurred: <code>%s</code></p>',
      htmlspecialchars($e->getMessage()));
  }
}
?>

<!doctype html>
<html>
  <head>
    <title>YouTube Search</title>
  </head>
  <body>
    <?=$htmlBody?>
  </body>
</html>
    <?php

// Call set_include_path() as needed to point to your client library.
if (!file_exists($file = __DIR__ . '/vendor/autoload.php')) {
    throw new \Exception('please run "composer require google/apiclient:~2.0" in "' . __DIR__ .'"');
}
require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * This variable specifies the location of a file where the access and
 * refresh tokens will be written after successful authorization.
 * Please ensure that you have enabled the YouTube Data API for your project.
 */
define('CREDENTIALS_PATH', '~/php-yt-oauth2.json');

function getClient() {
  $client = new Google_Client();
  // Set to name/location of your client_secret.json file.
  $client->setAuthConfigFile('client_secret.json');
  // Set to valid redirect URI for your project.
  $client->setRedirectUri('http://localhost');

  $client->addScope(Google_Service_YouTube::YOUTUBE_READONLY);
  $client->setAccessType('offline');

  // Load previously authorized credentials from a file.
  $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
  if (file_exists($credentialsPath)) {
    $accessToken = file_get_contents($credentialsPath);
  } else {
    // Request authorization from the user.
    $authUrl = $client->createAuthUrl();
    printf("Open the following link in your browser:\n%s\n", $authUrl);
    print 'Enter verification code: ';
    $authCode = trim(fgets(STDIN));

    // Exchange authorization code for an access token.
    $accessToken = $client->authenticate($authCode);

    // Store the credentials to disk.
    if(!file_exists(dirname($credentialsPath))) {
      mkdir(dirname($credentialsPath), 0700, true);
    }
    file_put_contents($credentialsPath, $accessToken);
    printf("Credentials saved to %s\n", $credentialsPath);
  }
  $client->setAccessToken($accessToken);

  // Refresh the token if it's expired.
  if ($client->isAccessTokenExpired()) {
    $client->refreshToken($client->getRefreshToken());
    file_put_contents($credentialsPath, $client->getAccessToken());
  }
  return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
  $homeDirectory = getenv('HOME');
  if (empty($homeDirectory)) {
    $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
  }
  return str_replace('~', realpath($homeDirectory), $path);
}

// Define an object that will be used to make all API requests.
$client = getClient();
$service = new Google_Service_YouTube($client);

if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('The session state did not match.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION['token'] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

if (!$client->getAccessToken()) {
  print("no access token, whaawhaaa");
  exit;
}

// Call channels.list to retrieve information 

function channelsListByUsername($service, $part, $params) {
    $params = array_filter($params);
    $response = $service->channels->listChannels(
        $part,
        $params
    );

    $description = sprintf(
        'This channel\'s ID is %s. Its title is %s, and it has %s views.',
        $response['items'][0]['id'],
        $response['items'][0]['snippet']['title'],
        $response['items'][0]['statistics']['viewCount']);
    print $description . "\n";
}

channelsListByUsername($service, 'snippet,contentDetails,statistics', array('forUsername' => 'GoogleDevelopers'));
?>
<!DOCTYPE html>
<html>
  <body>
    <p>Videos uploaded by {{ channel_name }}: </p>
    {% for video in videos %}
      <img src="{{ video.snippet.thumbnails.default.url }}" style="padding-right: 15px">
      <div style="vertical-align:top; display: inline-block">
        <strong><a href="https://www.youtube.com/watch?v={{ video.id.videoId }}">{{ video.snippet.title }}</a></strong>
      </div><br/><br/>
    {% endfor %}
  </body>
</html>