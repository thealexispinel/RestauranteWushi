<?php
require 'includes/db.php';

$error = '';
$message = '';

// Variables para mantener los valores en el input (solo el token, las contraseñas no)
$code_input = ''; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Obtener y sanitizar inputs
    $code = trim($_POST['code'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $code_input = htmlspecialchars($code); // Mantener el token en el campo por si hay error

    if (!$code || !$new_password || !$confirm_password) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($new_password) < 8) {
        $error = "La contraseña debe tener mínimo 8 caracteres.";
    } else {

        try {
            // 2. Buscar token válido y no expirado (Inyección SQL prevenida con parámetros nombrados)
            $stmt = $pdo->prepare("
                SELECT user_id, expires_at
                FROM password_codes
                WHERE code = :code
                LIMIT 1
            ");
            $stmt->execute([':code' => $code]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reset) {
                $error = "Token inválido.";
            } elseif (strtotime($reset['expires_at']) < time()) {
                $error = "El token ha expirado.";
            } else {
                $user_id = $reset['user_id'];

                // 3. Encriptar nueva contraseña
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // 4. Actualizar contraseña (y resetear intentos/bloqueo por seguridad)
                $stmt = $pdo->prepare("
                    UPDATE users
                    SET password = :password, login_attempts = 0, lock_until = NULL
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':password' => $hashed_password,
                    ':id' => $user_id
                ]);

                // 5. Eliminar token usado para evitar Replay Attacks (Uso único)
                $stmt = $pdo->prepare("
                    DELETE FROM password_codes
                    WHERE user_id = :user_id
                ");
                $stmt->execute([':user_id' => $user_id]);

                $message = "Tu contraseña ha sido actualizada correctamente. ¡Ya puedes iniciar sesión!";
            }

        } catch (PDOException $e) {
            // Loguear el error y mostrar un mensaje genérico al usuario
            error_log("Password change error: " . $e->getMessage());
            $error = "Error interno del servidor. Por favor, inténtalo de nuevo.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WUSHI - Cambiar Contraseña</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@100..900&display=swap" rel="stylesheet">
    <style>
        /* INICIO: ESTILOS CSS REUTILIZADOS */

        /* 1. VARIABLES CSS NATIVAS (reemplazando a $primary de SCSS) */
        :root {
            --primary-color: rgb(182, 157, 230);
            --saturated-primary: rgb(167, 137, 227); 
            --displacement: 3px;
        }

        /* 2. ESTILOS BASE */
        * {
            font-family: "Lexend Deca", sans-serif;
            font-optical-sizing: auto;
            font-weight: 400; 
            font-style: normal;
            margin: 0;
        }

        html {
            height: 100%; 
            margin: 0;
            overflow-x: hidden; 
        }

        body {
            height: 100%; 
            width: 100vw;
            margin: 0;
            display: flex;
            align-items: flex-start; 
            justify-content: flex-start;
            background-image: url("https://cdnb.artstation.com/p/assets/images/images/047/282/573/large/pangda-.jpg?1647226604"); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed; 
        }

        h4 {
            font-size: 24px;
            font-weight: 600;
            color: #000;
            opacity: .85;
        }

        label {
            font-size: 12.5px;
            color: #000;
            opacity: .8;
            font-weight: 400;
        }

        /* 3. CONTENEDORES PRINCIPALES */

        .session {
            display: flex;
            flex-direction: row;
            width: auto;
            height: auto;
            margin: auto auto;
            border-radius: 4px;
            box-shadow: 0px 2px 6px -1px rgba(0,0,0,.12);
        }

        .left {
            width: 220px;
            height: auto;
            min-height: 100%;
            position: relative;
            background-image: url("https://cdna.artstation.com/p/assets/images/images/039/196/844/large/claire-lin-img-8962.jpg?1625205968");
            background-size: cover;
            border-top-left-radius: 4px;
            border-bottom-left-radius: 4px;
        }

        .left svg {
            height: 40px;
            width: auto;
            margin: 20px;
        }

        /* 4. ESTILOS DEL FORMULARIO */

        form {
            padding: 40px 30px;
            background: #fefefe;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding-bottom: 20px;
            width: 300px;
        }

        form h4 {
            margin-bottom: 20px;
            color: rgba(0, 0, 0, .5);
        }

        form h4 span {
            color: rgba(0, 0, 0, 1);
            font-weight: 700;
        }

        form p {
            line-height: 155%;
            margin-bottom: 5px;
            font-size: 14px;
            color: #000;
            opacity: .65;
            font-weight: 400;
            max-width: 280px;
            margin-bottom: 20px;
        }
        
        /* Estilo para mensajes de error */
        .error-message {
            color: #d32f2f;
            background-color: #ffebee;
            border: 1px solid #ffcdd2;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
            font-weight: 400;
        }

        /* Estilo para mensajes de éxito */
        .success-message {
            color: #2e7d32;
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 16px;
            width: 100%;
            box-sizing: border-box;
            font-weight: 500;
            text-align: center;
        }


        /* 5. ENLACE DISCRETO */

        a.discrete {
            color: var(--primary-color);
            font-size: 14px;
            border-bottom: solid 1px rgba(0, 0, 0, .0);
            padding-bottom: 4px;
            margin: 10px auto 0 auto;
            font-weight: 500;
            transition: all .3s ease;
            text-decoration: none;
            display: block;
        }

        a.discrete:hover {
            border-bottom: solid 1px var(--primary-color);
        }

        /* 6. BOTÓN */

        button {
            -webkit-appearance: none;
            -moz-appearance: none;    
            appearance: none;         
            width: auto;
            min-width: 100px;
            border-radius: 24px;
            text-align: center;
            padding: 15px 40px;
            margin-top: 20px;
            margin-bottom: 7px;
            background-color: #F26E50;
            color: #fff;
            font-size: 14px;
            margin-left: auto;
            font-weight: 500;
            box-shadow: 0px 2px 6px -1px rgba(0,0,0,.13);
            border: none;
            transition: all .3s ease;
            outline: 0;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 2px 6px -1px rgba(182, 157, 230, .65);
        }

        button:hover:active {
            transform: scale(.99);
        }

        /* 7. CAMPOS DE ENTRADA (INPUT) */

        input {
            -webkit-appearance: none; 
            -moz-appearance: none;    
            appearance: none;         
            font-size: 16px;
            padding: 20px 0px;
            height: 56px;
            border: none;
            border-bottom: solid 1px rgba(0,0,0,.1);
            background: #fff;
            width: 280px;
            box-sizing: border-box;
            transition: all .3s linear;
            color: #000;
            font-weight: 400;
        }

        input:focus {
            border-bottom: solid 1px var(--primary-color);
            outline: 0;
            box-shadow: 0 2px 6px -8px rgba(182, 157, 230, .45);
        }

        /* 8. ETIQUETA FLOTANTE (FLOATING LABEL) Y VALIDACIÓN */

        .floating-label {
            position: relative;
            margin-bottom: 10px;
            width: 100%;
        }

        .floating-label label {
            position: absolute;
            top: calc(50% - 7px);
            left: 0;
            opacity: 0;
            transition: all .3s ease;
            padding-left: 44px;
        }

        .floating-label input {
            width: calc(100% - 44px);
            margin-left: auto;
            display: flex;
        }

        .floating-label .icon {
            position: absolute;
            top: 0;
            left: 0;
            height: 56px;
            width: 44px;
            display: flex;
        }

        .floating-label .icon svg {
            height: 30px;
            width: 30px;
            margin: auto;
            opacity: .15;
            transition: all .3s ease;
        }

        .floating-label .icon svg path {
            transition: all .3s ease;
        }

        /* Transiciones para la etiqueta flotante */
        .floating-label input:not(:placeholder-shown) {
            padding: 28px 0px 12px 0px;
        }

        .floating-label input:not(:placeholder-shown) + label {
            transform: translateY(-10px);
            opacity: .7;
        }

        /* Estilos de validación (input:valid) */
        .floating-label input:valid:not(:placeholder-shown) + label + .icon svg {
            opacity: 1;
        }

        .floating-label input:valid:not(:placeholder-shown) + label + .icon svg path {
            fill: var(--primary-color);
        }

        /* Animación de error (input:not(:valid)) */
        .floating-label input:not(:valid):not(:focus) + label + .icon {
            animation-name: shake-shake;
            animation-duration: .3s;
        }

        .floating-label input:valid:not(:placeholder-shown) + label + .icon svg path.st1 {
            fill: var(--primary-color);
        }
        
        /* 9. KEYFRAMES */

        @keyframes shake-shake {
            0% { transform: translateX(calc(-1 * var(--displacement)));}
            20% { transform: translateX(var(--displacement)); }
            40% { transform: translateX(calc(-1 * var(--displacement)));}
            60% { transform: translateX(var(--displacement));}
            80% { transform: translateX(calc(-1 * var(--displacement)));}
            100% { transform: translateX(0px);}
        }
        /* FIN: ESTILOS CSS REUTILIZADOS */
    </style>
</head>
<body>
    <div class="session">
        <div class="left">
            <svg enable-background="new 0 0 300 302.5" version="1.1" viewBox="0 0 300 302.5" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                <style type="text/css">.st01{fill:#fff;}</style>
                <path class="st01" d="m126 302.2c-2.3 0.7-5.7 0.2-7.7-1.2l-105-71.6c-2-1.3-3.7-4.4-3.9-6.7l-9.4-126.7c-0.2-2.4 1.1-5.6 2.8-7.2l93.2-86.4c1.7-1.6 5.1-2.6 7.4-2.3l125.6 18.9c2.3 0.4 5.2 2.3 6.4 4.4l63.5 110.1c1.2 2 1.4 5.5 0.6 7.7l-46.4 118.3c-0.9 2.2-3.4 4.6-5.7 5.3l-121.4 37.4zm63.4-102.7c2.3-0.7 4.8-3.1 5.7-5.3l19.9-50.8c0.9-2.2 0.6-5.7-0.6-7.7l-27.3-47.3c-1.2-2-4.1-4-6.4-4.4l-53.9-8c-2.3-0.4-5.7 0.7-7.4 2.3l-40 37.1c-1.7 1.6-3 4.9-2.8 7.2l4.1 54.4c0.2 2.4 1.9 5.4 3.9 6.7l45.1 30.8c2 1.3 5.4 1.9 7.7 1.2l52-16.2z"/>
            </svg>
        </div>
        
        <form method="POST" class="password-set" autocomplete="off">
            <h4>CONFIRMAR <span>CAMBIO</span></h4>
            <p>Introduce el código de seguridad que recibiste y tu nueva contraseña.</p>

            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="success-message">
                    <p>✅ <?php echo $message; ?></p>
                    <a href="login.php" class="discrete">Inicia Sesión</a>
                </div>
            <?php else: // Muestra el formulario si no hay mensaje de éxito ?>
                <div class="floating-label">
                    <input placeholder="Código de Seguridad / Token" type="text" name="code" id="code" required value="<?php echo $code_input; ?>">
                    <label for="token">Código / Token:</label>
                    <div class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" height="24" viewBox="0 0 24 24" width="24"><path d="M0 0h24v24H0z" fill="none"/><path fill="#010101" d="M22 10V6c0-1.1-.9-2-2-2H4c-1.1 0-2 .9-2 2v4c.6 0 1 .4 1 1s-.4 1-1 1v4c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2v-4c-.6 0-1-.4-1-1s.4-1 1-1zm-9 7c0 .5-.4 1-1 1s-1-.5-1-1v-2h2v2zm-4-7h-3V6h3v4zm7 0h-3V6h3v4zm-7 3h-3v-2h3v2zm7 0h-3v-2h3v2z"/></svg>
                    </div>
                </div>

                <div class="floating-label">
                    <input placeholder="Nueva Contraseña (min 8 caracteres)" type="password" name="new_password" id="new_password" required pattern=".{8,}">
                    <label for="new_password">Nueva Contraseña:</label>
                    <div class="icon">
                        <svg enable-background="new 0 0 24 24" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                            <style type="text/css">.st0{fill:none;} .st1{fill:#010101;}</style>
                            <rect class="st0" width="24" height="24"/>
                            <path class="st1" d="M19,21H5V9h14V21z M6,20h12V10H6V20z"/>
                            <path class="st1" d="M16.5,10h-1V7c0-1.9-1.6-3.5-3.5-3.5S8.5,5.1,8.5,7v3h-1V7c0-2.5,2-4.5,4.5-4.5s4.5,2,4.5,4.5V10z"/>
                            <path class="st1" d="m12 16.5c-0.8 0-1.5-0.7-1.5-1.5s0.7-1.5 1.5-1.5 1.5 0.7 1.5 1.5-0.7 1.5-1.5 1.5zm0-2c-0.3 0-0.5 0.2-0.5 0.5s0.2 0.5 0.5 0.5 0.5-0.2 0.5-0.5-0.2-0.5-0.5-0.5z"/>
                        </svg>
                    </div>
                </div>
                
                <div class="floating-label">
                    <input placeholder="Confirmar Nueva Contraseña" type="password" name="confirm_password" id="confirm_password" required pattern=".{8,}">
                    <label for="confirm_password">Confirmar Contraseña:</label>
                    <div class="icon">
                        <svg enable-background="new 0 0 24 24" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                            <style type="text/css">.st0{fill:none;} .st1{fill:#010101;}</style>
                            <rect class="st0" width="24" height="24"/>
                            <path class="st1" d="M19,21H5V9h14V21z M6,20h12V10H6V20z"/>
                            <path class="st1" d="M16.5,10h-1V7c0-1.9-1.6-3.5-3.5-3.5S8.5,5.1,8.5,7v3h-1V7c0-2.5,2-4.5,4.5-4.5s4.5,2,4.5,4.5V10z"/>
                            <path class="st1" d="m12 16.5c-0.8 0-1.5-0.7-1.5-1.5s0.7-1.5 1.5-1.5 1.5 0.7 1.5 1.5-0.7 1.5-1.5 1.5zm0-2c-0.3 0-0.5 0.2-0.5 0.5s0.2 0.5 0.5 0.5 0.5-0.2 0.5-0.5-0.2-0.5-0.5-0.5z"/>
                        </svg>
                    </div>
                </div>
                
                <button type="submit">Establecer Nueva Contraseña</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>