<?php

class HomeController extends Controller
{
    public function limarocloud(): void
{
    // Simplemente redirige al dashboard o a donde quieras
    header('Location: ' . APP_URL . 'dashboard/index');
    exit;
}

}
