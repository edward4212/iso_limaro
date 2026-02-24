<?php

class FormularioController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('formulario/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model = new Formulario();
        $lista = $model->obtenerTodos();
        $titulo = 'Formularios';

        $this->render('formulario/index', compact('titulo', 'lista'));
    }

    public function crear(): void
    {
        if (!Auth::hasPermission('formulario/crear', 'crear')) {
            header('Location: ' . APP_URL . 'formulario/index');
            exit;
        }

        $model   = new Formulario();
        $padres  = $model->obtenerPadresActivos();
        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre'      => trim($_POST['nombre'] ?? ''),
                'ruta'        => trim($_POST['ruta'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'id_estado'   => (int)($_POST['id_estado'] ?? 1),
                'orden'       => (int)($_POST['orden'] ?? 0),
                'id_padre'    => $_POST['id_padre'] !== '' ? (int)$_POST['id_padre'] : null,
            ];

            if ($model->crear($data)) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Formulario creado.'];
                header('Location: ' . APP_URL . 'formulario/index');
                exit;
            }

            $mensaje = 'No se pudo crear el formulario.';
        }

        $titulo = 'Crear formulario';
        $this->render('formulario/crear', compact('titulo', 'padres', 'mensaje'));
    }

    public function editar($id): void
    {
        if (!Auth::hasPermission('formulario/editar', 'editar')) {
            header('Location: ' . APP_URL . 'formulario/index');
            exit;
        }

        $id      = (int)$id;
        $model   = new Formulario();
        $form    = $model->obtenerPorId($id);

        if (!$form) {
            header('Location: ' . APP_URL . 'formulario/index');
            exit;
        }

        $padres = $model->obtenerPadresActivos();
        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre'      => trim($_POST['nombre'] ?? ''),
                'ruta'        => trim($_POST['ruta'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'id_estado'   => (int)($_POST['id_estado'] ?? 1),
                'orden'       => (int)($_POST['orden'] ?? 0),
                'id_padre'    => $_POST['id_padre'] !== '' ? (int)$_POST['id_padre'] : null,
            ];

            if ($model->actualizar($id, $data)) {
                $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Formulario actualizado.'];
                header('Location: ' . APP_URL . 'formulario/index');
                exit;
            }

            $mensaje = 'No se pudo actualizar el formulario.';
            $form    = $model->obtenerPorId($id);
        }

        $titulo = 'Editar formulario';
        $this->render('formulario/editar', compact('titulo', 'form', 'padres', 'mensaje'));
    }
}
