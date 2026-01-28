<?php

// Valeurs par défaut
$controller = $_GET['controller'] ?? 'Auth';
$action     = $_GET['action'] ?? 'login';

// Chemin du contrôleur
$controllerFile = __DIR__ . '/../app/controllers/' . $controller . 'Controller.php';

if (!file_exists($controllerFile)) {
    die("Contrôleur introuvable");
}

require_once $controllerFile;

$controllerClass = $controller . 'Controller';

if (!class_exists($controllerClass)) {
    die("Classe contrôleur introuvable");
}

$controllerObject = new $controllerClass();

if (!method_exists($controllerObject, $action)) {
    die("Action introuvable");
}

$controllerObject->$action();
