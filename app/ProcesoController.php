<?php

require_once __DIR__ . '/../models/Proceso.php';
require_once __DIR__ . '/../models/Macroproceso.php';

class ProcesoController extends Controller
{
    public function index($id_macroproceso = null): void
{
    if (!Auth::hasPermission('proceso/index', 'ver')) {
        header('Location: ' . APP_URL . 'dashboard/index');
        exit;
    }

    $procesoModel = new Proceso();
    $macroModel   = new Macroproceso();

    if ($id_macroproceso === null) {
        // Listado global de todos los procesos
        $lista  = $procesoModel->listarTodosConMacro();
        $titulo = 'Procesos del sistema';

        $this->render('proceso/index_global', compact('titulo', 'lista'));
        return;
    }

    // Listado filtrado por macroproceso
    $id_macroproceso = (int)$id_macroproceso;

    $macro = $macroModel->obtenerPorId($id_macroproceso);
    if (!$macro) {
        header('Location: ' . APP_URL . 'proceso/index'); // vuelve al global
        exit;
    }

    $lista  = $procesoModel->listarPorMacroproceso($id_macroproceso);
    $titulo = 'Procesos de ' . $macro['macroproceso'];

    $this->render('proceso/index', compact('titulo', 'lista', 'macro'));
}


public function crear(): void
{
    if (!Auth::hasPermission('proceso/crear', 'crear')) {
        header('Location: ' . APP_URL . 'proceso/index');
        exit;
    }

    $macroModel   = new Macroproceso();
    $procesoModel = new Proceso();

    // Solo macroprocesos activos
    $macroprocesos = $macroModel->listarActivos();

    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_macroproceso = (int)($_POST['id_macroproceso'] ?? 0);

        $data = [
            ':id_macroproceso' => $id_macroproceso,
            ':proceso'         => trim($_POST['proceso'] ?? ''),
            ':sigla_proceso'   => trim($_POST['sigla_proceso'] ?? ''),
            ':objetivo'        => trim($_POST['objetivo'] ?? ''),
            ':id_estado'       => (int)($_POST['id_estado'] ?? 1),
        ];

        if ($id_macroproceso <= 0) {
            // Error validación: SweetAlert2
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Debe seleccionar un macroproceso activo.',
            ];
        } else {
            try {
                if ($procesoModel->crear($data)) {
                    $_SESSION['flash'] = [
                        'type' => 'success',
                        'msg'  => 'Proceso creado correctamente.',
                    ];
                    header('Location: ' . APP_URL . 'proceso/index');
                    exit;
                }

                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'No se pudo crear el proceso.',
                ];
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    // UNIQUE (sigla_proceso)
                    $_SESSION['flash'] = [
                        'type' => 'error',
                        'msg'  => 'Ya existe un proceso con esa sigla.',
                    ];
                } else {
                    $_SESSION['flash'] = [
                        'type' => 'error',
                        'msg'  => 'Error al crear el proceso.',
                    ];
                }
            }
        }
    }

    $titulo = 'Crear Proceso';
    $this->render('proceso/crear', compact('titulo', 'macroprocesos', 'mensaje'));
}




    public function editar($id_proceso): void
{
    if (!Auth::hasPermission('proceso/editar', 'editar')) {
        header('Location: ' . APP_URL . 'proceso/index');
        exit;
    }

    $id_proceso = (int)$id_proceso;

    $macroModel   = new Macroproceso();
    $procesoModel = new Proceso();

    $item = $procesoModel->obtenerPorId($id_proceso);
    if (!$item) {
        header('Location: ' . APP_URL . 'proceso/index');
        exit;
    }

    $macro = $macroModel->obtenerPorId((int)$item['id_macroproceso']);
    if (!$macro) {
        header('Location: ' . APP_URL . 'proceso/index');
        exit;
    }

    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            ':id_macroproceso' => (int)($_POST['id_macroproceso'] ?? $item['id_macroproceso']),
            ':proceso'         => trim($_POST['proceso'] ?? ''),
            ':sigla_proceso'   => trim($_POST['sigla_proceso'] ?? ''),
            ':objetivo'        => trim($_POST['objetivo'] ?? ''),
            ':id_estado'       => (int)($_POST['id_estado'] ?? 1),
        ];

        if ($procesoModel->actualizar($id_proceso, $data)) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Proceso actualizado correctamente.',
            ];
            header('Location: ' . APP_URL . 'proceso/index');
            exit;
        }

        $mensaje = 'No se pudo actualizar el proceso.';
        $item    = $procesoModel->obtenerPorId($id_proceso);
        $macro   = $macroModel->obtenerPorId((int)$item['id_macroproceso']);
    }

    $titulo = 'Editar Proceso ';

    $this->render('proceso/editar', compact('titulo', 'item', 'macro', 'mensaje'));
}

}
