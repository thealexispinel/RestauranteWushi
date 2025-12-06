<?php
// request_code.php

// 1. Incluir las clases de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Asegúrate de que esta ruta sea correcta: apunta a la carpeta vendor/ generada por Composer
require __DIR__ . '/vendor/autoload.php'; 
require 'includes/db.php';     

$username_input = ''; // Variable para mantener el valor del input
$code = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitización y limpieza de la entrada
    $username = trim($_POST['username'] ?? ''); // Usar null coalescing para evitar advertencias
    $username_input = htmlspecialchars($username); 

    // Usaremos un mensaje de éxito genérico para fines de seguridad (mejor práctica)
    $generic_success_msg = "Si la cuenta existe, recibirás un correo electrónico con el código de recuperación. Revisa también tu carpeta de spam.";

    if (empty($username)) {
        $error = "Debes ingresar tu nombre de usuario o email.";
    } else {
            // 1. Buscar usuario por username o email
            // Necesitamos el ID para la DB y el EMAIL para PHPMailer
            $stmt = $pdo->prepare("SELECT id, email FROM users WHERE username = ? OR email = ?");
            // Ejecutamos con el mismo input para buscar en ambas columnas
            $stmt->execute([$username, $username]); 
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // PRÁCTICA DE SEGURIDAD: Usar mensaje genérico
                $success = $generic_success_msg; 
            } else {
                // El usuario existe.
                $user_id = $user['id'];
                $user_email = $user['email'];
                
                // 2. Generar código y programar expiración
                $code = random_int(100000, 999999);
                $expires_at = date("Y-m-d H:i:s", time() + 300); // 5 minutos de validez

                // 3. Insertar/Actualizar código en la base de datos
                // Usamos REPLACE INTO para sobrescribir códigos anteriores del mismo usuario.
                $stmt = $pdo->prepare("
                    REPLACE INTO password_codes (user_id, code, expires_at, created_at)
                    VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $stmt->execute([$user_id, $code, $expires_at]);
    
                // 4. LÓGICA DE ENVÍO DE CORREO CON PHPMailer
                $mail = new PHPMailer(true);
                
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'wushiptymail@gmail.com'; 
                    $mail->Password   = 'urogwmbbobzuymql';      
                    $mail->SMTPSecure = 'tls'; 
                    $mail->Port       = 587;                        
                    // --------------------------------------------------

                    // Configuración del Remitente y Destinatario
                    $mail->setFrom('no-reply@wushi-app.com', 'WUSHI Seguridad');
                    $mail->addAddress($user_email); 

                    // Contenido del Correo
                    $mail->isHTML(true);
                    $mail->Subject = 'Wushi Password';
                    
                    $mail->Body    = "
                        <h2>Hola,</h2>
                        <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                        <p>Tu código de un solo uso (OTP) es:</p>
                        <h1 style='background-color: #f0f0f0; padding: 20px; border-radius: 5px; text-align: center; font-size: 32px; color: #333;'>$code</h1>
                        <p>Este código caducará en 5 minutos.</p>
                        <p>Ingresa este código en la página de verificación para continuar: <a href='verify_code.php'>Verificar Código</a></p>
                    ";
                    $mail->AltBody = "Tu código de recuperación es: $code. Caduca en 5 minutos.";

                    $mail->send();
                    
                    // Mensaje final (usando el genérico para mantener la consistencia)
                    $success = $generic_success_msg;
                    
                } catch (Exception $e) {
                    // Manejo de errores de PHPMailer
                    error_log("Error al enviar el correo: {$mail->ErrorInfo}");
                    // Usar mensaje genérico para el usuario
                    $success = $generic_success_msg; 
                }

            } 
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WUSHI - Recuperar Contraseña</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend+Deca:wght@100..900&display=swap" rel="stylesheet">
    <style>
        /* 1. VARIABLES CSS NATIVAS (reemplazando a $primary de SCSS) */
        :root {
            --primary-color: rgb(182, 157, 230);
            /* El color saturado (saturate(var(--primary-color), 30%)) calculado */
            --saturated-primary: rgb(167, 137, 227); 
            --displacement: 3px; /* Reemplazando la variable SCSS del @keyframes */
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

        /* Estilo para el código de 6 dígitos */
        .recovery-code {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            padding: 10px 0;
            margin-bottom: 20px;
            border: 2px dashed rgba(182, 157, 230, .5);
            border-radius: 4px;
            letter-spacing: 5px;
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
            /* Se mantiene la URL externa para la imagen de fondo */
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
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
            font-weight: 400;
            text-align: left;
        }


        /* 5. ENLACE DISCRETO */

        a.discrete {
            color: rgba(0, 0, 0, .4);
            font-size: 14px;
            border-bottom: solid 1px rgba(0, 0, 0, .0);
            padding-bottom: 4px;
            margin-left: auto;
            font-weight: 300;
            transition: all .3s ease;
            margin-top: 40px;
            text-decoration: none;
        }

        a.discrete:hover {
            border-bottom: solid 1px rgba(0, 0, 0, .2);
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
        
        <form method="POST" class="recovery-request" autocomplete="off">
            <h4>RECUPERAR <span>CONTRASEÑA</span></h4>
            <p>Introduce tu nombre de usuario o correo electrónico para recibir un código de recuperación de 6 dígitos.</p>

            <?php if ($error): ?>
                <p class="error-message">⚠️ <?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <p>🎉
                        <?php 
                            // Muestra el mensaje de éxito genérico después de intentar enviar el correo.
                            echo $success; 
                        ?>
                    </p>
                    <p>Una vez que recibas el correo, haz clic <a href="verify_code.php">aquí para verificar el código</a>.</p>
                </div>
            <?php endif; ?>

            <?php if (!$success): // Muestra el formulario solo si NO hay mensaje de éxito (o error, pero no ambos) ?>
                <div class="floating-label">
                    <input placeholder="Tu Nombre de Usuario o Correo" type="text" name="username" id="username" required value="<?php echo $username_input; ?>">
                    <label for="username">Nombre de Usuario o Correo:</label>
                    <div class="icon">
                        <svg enable-background="new 0 0 100 100" version="1.1" viewBox="0 0 100 100" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                            <style type="text/css">.st0{fill:none;}</style>
                            <g transform="translate(0 -952.36)">
                                <path d="m50 972.36c-11.4 0-20.6 9.2-20.6 20.6 0 11.4 9.2 20.6 20.6 20.6s20.6-9.2 20.6-20.6c0-11.4-9.2-20.6-20.6-20.6zm-17.6 57.5c-9.2 0-16.7 7.5-16.7 16.7v10.7h68.6v-10.7c0-9.2-7.5-16.7-16.7-16.7h-35.2z"/>
                            </g>
                            <rect class="st0" width="100" height="100"/>
                        </svg>
                    </div>
                </div>
                
                <button type="submit">Generar Código</button>
                <a href="login.php" class="discrete">Volver al Inicio de Sesión</a>
            <?php endif; ?>
        </form>
    </div>
</body>

<script>
let seconds = 300;
const redirectUrl = "verify_code.php";

const timerInterval = setInterval(() => {
    seconds--;

    const timerElement = document.getElementById("timer");
    if (timerElement) {
        timerElement.textContent = seconds;
    }

    if (seconds <= 0) {
        clearInterval(timerInterval);
        window.location.href = redirectUrl;
    }
}, 1000);
</script>


</html>