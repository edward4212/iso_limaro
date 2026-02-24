<?php

class AuthController extends Controller
{
    public function login(): void
    {
        // Si ya está logueado, redirige al dashboard/inicio
        if (!empty($_SESSION['user'])) {
            header('Location: ' . APP_URL);
            exit;
        }

        $error   = null;
        $empresa = null;

        // 1) Obtener datos de empresa para el login
        // Asegúrate de tener el modelo Empresa y el autoload correcto
        $empresaModel = new Empresa();
        $empresa = $empresaModel->obtenerActiva();

        // 2) Procesar login si viene POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = trim($_POST['usuario'] ?? '');
            $clave   = $_POST['clave'] ?? '';

            if ($usuario === '' || $clave === '') {
                $error = 'Usuario y contraseña son obligatorios.';
            } else {
                $userModel = new Usuario();
                $user = $userModel->buscarPorUsuario($usuario);

                if (
                    $user &&
                    password_verify($clave, $user['clave']) &&
                    (int)$user['id_estado'] === 1
                ) {
                    // Guardar datos mínimos en sesión
                    $_SESSION['user'] = [
                        'idusuario' => $user['id_usuario'],
                        'usuario'   => $user['usuario'],
                        'idrol'     => $user['id_rol'],
                    ];
                    header('Location: ' . APP_URL);
                    exit;
                } else {
                    $error = 'Credenciales inválidas o usuario inactivo.';
                }
            }
        }

        // 3) Enviar error (si lo hay) y empresa a la vista
        $this->render('auth/login', compact('error', 'empresa'));
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        header('Location: ' . APP_URL . 'auth/login');
        exit;
    }
}
