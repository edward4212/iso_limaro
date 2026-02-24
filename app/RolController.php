<?php

class RolController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('rol/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model = new Rol();
        $lista = $model->obtenerTodos();
        $titulo = 'Roles';

        $this->render('rol/index', compact('titulo', 'lista'));
    }

    public function crear(): void
    {
        if (!Auth::hasPermission('rol/crear', 'crear')) {
            header('Location: ' . APP_URL . 'rol/index');
            exit;
        }

        $model   = new Rol();
        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'rol'       => trim($_POST['rol'] ?? ''),
                'id_estado' => (int)($_POST['id_estado'] ?? 1),
            ];

            if ($model->crear($data)) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Rol creado correctamente.',
                ];
                header('Location: ' . APP_URL . 'rol/index');
                exit;
            }

            $mensaje = 'No se pudo crear el rol.';
        }

        $titulo = 'Crear rol';
        $this->render('rol/crear', compact('titulo', 'mensaje'));
    }

    public function editar($id): void
    {
        if (!Auth::hasPermission('rol/editar', 'editar')) {
            header('Location: ' . APP_URL . 'rol/index');
            exit;
        }

        $id      = (int)$id;
        $model   = new Rol();
        $mensaje = null;

        $rol = $model->obtenerPorId($id);
        if (!$rol) {
            header('Location: ' . APP_URL . 'rol/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'rol'       => trim($_POST['rol'] ?? ''),
                'id_estado' => (int)($_POST['id_estado'] ?? 1),
            ];

            if ($model->actualizar($id, $data)) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Rol actualizado correctamente.',
                ];
                header('Location: ' . APP_URL . 'rol/index');
                exit;
            }

            $mensaje = 'No se pudo actualizar el rol.';
        }

        $titulo = 'Editar rol';

        $this->render('rol/editar', [
            'titulo'  => $titulo,
            'rol'     => $rol,
            'mensaje' => $mensaje,
        ]);
    }
}
