<?php

/**
 * Este ejemplo crea y gestiona comentarios mediante:
 *
 * 1. Obteniendo los comentarios de nivel superior para un video a través del método "commentThreads.list".
 * 2. Respondiendo a un hilo de comentarios mediante el método "comments.insert".
 * 3. Obteniendo respuestas de comentarios mediante el método "comments.list".
 * 4. Actualizando un comentario existente mediante el método "comments.update".
 * 5. Estableciendo el estado de moderación de un comentario existente mediante el método "comments.setModerationStatus".
 * 6. Marcando un comentario como spam mediante el método "comments.markAsSpam".
 * 7. Eliminando un comentario existente mediante el método "comments.delete".
 *
 * @author andy axcel dominguez ccorahua 
 */

/**
 * Requisitos de la biblioteca
 *
 * 1. Instala composer (https://getcomposer.org)
 * 2. En la línea de comandos, cambia a este directorio (api-samples/php)
 * 3. Requiere la biblioteca google/apiclient
 *    $ composer require google/apiclient:~2.0
 */
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
  throw new \Exception('Por favor, ejecuta "composer require google/apiclient:~2.0" en "' . __DIR__ .'"');
}

require_once __DIR__ . '/vendor/autoload.php';
session_start();

/*
 * Puedes adquirir un ID de cliente y un secreto de cliente de OAuth 2.0 desde
 * {{ Google Cloud Console }} <{{ https://cloud.google.com/console }}>
 * Para obtener más información sobre el uso de OAuth 2.0 para acceder a las API de Google, consulta:
 * <https://developers.google.com/youtube/v3/guides/authentication>
 * Asegúrate de que hayas habilitado la API de Datos de YouTube para tu proyecto.
 */
$OAUTH2_CLIENT_ID = 'REEMPLAZAR';
$OAUTH2_CLIENT_SECRET = 'REEMPLAZAR';

/* Puedes reemplazar $VIDEO_ID con el ID de uno de tus videos y $TEXT con el
 * comentario que deseas agregar.
 */
$VIDEO_ID = 'REEMPLAZAR';
$TEXT = 'REEMPLAZAR';

$client = new Google_Client();
$client->setClientId($OAUTH2_CLIENT_ID);
$client->setClientSecret($OAUTH2_CLIENT_SECRET);

/*
 * Este alcance de acceso de OAuth 2.0 permite acceso completo de lectura/escritura a la
 * cuenta del usuario autenticado y requiere que las solicitudes utilicen una conexión SSL.
 */
$client->setScopes('https://www.googleapis.com/auth/youtube.force-ssl');
$redirect = filter_var('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'],
    FILTER_SANITIZE_URL);
$client->setRedirectUri($redirect);

// Define un objeto que se utilizará para realizar todas las solicitudes a la API.
$youtube = new Google_Service_YouTube($client);

// Verifica si existe un token de autenticación para los alcances requeridos
$tokenSessionKey = 'token-' . $client->prepareScopes();
if (isset($_GET['code'])) {
  if (strval($_SESSION['state']) !== strval($_GET['state'])) {
    die('El estado de la sesión no coincidió.');
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
  try {
    # Se utilizan todos los métodos disponibles en secuencia solo como ejemplo.

    // Llama al método "commentThreads.list" de la API de Datos de YouTube para recuperar hilos de comentarios de video.
    $videoCommentThreads = $youtube->commentThreads->listCommentThreads('snippet', array(
    'videoId' => $VIDEO_ID,
    'textFormat' => 'plainText',
    ));

    $parentId = $videoCommentThreads[0]['id'];

    # Crea un fragmento de comentario con texto.
    $commentSnippet = new Google_Service_YouTube_CommentSnippet();
    $commentSnippet->setTextOriginal($TEXT);
    $commentSnippet->setParentId($parentId);

    # Crea un comentario con el fragmento.
    $comment = new Google_Service_YouTube_Comment();
    $comment->setSnippet($commentSnippet);

    # Llama al método "comments.insert" de la API de Datos de YouTube para responder a un comentario.
    # (Si la intención es crear un nuevo comentario de nivel superior, debería usarse el método "commentThreads.insert" en su lugar.)
    $commentInsertResponse = $youtube->comments->insert('snippet', $comment);

    // Llama al método "comments.list" de la API de Datos de YouTube para recuperar respuestas de comentarios existentes.
    $videoComments = $youtube->comments->listComments('snippet', array(
        'parentId' => $parentId,
        'textFormat' => 'plainText',
    ));

    if (empty($videoComments)) {
      $htmlBody .= "<h3>No se pueden obtener comentarios del video.</h3>";
    } else {
      $videoComments[0]['snippet']['textOriginal'] = 'actualizado';

      // Llama al método "comments.update" de la API de Datos de YouTube para actualizar un comentario existente.
      $videoCommentUpdateResponse = $youtube->comments->update('snippet', $videoComments[0]);

      // Llama al método "comments.setModerationStatus" de la API de Datos de YouTube para establecer el estado de moderación
      // de un comentario existente.
      $youtube->comments->setModerationStatus($videoComments[0]['id'], 'published');

      // Llama al método "comments.markAsSpam" de la API de Datos de YouTube para marcar un comentario existente como spam.
      $youtube->comments->markAsSpam($videoComments[0]['id']);

      // Llama al método "comments.delete" de la API de Datos de YouTube para eliminar un comentario existente.
      $youtube->comments->delete($videoComments[0]['id']);
    }

    $htmlBody .= "<h3>Respuestas de comentarios del video</h3><ul>";
    foreach ($videoComments as $comment) {
      $htmlBody .= sprintf('<li>%s: "%s"</li>', $comment['snippet']['authorDisplayName'],
          $comment['snippet']['textOriginal']);
    }
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Se respondió a un comentario de</h2><ul>";
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $commentInsertResponse['snippet']['authorDisplayName'],
        $commentInsertResponse['snippet']['textDisplay']);
    $htmlBody .= '</ul>';

    $htmlBody .= "<h2>Comentario actualizado de</h2><ul>";
    $htmlBody .= sprintf('<li>%s: "%s"</li>',
        $videoCommentUpdateResponse['snippet']['authorDisplayName'],
        $videoCommentUpdateResponse['snippet']['textDisplay']);
    $htmlBody .= '</ul>';

  } catch (Google_Service_Exception $e) {
    $htmlBody .= sprintf('<p>Ocurrió un error del servicio: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  } catch (Google_Exception $e) {
    $htmlBody .= sprintf('<p>Ocurrió un error del cliente: <code>%s</code></p>',
        htmlspecialchars($e->getMessage()));
  }

  $_SESSION[$tokenSessionKey] = $client->getAccessToken();
} elseif ($OAUTH2_CLIENT_ID == 'REEMPLAZAR') {
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
<title>Insertar, listar, actualizar, moderar, marcar y eliminar comentarios.</title>
</head>
<body>
  <?=$htmlBody?>
</body>
</html>
