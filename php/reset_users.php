<?php

// Create or reset the three demonstration accounts.

// Run account setup locally from the command line.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this setup script from the command line.');
}

require_once "db.php";

$users = [
    [
        "name" => "user",
        "email" => "user@gmail.com",
        "password" => "user",
        "role" => "student"
    ],
    [
        "name" => "Sarah Perera",
        "email" => "sarah@gmail.com",
        "password" => "sarah",
        "role" => "counselor"
    ],
    [
        "name" => "admin",
        "email" => "admin@gmail.com",
        "password" => "admin",
        "role" => "admin"
    ]
];

foreach ($users as $user) {

    // Save each demonstration password as the literal value defined above.

    // Check whether email already exists
    $check = $conn->prepare(
        "SELECT user_id FROM users WHERE email = ?"
    );

    $check->bind_param("s", $user["email"]);
    $check->execute();

    $result = $check->get_result();

    if ($result->num_rows > 0) {

        // Update existing account
        $row = $result->fetch_assoc();
        $userId = $row["user_id"];

        $stmt = $conn->prepare(
            "UPDATE users
             SET name = ?, password = ?, role = ?
             WHERE user_id = ?"
        );

        $stmt->bind_param(
            "sssi",
            $user["name"],
            $user["password"],
            $user["role"],
            $userId
        );

        $stmt->execute();

        echo "Updated: " . $user["email"] . "<br>";

        $stmt->close();

    } else {

        // Create new account
        $stmt = $conn->prepare(
            "INSERT INTO users (name, email, password, role)
             VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssss",
            $user["name"],
            $user["email"],
            $user["password"],
            $user["role"]
        );

        $stmt->execute();

        echo "Created: " . $user["email"] . "<br>";

        $stmt->close();
    }

    // Ensure a new demonstration student can book appointments.
    if ($user['role'] === 'student') {
        $profile = $conn->prepare("INSERT INTO students (user_id)
            SELECT user_id FROM users WHERE email = ?
            AND NOT EXISTS (SELECT 1 FROM students WHERE students.user_id = users.user_id)");
        $profile->bind_param('s', $user['email']);
        $profile->execute();
    }
    $check->close();
}

$conn->close();

echo "<br><strong>All accounts have been updated successfully.</strong>";

?>