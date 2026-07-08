<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Registration</title>


<style>

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:Arial;
}


body{

    background:#f1f5f9;

    height:100vh;

    display:flex;
    justify-content:center;
    align-items:center;

}


.container{

    width:420px;

    background:white;

    padding:35px;

    border-radius:12px;

    box-shadow:0 10px 25px rgba(0,0,0,0.15);

}


h2{

    text-align:center;

    margin-bottom:25px;

}


label{

    display:block;

    margin-top:15px;

    font-weight:bold;

}


input{

    width:100%;

    padding:12px;

    margin-top:5px;

    border:1px solid #ccc;

    border-radius:6px;

}


input[type="submit"]{

    margin-top:25px;

    background:#2563eb;

    color:white;

    border:none;

    cursor:pointer;

}


input[type="submit"]:hover{

    background:#1d4ed8;

}


</style>


</head>


<body>


<div class="container">


<h2>
Student Registration
</h2>


<form action="process.php" method="POST">


<label>Name</label>

<input 
type="text"
name="name"
placeholder="Enter name"
required>


<label>Email</label>

<input 
type="email"
name="email"
placeholder="Enter email"
required>


<label>Branch</label>

<input 
type="text"
name="branch"
placeholder="Enter branch"
required>


<label>College</label>

<input 
type="text"
name="college"
placeholder="Enter college"
required>



<input 
type="submit"
name="submit"
value="Register">


</form>


</div>


</body>

</html>