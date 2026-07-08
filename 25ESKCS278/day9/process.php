<?php

include("db_connect.php");

$message="";
$class="";

if(isset($_POST['submit']))
{
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $branch = trim($_POST['branch']);
    $college = trim($_POST['college']);

    // Check empty fields
    if(empty($name) || empty($email) || empty($branch) || empty($college))
    {
        $message="All fields are required";
        $class="error";
    }
    // Check email
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))
    {
        $message="Invalid Email Address";
        $class="error";
    }
    else
    {
        // Check duplicate email
        $check = "SELECT * FROM students WHERE email='$email'";
        $result=mysqli_query($conn,$check);

        if(mysqli_num_rows($result)>0)
        {
            $message="Student is already registered";
            $class="error";
        }
        else
        {
            // The query is structured perfectly to allow Auto-Increment
            $sql="INSERT INTO students (name,email,branch,college) VALUES ('$name','$email','$branch','$college')";

            if(mysqli_query($conn,$sql))
            {
                $message="Registration Successful!";
                $class="success";
            }
            else
            {
                $message="Database Error: ".mysqli_error($conn);
                $class="error";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Status</title>
    <style>
        body{
            height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:#f1f5f9;
            font-family:Arial;
        }
        .box{
            background:white;
            padding:35px;
            width:400px;
            text-align:center;
            border-radius:12px;
            box-shadow:0 10px 25px rgba(0,0,0,.15);
        }
        .success{
            background:#dcfce7;
            color:green;
            padding:15px;
            border-radius:8px;
            font-weight:bold;
        }
        .error{
            background:#fee2e2;
            color:red;
            padding:15px;
            border-radius:8px;
            font-weight:bold;
        }
        a{
            display:inline-block;
            margin-top:20px;
            background:#2563eb;
            color:white;
            padding:10px 20px;
            text-decoration:none;
            border-radius:6px;
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="<?php echo $class; ?>">
            <?php echo $message; ?>
        </div>
        <a href="index.php">Go Back</a>
    </div>
</body>
</html>
