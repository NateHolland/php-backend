<?php

// Set the content type to application/json
header("Content-Type: application/json; charset=UTF-8");

// Handle the request
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

require_once 'ShotQuality.php';

// Simple routing
if ($method === 'POST' && preg_match('/^\/upload\/?$/', $request_uri)) {
    // Get the request body
    $data = json_decode(file_get_contents('php://input'), true);

    // --- Data Validation ---
    $requiredFields = ['arcQuality', 'shortQuality', 'longQuality', 'userId'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field])) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        http_response_code(400); // Bad Request
        echo json_encode([
            "message" => "Missing required fields.",
            "missing" => $missingFields
        ]);
        exit;
    }

    // Additional type checks
    if (!is_numeric($data['arcQuality']) || !is_numeric($data['shortQuality']) || !is_numeric($data['longQuality'])) {
        http_response_code(400);
        echo json_encode(["message" => "arcQuality, shortQuality, and longQuality must be numbers."]);
        exit;
    }

    if (isset($data['brick']) && !is_bool($data['brick'])) {
        http_response_code(400);
        echo json_encode(["message" => "Field 'brick' must be a boolean."]);
        exit;
    }
    // --- End Validation ---


    // Create a new ShotQuality object
    try {
        $shot = new ShotQuality(
            (float)$data['arcQuality'],
            (float)$data['shortQuality'],
            (float)$data['longQuality'],
            $data['brick'] ?? null, // Handle nullable brick
            (string)$data['userId']
        );
    } catch (TypeError $e) {
        http_response_code(400);
        echo json_encode(["message" => "Invalid data types provided. " . $e->getMessage()]);
        exit;
    }


    // --- Store the data in the database ---
    $pdo = null; // Make it available in the catch block
    try {
        $pdo = new PDO('sqlite:' . __DIR__ . '/database.sqlite');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Begin transaction for data integrity
        $pdo->beginTransaction();

        // 1. Check if user exists. If not, create them.
        $userId = $shot->userId;
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);

        if ($stmt->fetchColumn() === false) {
            // User does not exist, so insert them.
            $userStmt = $pdo->prepare("INSERT INTO users (id) VALUES (:id)");
            $userStmt->execute([':id' => $userId]);
        }

        // 2. Insert the shot record.
        $shotSql = "INSERT INTO shots (arcQuality, shortQuality, longQuality, brick, timestamp, user_id)
                    VALUES (:arcQuality, :shortQuality, :longQuality, :brick, :timestamp, :user_id)";

        $shotStmt = $pdo->prepare($shotSql);

        // Bind values from the shot object
        $shotStmt->bindValue(':arcQuality', $shot->arcQuality);
        $shotStmt->bindValue(':shortQuality', $shot->shortQuality);
        $shotStmt->bindValue(':longQuality', $shot->longQuality);

        if ($shot->brick === null) {
            $shotStmt->bindValue(':brick', null, PDO::PARAM_NULL);
        } else {
            $shotStmt->bindValue(':brick', (int)$shot->brick, PDO::PARAM_INT);
        }

        $shotStmt->bindValue(':timestamp', $shot->timestamp, PDO::PARAM_INT);
        $shotStmt->bindValue(':user_id', $userId, PDO::PARAM_STR);

        $shotStmt->execute();

        $newShotId = $pdo->lastInsertId();

        // If all queries were successful, commit the transaction
        $pdo->commit();

        http_response_code(201); // Created
        echo json_encode([
            "message" => "Shot data saved to database successfully.",
            "shotId" => $newShotId,
            "data" => $shot
        ]);

    } catch (PDOException $e) {
        // If an error occurred, roll back the transaction
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        http_response_code(500); // Internal Server Error
        echo json_encode([
            "message" => "Failed to write to database.",
            "error" => $e->getMessage()
        ]);
        exit;
    }

} else {
    http_response_code(404);
    echo json_encode(["message" => "Endpoint not found."]);
}
