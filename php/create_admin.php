<?php

// Create or migrate the default administrator account.

// Run account setup locally from the command line.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Run this setup script from the command line.');
}

require_once "db.php";

$name = "admin";
$old_email = "admin@example.com";
$new_email = "admin@gmail.com";
$password = "admin";
$role = "admin";

// Use the plain-text password for this local demonstration account.


// Check whether the old admin exists
$check = $conn->prepare(
    "SELECT user_id
     FROM users
     WHERE email = ?"
);

$check->bind_param(
    "s",
    $old_email
);

$check->execute();

$result = $check->get_result();


if ($result->num_rows > 0) {

    // Update existing admin
    $stmt = $conn->prepare(
        "UPDATE users
         SET name = ?,
             email = ?,
             password = ?,
             role = ?
         WHERE email = ?"
    );

    $stmt->bind_param(
        "sssss",
        $name,
        $new_email,
        $password,
        $role,
        $old_email
    );

    if ($stmt->execute()) {

        echo "Admin account updated successfully.<br><br>";
        echo "Name: admin<br>";
        echo "Email: admin@gmail.com<br>";
        echo "Password: admin";

    } else {

        echo "Error updating admin: "
             . $stmt->error;
    }

    $stmt->close();

}
else {

    // Check whether new email already exists
    $checkNew = $conn->prepare(
        "SELECT user_id
         FROM users
         WHERE email = ?"
    );

    $checkNew->bind_param(
        "s",
        $new_email
    );

    $checkNew->execute();

    $newResult =
        $checkNew->get_result();


    if ($newResult->num_rows > 0) {

        echo "admin@gmail.com already exists.";

    }
    else {

        // Create admin if neither exists
        $stmt = $conn->prepare(
            "INSERT INTO users
            (name, email, password, role)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "ssss",
            $name,
            $new_email,
            $password,
            $role
        );

        if ($stmt->execute()) {

            echo "Admin account created successfully.<br><br>";
            echo "Name: admin<br>";
            echo "Email: admin@gmail.com<br>";
            echo "Password: admin";

        }
        else {

            echo "Error creating admin: "
                 . $stmt->error;
        }

        $stmt->close();
    }

    $checkNew->close();
}


$check->close();

$conn->close();

?>