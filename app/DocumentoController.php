<?php

class DocumentoController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('documento/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model  = new Documento();
        $lista  = $model->obtenerTodos();
        $titulo = 'Documentos';

        $this->render('documento/index', compact('titulo', 'lista'));
    }

    public function crear(): void
    {
        if (!Auth::hasPermission('documento/crear', 'crear')) {
            header('Location: ' . APP_URL . 'documento/index');
            exit;
        }

        $model     = new Documento();
        $procesos  = $model->obtenerProcesos();
        $tipos     = $model->obtenerTiposDocumento();
        $titulo    = 'Crear documento';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idProceso = (int)($_POST['id_proceso'] ?? 0);
            $idTipo    = (int)($_POST['id_tipo_documento'] ?? 0);
            $nombre    = trim($_POST['nombre_documento'] ?? '');
            $objetivo  = trim($_POST['objetivo_documento'] ?? '');

            $idUsuario = (int)($_SESSION['user']['idusuario'] ?? 0);

if ($idUsuario === 0) {
    $_SESSION['flash'] = [
        'type' => 'error',
        'msg'  => 'No se pudo identificar el usuario que crea el documento.',
    ];
    header('Location: ' . APP_URL . 'documento/crear');
    exit;
}

$data = [
    'id_proceso'        => $idProceso,
    'id_tipo_documento' => $idTipo,
    'nombre_documento'  => $nombre,
    'objetivo_documento'=> $objetivo,
    'id_usuario_crea'   => $idUsuario,
];

            $result = $model->crearConVersion($data);

            if ($result['id_documento'] === 0 || empty($result['codigo'])) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'No se pudo crear el documento. Verifique proceso y tipo.',
                ];
                header('Location: ' . APP_URL . 'documento/crear');
                exit;
            }

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Documento creado con código ' . $result['codigo'] . ' y versión V0.',
            ];
            header('Location: ' . APP_URL . 'documento/index');
            exit;
        }

        $this->render('documento/crear', compact('titulo', 'procesos', 'tipos'));
    }
    
    public function editar($id): void
{
    if (!Auth::hasPermission('documento/editar', 'editar')) {
        header('Location: ' . APP_URL . 'documento/index');
        exit;
    }

    $id     = (int)$id;
    $model  = new Documento();
    $item   = $model->obtenerPorId($id);

    if (!$item) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'msg'  => 'Documento no encontrado.',
        ];
        header('Location: ' . APP_URL . 'documento/index');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nombre   = trim($_POST['nombre_documento'] ?? '');
        $objetivo = trim($_POST['objetivo_documento'] ?? '');

        if ($nombre === '' || $objetivo === '') {
            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'Complete todos los campos.',
            ];
            header('Location: ' . APP_URL . 'documento/editar/' . $id);
            exit;
        }

        $data = [
            'nombre_documento'   => $nombre,
            'objetivo_documento' => $objetivo,
        ];

        if ($model->actualizar($id, $data)) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Documento actualizado correctamente.',
            ];
            header('Location: ' . APP_URL . 'documento/index');
            exit;
        }

        $_SESSION['flash'] = [
            'type' => 'error',
            'msg'  => 'No se pudo actualizar el documento.',
        ];
        header('Location: ' . APP_URL . 'documento/editar/' . $id);
        exit;
    }

    $titulo = 'Editar documento';
    $this->render('documento/editar', compact('titulo', 'item'));
}

}
