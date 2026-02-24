<?php

class UsuarioController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('usuario/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model  = new Usuario();
        $lista  = $model->obtenerTodos();
        $titulo = 'Usuarios del sistema';

        $this->render('usuario/index', compact('titulo', 'lista'));
    }

   public function crear(): void
{
    if (!Auth::hasPermission('usuario/crear', 'crear')) {
        header('Location: ' . APP_URL . 'usuario/index');
        exit;
    }

    $usuarioModel  = new Usuario();
    $empleadoModel = new Empleado();
    $rolModel      = new Rol(); // nuevo

    $empleados = $empleadoModel->obtenerActivos();
    $roles     = $rolModel->obtenerActivos(); // nuevo
    $mensaje   = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario     = trim($_POST['usuario'] ?? '');
        $idRol       = (int)($_POST['id_rol'] ?? 0);
        $idEmpleado  = (int)($_POST['id_empleado'] ?? 0);
        $idEstado    =  3;

        if ($usuario === '' || $idRol <= 0 || $idEmpleado <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Complete todos los campos obligatorios.',
            ];
            header('Location: ' . APP_URL . 'usuario/crear');
            exit;
        }

        // contraseña aleatoria
        $plainPassword = bin2hex(random_bytes(4)); // 8 caracteres
        $hash          = password_hash($plainPassword, PASSWORD_DEFAULT);

        $data = [
            'usuario'     => $usuario,
            'clave'       => $hash,
            'id_rol'      => $idRol,
            'id_empleado' => $idEmpleado,
            'id_estado'   => $idEstado,
        ];

        $idUsuario = $usuarioModel->crear($data);

        if ($idUsuario === 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Ya existe un usuario con ese nombre de acceso.',
            ];
            header('Location: ' . APP_URL . 'usuario/crear');
            exit;
        }

        // datos para correo
        $infoEmpleado = $empleadoModel->obtenerConEmpresa($idEmpleado);
        if ($infoEmpleado) {
            $this->enviarCorreoCreacionUsuario(
                $infoEmpleado['correo_empleado'],
                $infoEmpleado['nombre_completo'],
                $infoEmpleado['nombre_empresa'] ?? 'LIMARO',
                $usuario,
                $plainPassword
            );
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => 'Usuario creado y correo enviado (si el correo es válido).',
        ];
        header('Location: ' . APP_URL . 'usuario/index');
        exit;
    }

    $titulo = 'Crear usuario';
    $this->render('usuario/crear', compact('titulo', 'empleados', 'roles', 'mensaje'));
}

    /**
     * Enviar correo de creación de usuario con mail() simple.
     */
    private function enviarCorreoCreacionUsuario(
        string $correo,
        string $nombreEmpleado,
        string $nombreEmpresa,
        string $usuario,
        string $claveInicial
    ): void {
        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $asunto = 'Creación de usuario en LIMARO - ' . $nombreEmpresa;

        $mensaje = "Estimado(a) {$nombreEmpleado},\n\n"
            . "Su usuario dentro del sistema LIMARO - {$nombreEmpresa} ha sido creado con la siguiente información:\n\n"
            . "Usuario: {$usuario}\n"
            . "Contraseña Inicial: {$claveInicial}\n\n"
            . "Para ingresar por primera vez, el sistema solicitará activar su usuario: debe iniciar sesión y realizar el cambio de contraseña.\n"
            . "No olvide guardarla en un sitio seguro.\n\n"
            . "Bienvenido(a).\n\n"
            . "LIMARO";

        $headers  = "From: no-reply@tudominio.com\r\n";
        $headers .= "Reply-To: no-reply@tudominio.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        @mail($correo, $asunto, $mensaje, $headers);
    }
    
