<?php

/**
 * Requisitos de la biblioteca
 *
 * 1. Instalar composer (https://getcomposer.org)
 * 2. En la línea de comandos, cambiar a este directorio (api-samples/php)
 * 3. Requerir la biblioteca google/apiclient
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('Por favor, ejecuta "composer require google/apiclient:~2.0" en "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * Puedes obtener un ID de cliente y un secreto de cliente OAuth 2.0 desde
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * Para obtener más información sobre cómo usar OAuth 2.0 para acceder a las API de Google, consulta:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Asegúrate de haber habilitado la API de datos de YouTube para tu proyecto.
 */
$OAUTH2_CLIENT_ID = 'REEMPLAZAR';
$OAUTH2_CLIENT_SECRET = 'REEMPLAZAR';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);
$client->setScopes('https://www.googleapis.com/auth/youtube');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define un objeto que se utilizará para realizar todas las solicitudes a la API.
$youtube = new Google_Service_YouTube($client);

// Verifica si existe un token de autenticación para los ámbitos requeridos.
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('El estado de la sesión no coincide.');
  }

  $client->authenticate($_GET['code']);
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
  header('Location: ' . $redirect);
}

if (isset($_SESSION[$tokenSessionKey])) {
  $client->setAccessToken($_SESSION[$tokenSessionKey]);
}

// Verifica que se haya adquirido el token de acceso con éxito.
if ($client->getAccessToken()) {
  $htmlBody = '';
  try{
    // REEMPLAZAR este valor con la ruta al archivo que estás subiendo.
    $videoPath = "/ruta/al/archivo.mp4";

    // Crea un fragmento con título, descripción, etiquetas e ID de categoría.
    // Crea un recurso de activo y establece su metadatos y tipo de fragmento.
    // Este ejemplo establece el título, la descripción, las etiquetas y
    // la categoría del video.
    $snippet = new Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle("Título de prueba");
    $snippet->setDescription("Descripción de prueba");
    $snippet->setTags(array("etiqueta1", "etiqueta2"));

    // ID numérico de la categoría del video. Consulta
    // https://developers.google.com/youtube/v3/docs/videoCategories/list
    $snippet->setCategoryId("22");

    // Establece el estado del video como "público". Los estados válidos son "público",
    // "privado" y "no listado".
    $status = new Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "público";

    // Asocia los objetos de fragmento y estado con un nuevo recurso de video.
    $video = new Google_Service_YouTube_Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);

    // Especifica el tamaño de cada fragmento de datos, en bytes. Establece un valor más alto para
    // una conexión más confiable, ya que menos fragmentos conducen a una carga más rápida. Establece un valor más bajo
    // para una mejor recuperación en conexiones menos confiables.
    $chunkSizeBytes = 1 * 1024 * 1024;

    // Establecer la bandera de diferir en true indica al cliente que devuelva una solicitud que se puede llamar
    // con ->execute(); en lugar de realizar la llamada a la API inmediatamente.
    $client->setDefer(true);

    // Crea una solicitud para el método videos.insert de la API para crear y cargar el video.
    $insertRequest = $youtube->videos->insert("estado,fragmento", $video);

    // Crea un objeto MediaFileUpload para cargas resumibles.
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'video/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($videoPath));

    // Lee el archivo multimedia y cárgalo fragmento por fragmento.
    $status = false;
    $handle = fopen($videoPath, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }

    fclose($handle);

    // Si deseas realizar otras llamadas después de la carga del archivo, establece setDefer de nuevo en false.
    $client->setDefer(false);

    $htmlBody .= "<h3>Video cargado</h3><ul>";
    $htmlBody .= sprintf('<li>%s (%s)</li>',
        $status['fragmento']['título'],
        $status['id']);

    $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>Ocurrió un error del servicio: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>Ocurrió un error del cliente: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  }<?php

// ... (código anterior)

// Verifica si el usuario ha autorizado la aplicación
if ($client->getAccessToken()) {
  $htmlBody = '';
  try {
    // Ruta al archivo de video que se cargará
    $videoPath = "/ruta/al/archivo.mp4";

    // Crea un objeto de fragmento con metadatos del video
    $snippet = new Google_Service_YouTube_VideoSnippet();
    $snippet->setTitle("Título de prueba");
    $snippet->setDescription("Descripción de prueba");
    $snippet->setTags(array("etiqueta1", "etiqueta2"));
    $snippet->setCategoryId("22"); // ID numérico de la categoría del video (ver la documentación)

    // Crea un objeto de estado del video y establece la privacidad como "público"
    $status = new Google_Service_YouTube_VideoStatus();
    $status->privacyStatus = "public";

    // Asocia los objetos de fragmento y estado con un nuevo recurso de video
    $video = new Google_Service_YouTube_Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);

    // Configura el tamaño de cada fragmento para la carga en partes
    $chunkSizeBytes = 1 * 1024 * 1024;

    // Habilita la opción de diferir para obtener una solicitud que se puede ejecutar más adelante
    $client->setDefer(true);

    // Crea una solicitud para el método videos.insert de la API para crear y cargar el video
    $insertRequest = $youtube->videos->insert("status,snippet", $video);

    // Crea un objeto MediaFileUpload para las cargas en partes
    $media = new Google_Http_MediaFileUpload(
        $client,
        $insertRequest,
        'video/*',
        null,
        true,
        $chunkSizeBytes
    );
    $media->setFileSize(filesize($videoPath));

    // Lee el archivo de video y cárgalo en partes
    $status = false;
    $handle = fopen($videoPath, "rb");
    while (!$status && !feof($handle)) {
      $chunk = fread($handle, $chunkSizeBytes);
      $status = $media->nextChunk($chunk);
    }

    fclose($handle);

    // Si se desean realizar otras llamadas después de cargar el archivo, establecer setDefer nuevamente en false
    $client->setDefer(false);

    // Mensaje de éxito y detalles del video cargado
    $htmlBody .= "<h3>Video cargado</h3><ul>";
    $htmlBody .= sprintf('<li>%s (%s)</li>',
        $status['snippet']['title'],
        $status['id']);
    $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
    // Manejo de errores del servicio
    $htmlBody .= sprintf('<p>Ocurrió un error del servicio: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    // Manejo de errores del cliente
    $htmlBody .= sprintf('<p>Ocurrió un error del cliente: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  }

  // Almacena el token de acceso en la sesión
  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REEMPLAZAR') {
  // Mensaje si las credenciales del cliente no se han configurado
  $htmlBody = <<<END
  <h3>Se requieren credenciales del cliente</h3>
  <p>
    Necesitas establecer <code>\$OAUTH2_CLIENT_ID</code> y
    <code>\$OAUTH2_CLIENT_ID</code> antes de continuar.
  <p>
END;
} else {
  // Si el usuario no ha autorizado la aplicación, inicia el flujo de OAuth
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  // Genera la URL de autorización y proporciona un enlace para que el usuario la apruebe
  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Se requiere autorización</h3>
  <p>Necesitas <a href="$authUrl">autorizar el acceso</a> antes de continuar.<p>
END;
}





} else {
  // Si el usuario no ha autorizado la aplicación, inicia el flujo de OAuth
  $state = mt_rand();
  $client->setState($state);
  $_SESSION['state'] = $state;

  $authUrl = $client->createAuthUrl();
  $htmlBody = <<<END
  <h3>Se requiere autorización</h3>
  <p>Necesitas <a href="$authUrl">autorizar el acceso</a> antes de continuar.<p>
END;
}
?>

<!doctype html>
<html>
<head>
<title>Video Cargado</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
