<?php

class PermisoController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('permiso/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model = new RolFormulario();
        $roles = $model->obtenerRoles();

        $idRol = isset($_GET['id_rol']) ? (int)$_GET['id_rol'] : ($roles[0]['id_rol'] ?? 0);
        $formularios = $idRol ? $model->obtenerFormulariosConPermisos($idRol) : [];

        $titulo = 'Permisos por rol';

        $this->render('permiso/index', compact('titulo', 'roles', 'idRol', 'formularios'));
    }

    public function guardar(): void
    {
        if (!Auth::hasPermission('permiso/index', 'editar')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . 'permiso/index');
            exit;
        }

        $idRol    = (int)($_POST['id_rol'] ?? 0);
        $permisos = $_POST['permisos'] ?? [];

        if ($idRol <= 0) {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Rol inválido.'];
            header('Location: ' . APP_URL . 'permiso/index');
            exit;
        }

        $model = new RolFormulario();
        $model->guardarPermisos($idRol, $permisos);

        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Permisos actualizados.'];
        header('Location: ' . APP_URL . 'permiso/index?id_rol=' . $idRol);
        exit;
    }
}
