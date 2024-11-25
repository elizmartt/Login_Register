<?php 
session_start();
if (isset($_SESSION["user"])) {
    header("Location: index.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <?php
        if (isset($_POST["submit"])) {
            $fullName = $_POST["fullname"];
            $email = $_POST["email"];
            $password = $_POST["password"];
            $passwordRepeat = $_POST["repeat_password"];
            $age = $_POST["age"];
            $gender = $_POST["gender"];
            $profilePic = $_FILES["profile_pic"]["name"];
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $errors = array();

            // Validate fields
            if (empty($fullName) || empty($password) || empty($email) || empty($passwordRepeat) || empty($age) || empty($gender) || empty($profilePic)) {
                array_push($errors, "All fields are required");
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                array_push($errors, "Email is not valid");
            }
            if (strlen($password) < 8) {
                array_push($errors, "Password must be at least 8 characters");
            }
            if ($password !== $passwordRepeat) {
                array_push($errors, "The passwords do not match");
            }
            if (!is_numeric($age) || $age < 1 || $age > 120) {
                array_push($errors, "Please enter a valid age");
            }

            // Handle profile picture upload
            $uploadDir = __DIR__ . "/uploads/";
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $targetFile = $uploadDir . basename($profilePic);
            $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
            $validExtensions = array("jpg", "jpeg", "png", "gif");

            if (!in_array($imageFileType, $validExtensions)) {
                array_push($errors, "Invalid file type for profile picture. Only JPG, JPEG, PNG, and GIF are allowed.");
            }
            if ($_FILES["profile_pic"]["size"] > 5000000) {
                array_push($errors, "Profile picture size must not exceed 5MB.");
            }

            require_once "database.php";
            $sql = "SELECT * FROM users WHERE email=?";
            $stmt = mysqli_stmt_init($conn);
            if (!mysqli_stmt_prepare($stmt, $sql)) {
                die("SQL error");
            }
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                array_push($errors, "Email already exists!");
            }

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    echo "<div class='alert alert-danger'>$error</div>";
                }
            } else {
                // Move the uploaded file to the target directory
                if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $targetFile)) {
                    $sql = "INSERT INTO users (full_name, email, password, age, gender, profile_pic) VALUES (?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_stmt_init($conn);
                    if (mysqli_stmt_prepare($stmt, $sql)) {
                        mysqli_stmt_bind_param($stmt, "ssssss", $fullName, $email, $passwordHash, $age, $gender, $profilePic);
                        mysqli_stmt_execute($stmt);
                        echo "<div class='alert alert-success'>You are registered successfully!</div>";
                    } else {
                        die("Something went wrong");
                    }
                } else {
                    echo "<div class='alert alert-danger'>Failed to upload profile picture.</div>";
                }
            }
        }
        ?>
        <form action="registration.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <input type="text" class="form-control" name="fullname" placeholder="Full Name:">
            </div>
            <div class="form-group">
                <input type="email" class="form-control" name="email" placeholder="Email:">
            </div>
            <div class="form-group">
                <input type="password" class="form-control" name="password" placeholder="Password:">
            </div>
            <div class="form-group">
                <input type="text" class="form-control" name="repeat_password" placeholder="Repeat Password:">
            </div>
            <div class="form-group">
                <input type="number" class="form-control" name="age" placeholder="Age:">
            </div>
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select class="form-control" name="gender">
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="profile_pic">Upload Profile Picture:</label>
                <input type="file" class="form-control" name="profile_pic" id="profile_pic">
            </div>
            <div class="form-btn">
                <input type="submit" class="btn btn-primary" value="Register" name="submit">
            </div>
        </form>
        <div><p>Already Registered? <a href="login.php">Login Here</a></p></div>
    </div>
</body>
</html>
