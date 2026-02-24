<?php

// Opción 3: relativa al directorio del controlador
require_once __DIR__ . '/../models/Macroproceso.php';

class MacroprocesoController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('macroproceso/index', 'ver')) {
            header('Location: ' . APP_URL . 'home/index');
            exit;
        }

        $model = new Macroproceso();
        $lista = $model->listar();

        $titulo = 'Macroprocesos';

        $this->render('macroproceso/index', compact('titulo', 'lista'));
    }

    public function crear(): void
{
    if (!Auth::hasPermission('macroproceso/crear', 'crear')) {
        header('Location: ' . APP_URL . 'macroproceso/index');
        exit;
    }

    $model   = new Macroproceso();
    $mensaje = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = [
            ':macroproceso' => trim($_POST['macroproceso'] ?? ''),
            ':objetivo'     => trim($_POST['objetivo'] ?? ''),
            ':id_estado'    => (int)($_POST['id_estado'] ?? 1),
        ];

        try {
            if ($model->crear($data)) {
                // SweetAlert2 (flash)
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Macroproceso creado correctamente.',
                ];

                header('Location: ' . APP_URL . 'macroproceso/index');
                exit;
            }

            $mensaje = 'No se pudo crear el macroproceso.';
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                // Violación de UNIQUE (uq_macroproceso)
                $mensaje = 'Ya existe un macroproceso con ese nombre.';
            } else {
                $mensaje = 'Error al crear el macroproceso.';
            }
        }
    }

    $titulo = 'Nuevo macroproceso';
    $this->render('macroproceso/crear', compact('titulo', 'mensaje'));
}


    public function editar($id): void
    {
        if (!Auth::hasPermission('macroproceso/editar', 'editar')) {
            header('Location: ' . APP_URL . 'macroproceso/index');
            exit;
        }

        $id      = (int)$id;
        $model   = new Macroproceso();
        $mensaje = null;

        // 1) Cargar el registro desde la BD
        $macro = $model->obtenerPorId($id);
        if (!$macro) {
            header('Location: ' . APP_URL . 'macroproceso/index');
            exit;
        }

        // 2) Si envían el formulario, actualizar
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                ':macroproceso' => trim($_POST['macroproceso'] ?? ''),
                ':objetivo'     => trim($_POST['objetivo'] ?? ''),
                ':id_estado'    => (int)($_POST['id_estado'] ?? 1),
            ];

            // En editar()
if ($model->actualizar($id, $data)) {
    $_SESSION['flash'] = [
        'type' => 'success',
        'msg'  => 'Macroproceso actualizado correctamente.',
    ];
    header('Location: ' . APP_URL . 'macroproceso/index');
    exit;
} else {
    $_SESSION['flash'] = [
        'type' => 'error',
        'msg'  => 'No se pudo actualizar el macroproceso.',
    ];
}


            // Recargar datos actualizados
            $macro = $model->obtenerPorId($id);
        }

        // 3) Enviar datos a la vista
        $titulo = 'Editar macroproceso';

        $this->render('macroproceso/editar', [
            'titulo'  => $titulo,
            'macro'   => $macro,   // clave distinta a $item
            'mensaje' => $mensaje,
        ]);
    }
}
