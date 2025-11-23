<?php
// index.php

// Configurar cabeceras CORS para TODAS las solicitudes
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder a solicitudes OPTIONS para cumplir con CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configurar Content-Type para las respuestas JSON
header("Content-Type: application/json");

require 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    require 'items.php';
    echo json_encode(getItems($pdo));
} elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!empty($data['name']) && !empty($data['description'])) {
        require 'items.php';
        $id = addItem($pdo, $data['name'], $data['description']);
        echo json_encode(['id' => $id, 'message' => 'Item added']);
    } else {
        echo json_encode(['message' => 'Name and description are required']);
    }
} else {
    http_response_code(405);
    echo json_encode(['message' => 'Method Not Allowed']);
}
