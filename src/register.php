<?php
session_start();

// Incluir la conexión a la base de datos
require 'includes/db.php'; 

// Comprobar si el usuario ya está logeado
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$username = ''; 
$email = '';
$succes_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. Limpieza y Sanitización de Inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']); 
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2. Validación de Campos
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Formato de correo electrónico inválido.';
    } elseif ($password !== $confirm_password) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (strlen($password) < 8 || strlen($password) > 20) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            // 3. Verificar si el usuario o email ya existen
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                $error = 'El nombre de usuario o el correo electrónico ya está registrado.';
            } else {
                // 4. Hashing de la Contraseña y Registro
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $hashed_password
                ]);
                
                // Registro exitoso, redirigir a login
                header('Location: login.php?registration=success');
                exit();
            }

        } catch (PDOException $e) {
            $error = 'Error de base de datos durante el registro.';
            // En un entorno de desarrollo: $error = 'Error de base de datos: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro WUSHI</title>
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
            font-weight: <weight>;
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
            background-image: url("https://i.pinimg.com/1200x/ee/b1/c2/eeb1c27b74c883741dda7ec8284e1a08.jpg"); 
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
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0px 2px 6px -1px rgba(0,0,0,.12);
        }

        .left {
            width: 220px;
            height: auto;
            min-height: 100%;
            position: relative;
            /* Se mantiene la URL externa para la imagen de fondo */
            background-image: url("https://i.pinimg.com/1200x/be/3e/ae/be3eaee933dca4b8587f827f2baf5672.jpg");
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
            background: #ffffff;
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
            max-width: 280px; /* Aumentado para el texto del registro */
            margin-bottom: 20px;
        }
        
        /* Estilo para mensajes de error/éxito */
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
        
        .success-message {
            color: #1b5e20;
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
            font-weight: 400;
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
            margin-top: 10px; /* Ajustado para mejor espaciado en el formulario más largo */
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
            margin-top: 6px;
            margin-bottom: 15px;
            background-color: #F26E50;
            color: #fff;
            font-size: 14px;
            margin-left: auto;
            font-weight: 500;
            box-shadow: 0px 2px 6px -1px rgba(0,0,0,.13);
            border: none;
            transition: all .3s ease;
            outline: 0;
            cursor: pointer;
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
        
        /* Corregir path en el candado para que use el color de la variable primaria si es válido */
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
    </style>
</head>
<body>
    
    <div class="session">
        <div class="left">
            <!-- Icono SVG (se mantiene sin cambios) -->
            <svg enable-background="new 0 0 300 302.5" version="1.1" viewBox="0 0 300 302.5" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                <style type="text/css">.st01{fill:#fff;}</style>
                <path class="st01" d="m126 302.2c-2.3 0.7-5.7 0.2-7.7-1.2l-105-71.6c-2-1.3-3.7-4.4-3.9-6.7l-9.4-126.7c-0.2-2.4 1.1-5.6 2.8-7.2l93.2-86.4c1.7-1.6 5.1-2.6 7.4-2.3l125.6 18.9c2.3 0.4 5.2 2.3 6.4 4.4l63.5 110.1c1.2 2 1.4 5.5 0.6 7.7l-46.4 118.3c-0.9 2.2-3.4 4.6-5.7 5.3l-121.4 37.4zm63.4-102.7c2.3-0.7 4.8-3.1 5.7-5.3l19.9-50.8c0.9-2.2 0.6-5.7-0.6-7.7l-27.3-47.3c-1.2-2-4.1-4-6.4-4.4l-53.9-8c-2.3-0.4-5.7 0.7-7.4 2.3l-40 37.1c-1.7 1.6-3 4.9-2.8 7.2l4.1 54.4c0.2 2.4 1.9 5.4 3.9 6.7l45.1 30.8c2 1.3 5.4 1.9 7.7 1.2l52-16.2z"/>
            </svg>
        </div>
        
        <form action="register.php" method="POST" class="sign-up" autocomplete="off">
            <h4>REGÍSTRATE EN <span>WUSHI</span></h4>
            <p>¡Únete a la plataforma y disfruta de todo lo que tenemos preparado para ti!</p>
            
            <?php if ($error): ?>
                <p class="error-message"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <!-- CAMPO DE NOMBRE DE USUARIO -->
            <div class="floating-label">
                <input placeholder="Nombre de Usuario" type="text" name="username" id="username" autocomplete="off" value="<?php echo htmlspecialchars($username); ?>" required>
                <label for="username">Nombre de Usuario:</label>
                <div class="icon">
                    <!-- SVG de Usuario (Añadido) -->
                    <svg enable-background="new 0 0 50 50" version="1.1" viewBox="0 0 50 50" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                        <path d="M25,24.5c4.7,0,8.5-3.8,8.5-8.5S29.7,7.5,25,7.5S16.5,11.3,16.5,16s3.8,8.5,8.5,8.5z M25,9.5c3.6,0,6.5,2.9,6.5,6.5S28.6,22.5,25,22.5S18.5,19.6,18.5,16S21.4,9.5,25,9.5z"/>
                        <path d="M25,26.5c-7.3,0-13.3,5.9-13.3,13.3c0,0.6,0.5,1.1,1.1,1.1h24.4c0.6,0,1.1-0.5,1.1-1.1C38.3,32.4,32.4,26.5,25,26.5z M13.8,38.8c0-6.2,5-11.3,11.2-11.3s11.2,5,11.2,11.3H13.8z"/>
                    </svg>
                </div>
            </div>

            <!-- CAMPO DE EMAIL -->
            <div class="floating-label">
                <input placeholder="Correo Electrónico" type="email" name="email" id="email" autocomplete="off" value="<?php echo htmlspecialchars($email); ?>" required>
                <label for="email">Correo Electrónico:</label>
                <div class="icon">
                    <!-- SVG Email (Mantenido) -->
                    <svg enable-background="new 0 0 100 100" version="1.1" viewBox="0 0 100 100" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                        <style type="text/css">.st0{fill:none;}</style>
                        <g transform="translate(0 -952.36)">
                            <path d="m17.5 977c-1.3 0-2.4 1.1-2.4 2.4v45.9c0 1.3 1.1 2.4 2.4 2.4h64.9c1.3 0 2.4-1.1 2.4-2.4v-45.9c0-1.3-1.1-2.4-2.4-2.4h-64.9zm2.4 4.8h60.2v1.2l-30.1 22-30.1-22v-1.2zm0 7l28.7 21c0.8 0.6 2 0.6 2.8 0l28.7-21v34.1h-60.2v-34.1z"/>
                        </g>
                        <rect class="st0" width="100" height="100"/>
                    </svg>
                </div>
            </div>
            
            <!-- CAMPO DE CONTRASEÑA -->
            <div class="floating-label">
                <input placeholder="Contraseña" type="password" name="password" id="password" autocomplete="new-password" required pattern=".{8,}">
                <label for="password">Contraseña (Mín. 8 caracteres):</label>
                <div class="icon">
                    <!-- SVG Candado (Mantenido) -->
                    <svg enable-background="new 0 0 24 24" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                        <style type="text/css">.st0{fill:none;} .st1{fill:#010101;}</style>
                        <rect class="st0" width="24" height="24"/>
                        <path class="st1" d="M19,21H5V9h14V21z M6,20h12V10H6V20z"/>
                        <path class="st1" d="M16.5,10h-1V7c0-1.9-1.6-3.5-3.5-3.5S8.5,5.1,8.5,7v3h-1V7c0-2.5,2-4.5,4.5-4.5s4.5,2,4.5,4.5V10z"/>
                        <path class="st1" d="m12 16.5c-0.8 0-1.5-0.7-1.5-1.5s0.7-1.5 1.5-1.5 1.5 0.7 1.5 1.5-0.7 1.5-1.5 1.5zm0-2c-0.3 0-0.5 0.2-0.5 0.5s0.2 0.5 0.5 0.5 0.5-0.2 0.5-0.5-0.2-0.5-0.5-0.5z"/>
                    </svg>
                </div>
            </div>

            <!-- CAMPO DE CONFIRMAR CONTRASEÑA -->
            <div class="floating-label">
                <input placeholder="Confirmar Contraseña" type="password" name="confirm_password" id="confirm_password" autocomplete="new-password" required pattern=".{8,}">
                <label for="confirm_password">Confirmar Contraseña:</label>
                <div class="icon">
                    <!-- SVG Candado (Mantenido) -->
                    <svg enable-background="new 0 0 24 24" version="1.1" viewBox="0 0 24 24" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
                        <style type="text/css">.st0{fill:none;} .st1{fill:#010101;}</style>
                        <rect class="st0" width="24" height="24"/>
                        <path class="st1" d="M19,21H5V9h14V21z M6,20h12V10H6V20z"/>
                        <path class="st1" d="M16.5,10h-1V7c0-1.9-1.6-3.5-3.5-3.5S8.5,5.1,8.5,7v3h-1V7c0-2.5,2-4.5,4.5-4.5s4.5,2,4.5,4.5V10z"/>
                        <path class="st1" d="m12 16.5c-0.8 0-1.5-0.7-1.5-1.5s0.7-1.5 1.5-1.5 1.5 0.7 1.5 1.5-0.7 1.5-1.5 1.5zm0-2c-0.3 0-0.5 0.2-0.5 0.5s0.2 0.5 0.5 0.5 0.5-0.2 0.5-0.5-0.2-0.5-0.5-0.5z"/>
                    </svg>
                </div>
            </div>
            
            <button type="submit">Registrarme</button>
            <p>¿Ya tienes una cuenta? <a href="login.php" class="discrete">Iniciar Sesión</a></p>
        </form>
    </div>
</body>
</html>
