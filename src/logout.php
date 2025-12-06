<?php
session_start();

// 1. Destruir todas las variables de sesión
$_SESSION = array();

// 2. Si se desea destruir la cookie de sesión, esto eliminará la sesión,
// pero no la información de sesión en sí.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Destruir la sesión.
session_destroy();

// 4. Redirigir al login
header('Location: login.php');
exit();
?>