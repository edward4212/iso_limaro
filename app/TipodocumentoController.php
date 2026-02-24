<?php

class TipodocumentoController extends Controller
{
    public function index(): void
    {
        if (!Auth::hasPermission('tipodocumento/index', 'ver')) {
            header('Location: ' . APP_URL . 'dashboard/index');
            exit;
        }

        $model  = new Tipodocumento();
        $lista  = $model->obtenerTodos();
        $titulo = 'Tipos de documento';

        $this->render('tipodocumento/index', compact('titulo', 'lista'));
    }

    public function crear(): void
    {
        if (!Auth::hasPermission('tipodocumento/crear', 'crear')) {
            header('Location: ' . APP_URL . 'tipodocumento/index');
            exit;
        }

        $model   = new Tipodocumento();
        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre   = trim($_POST['tipo_documento'] ?? '');
            $sigla    = strtoupper(trim($_POST['sigla_tipo_documento'] ?? ''));
            $idEstado = 1; // ACTIVO por defecto

            if ($nombre === '' || $sigla === '') {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'Complete nombre y sigla.',
                ];
                header('Location: ' . APP_URL . 'tipodocumento/crear');
                exit;
            }

            $data = [
                'tipo_documento'       => $nombre,
                'sigla_tipo_documento' => $sigla,
                'id_estado'            => $idEstado,
            ];

            $id = $model->crear($data);

            if ($id === 0) {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'La sigla ya existe. Debe ser única.',
                ];
                header('Location: ' . APP_URL . 'tipodocumento/crear');
                exit;
            }

            $_SESSION['flash'] = [
                'type' => 'success',
                'msg'  => 'Tipo de documento creado correctamente.',
            ];
            header('Location: ' . APP_URL . 'tipodocumento/index');
            exit;
        }

        $titulo = 'Crear tipo de documento';
        $this->render('tipodocumento/crear', compact('titulo', 'mensaje'));
    }

    public function editar($id): void
    {
        if (!Auth::hasPermission('tipodocumento/editar', 'editar')) {
            header('Location: ' . APP_URL . 'tipodocumento/index');
            exit;
        }

        $id     = (int)$id;
        $model  = new Tipodocumento();
        $item   = $model->obtenerPorId($id);

        if (!$item) {
            header('Location: ' . APP_URL . 'tipodocumento/index');
            exit;
        }

        $mensaje = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nombre   = trim($_POST['tipo_documento'] ?? '');
            $sigla    = strtoupper(trim($_POST['sigla_tipo_documento'] ?? ''));
            $idEstado = (int)($_POST['id_estado'] ?? 1);

            if ($nombre === '' || $sigla === '') {
                $_SESSION['flash'] = [
                    'type' => 'error',
                    'msg'  => 'Complete nombre y sigla.',
                ];
                header('Location: ' . APP_URL . 'tipodocumento/editar/' . $id);
                exit;
            }

            $data = [
                'tipo_documento'       => $nombre,
                'sigla_tipo_documento' => $sigla,
                'id_estado'            => $idEstado,
            ];

            if ($model->actualizar($id, $data)) {
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'msg'  => 'Tipo de documento actualizado correctamente.',
                ];
                header('Location: ' . APP_URL . 'tipodocumento/index');
                exit;
            }

            $_SESSION['flash'] = [
                'type' => 'error',
                'msg'  => 'No se pudo actualizar (verifique que la sigla no esté repetida).',
            ];
            header('Location: ' . APP_URL . 'tipodocumento/editar/' . $id);
            exit;
        }

        $titulo = 'Editar tipo de documento';
        $this->render('tipodocumento/editar', compact('titulo', 'item', 'mensaje'));
    }
}
