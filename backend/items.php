 <?php
// items.php

function getItems($pdo) {
    $stmt = $pdo->query("SELECT * FROM items");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function addItem($pdo, $name, $description) {
    $stmt = $pdo->prepare("INSERT INTO items (name, description) VALUES (:name, :description)");
    $stmt->execute(['name' => $name, 'description' => $description]);
    return $pdo->lastInsertId();
}

