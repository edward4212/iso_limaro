<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . APP_URL . 'auth/login');
            exit;
        }

        $dashboardModel = new Dashboard();
        $resumen        = $dashboardModel->getResumen();
        $solicitudes    = $dashboardModel->ultimasSolicitudes();
        $versiones      = $dashboardModel->ultimasVersiones();

        $titulo = 'Dashboard';
        $this->render('dashboard/index', compact('titulo', 'resumen', 'solicitudes', 'versiones'));
    }
}