public function editar($id): void
{
    if (!Auth::hasPermission('usuario/editar', 'editar')) {
        header('Location: ' . APP_URL . 'usuario/index');
        exit;
    }

    $id           = (int)$id;
    $usuarioModel = new Usuario();
    $rolModel     = new Rol();

    // obtener usuario con nombre del empleado (ajusta obtenerPorId)
    $usuario = $usuarioModel->obtenerPorId($id);
    if (!$usuario) {
        header('Location: ' . APP_URL . 'usuario/index');
        exit;
    }

    $roles   = $rolModel->obtenerActivos();
    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login    = trim($_POST['usuario'] ?? '');
        $idRol    = (int)($_POST['id_rol'] ?? 0);
        $idEstado = (int)($_POST['id_estado'] ?? 1);

        if ($login === '' || $idRol <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Complete todos los campos obligatorios.',
            ];
            header('Location: ' . APP_URL . 'usuario/editar/' . $id);
            exit;
        }

        $data = [
            'usuario'   => $login,
            'id_rol'    => $idRol,
            'id_estado' => $idEstado,
            // no se toca id_empleado
        ];

        if ($usuarioModel->actualizar($id, $data)) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Usuario actualizado correctamente.',
            ];
            header('Location: ' . APP_URL . 'usuario/index');
            exit;
        }

        $mensaje = 'No se pudo actualizar el usuario.';
        $usuario = $usuarioModel->obtenerPorId($id);
    }

    $titulo = 'Editar usuario';
    // ya no se pasan empleados
    $this->render('usuario/editar', compact('titulo', 'usuario', 'roles', 'mensaje'));
}

public function restablecer(): void
{
    if (!Auth::hasPermission('usuario/restablecer', 'editar')) {
        header('Location: ' . APP_URL . 'usuario/index');
        exit;
    }

    $usuarioModel  = new Usuario();
    $empleadoModel = new Empleado();

    $usuarios = $usuarioModel->listarBasico();
    $mensaje  = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $idUsuario = (int)($_POST['id_usuario'] ?? 0);

        if ($idUsuario <= 0) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Debe seleccionar un usuario.',
            ];
            header('Location: ' . APP_URL . 'usuario/restablecer');
            exit;
        }

        // nueva contraseña aleatoria
        $plainPassword = bin2hex(random_bytes(4)); // 8 caracteres
        $hash          = password_hash($plainPassword, PASSWORD_DEFAULT);

        if (!$usuarioModel->actualizarClave($idUsuario, $hash)) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'No se pudo actualizar la contraseña.',
            ];
            header('Location: ' . APP_URL . 'usuario/restablecer');
            exit;
        }

        // datos para correo
        $infoEmpleado = $empleadoModel->obtenerPorUsuario($idUsuario);
        if ($infoEmpleado) {
            $login = $this->obtenerLoginPorId($usuarioModel, $idUsuario);
        
            $this->enviarCorreoRestablecerClave(
                $infoEmpleado['correo_empleado'],
                $infoEmpleado['nombre_completo'],
                $infoEmpleado['nombre_empresa'] ?? 'LIMARO',
                $login,
                $plainPassword
            );
        }

        $_SESSION['flash'] = [
            'type' => 'success',
            'msg'  => 'Contraseña restablecida y correo enviado (si el correo es válido).',
        ];
        header('Location: ' . APP_URL . 'usuario/index');
        exit;
    }

    $titulo = 'Restablecer contraseña';
    $this->render('usuario/restablecer', compact('titulo', 'usuarios', 'mensaje'));
}

/**
 * Obtener nombre de usuario (login) por id_usuario.
 */
private function obtenerLoginPorId(Usuario $usuarioModel, int $idUsuario): string
{
    $sql = "SELECT usuario FROM usuario WHERE id_usuario = :id";
    $st  = Database::getInstance()->getConnection()->prepare($sql);
    $st->execute([':id' => $idUsuario]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row['usuario'] ?? '';
}

private function enviarCorreoRestablecerClave(
    string $correo,
    string $nombreEmpleado,
    string $nombreEmpresa,
    string $usuario,
    string $claveNueva
): void {
    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $asunto = 'Restablecimiento de contraseña en LIMARO - ' . $nombreEmpresa;

    $mensaje = "Estimado(a) {$nombreEmpleado},\n\n"
        . "Se ha restablecido su contraseña en el sistema LIMARO - {$nombreEmpresa}.\n\n"
        . "Datos de acceso actualizados:\n"
        . "Usuario: {$usuario}\n"
        . "Nueva contraseña: {$claveNueva}\n\n"
        . "Al ingresar nuevamente, el sistema le solicitará cambiar esta contraseña.\n"
        . "No olvide guardarla en un sitio seguro.\n\n"
        . "Saludos,\n\n"
        . "LIMARO";

    $headers  = "From: no-reply@tudominio.com\r\n";
    $headers .= "Reply-To: no-reply@tudominio.com\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    @mail($correo, $asunto, $mensaje, $headers);
}



}
