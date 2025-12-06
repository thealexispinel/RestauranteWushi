<?php
session_start();
// Incluir la conexión a la base de datos
require 'includes/db.php';

// Verificar la sesión de reseteo para asegurar que no haya acceso directo
if (!isset($_SESSION['reset_user_id'])) {
    // Usar la misma redirección que el login si el acceso no es autorizado
    // O un mensaje de error estilizado. Aquí mantengo el die() por simplicidad del flujo de recuperación.
    // die("Acceso no autorizado"); 
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];

    // 1. Validación de Backend de las contraseñas
    if (strlen($password) < 8) {
        $error = "La contraseña debe tener mínimo 8 caracteres.";
    } elseif ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } else {

        // 2. Hash seguro de la contraseña
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // 3. Actualizar la contraseña
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ?, login_attempts = 0, lock_until = NULL WHERE id = ?");
            $stmt->execute([
                $hash,
                $_SESSION['reset_user_id']
            ]);
    
            // 4. Limpiar códigos de reseteo
            $stmt = $pdo->prepare("DELETE FROM password_codes WHERE user_id = ?");
            $stmt->execute([$_SESSION['reset_user_id']]);
    
            // 5. Destruir la sesión de reseteo (seguridad)
            session_destroy();
    
            $success = "✅ ¡Contraseña actualizada correctamente! Ahora puedes iniciar sesión.";

        } catch (PDOException $e) {
            // Manejo de error de base de datos
            $error = 'Error al actualizar la contraseña.';
            // En producción, se debe loguear $e->getMessage() y no mostrarlo al usuario.
        }

    }
}
?>