<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<title>Registration Successful</title>


<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Segoe UI',sans-serif;
}


body{

min-height:100vh;
display:flex;
justify-content:center;
align-items:center;
background:linear-gradient(135deg,#43cea2,#185a9d);

}


.card{

background:white;
width:500px;
padding:35px;
border-radius:20px;
box-shadow:0 15px 35px rgba(0,0,0,0.2);

}


h1{

text-align:center;
color:#28a745;
margin-bottom:20px;

}


.details{

background:#f7f7f7;
padding:20px;
border-radius:15px;

}


.details p{

font-size:17px;
margin:12px 0;
color:#333;

}


span{

font-weight:bold;
color:#185a9d;

}


.btn{

display:block;
text-align:center;
margin-top:25px;
padding:12px;
background:#185a9d;
color:white;
text-decoration:none;
border-radius:10px;

}


</style>


</head>


<body>


<div class="card">


<h1>
Registration Successful ✅
</h1>


<div class="details">


<?php


$name=$_POST['name'];
$email=$_POST['email'];
$password=$_POST['password'];
$phone=$_POST['phone'];
$gender=$_POST['gender'];
$address=$_POST['address'];



if(isset($_POST['hobbies']))
{
    $hobbies=implode(", ",$_POST['hobbies']);
}
else
{
    $hobbies="No hobbies selected";
}



echo "<p><span>Name:</span> $name</p>";

echo "<p><span>Email:</span> $email</p>";

echo "<p><span>Password:</span> $password</p>";

echo "<p><span>Phone:</span> $phone</p>";

echo "<p><span>Gender:</span> $gender</p>";

echo "<p><span>Hobbies:</span> $hobbies</p>";

echo "<p><span>Address:</span> $address</p>";



?>


</div>


<a class="btn" href="index.php">
Go Back
</a>


</div>



</body>

</html>