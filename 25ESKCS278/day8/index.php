<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Registration Form</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Segoe UI', sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:linear-gradient(135deg,#667eea,#764ba2);
        }

        .container{
            width:450px;
            background:white;
            padding:35px;
            border-radius:20px;
            box-shadow:0 15px 35px rgba(0,0,0,0.2);
        }

        h2{
            text-align:center;
            margin-bottom:25px;
            color:#333;
            font-size:30px;
        }

        .input-box{
            margin-bottom:18px;
        }

        label{
            display:block;
            margin-bottom:7px;
            color:#444;
            font-weight:600;
        }

        input, select, textarea{
            width:100%;
            padding:12px;
            border:1px solid #ddd;
            border-radius:10px;
            outline:none;
            font-size:15px;
            transition:0.3s;
        }

        input:focus,select:focus,textarea:focus{
            border-color:#667eea;
            box-shadow:0 0 8px rgba(102,126,234,0.3);
        }


        .hobbies{
            display:flex;
            gap:15px;
            flex-wrap:wrap;
        }

        .hobbies label{
            font-weight:normal;
        }

        button{
            width:100%;
            padding:14px;
            border:none;
            border-radius:12px;
            background:linear-gradient(135deg,#667eea,#764ba2);
            color:white;
            font-size:18px;
            cursor:pointer;
            margin-top:15px;
            transition:0.3s;
        }

        button:hover{
            transform:translateY(-3px);
            box-shadow:0 10px 20px rgba(102,126,234,0.4);
        }


    </style>

</head>

<body>


<div class="container">

<h2>Create Account</h2>


<form action="process.php" method="POST">


<div class="input-box">
<label>Full Name</label>
<input type="text" name="name" placeholder="Enter your name" required>
</div>


<div class="input-box">
<label>Email Address</label>
<input type="email" name="email" placeholder="Enter your email" required>
</div>


<div class="input-box">
<label>Password</label>
<input type="password" name="password" placeholder="Enter password" required>
</div>


<div class="input-box">
<label>Phone Number</label>
<input type="number" name="phone" placeholder="Enter phone number" required>
</div>


<div class="input-box">
<label>Gender</label>

<select name="gender">

<option value="Male">Male</option>
<option value="Female">Female</option>
<option value="Other">Other</option>

</select>

</div>



<div class="input-box">

<label>Hobbies</label>

<div class="hobbies">

<label>
<input type="checkbox" name="hobbies[]" value="Coding">
Coding
</label>


<label>
<input type="checkbox" name="hobbies[]" value="Gaming">
Gaming
</label>


<label>
<input type="checkbox" name="hobbies[]" value="Dancing">
Dancing
</label>


<label>
<input type="checkbox" name="hobbies[]" value="Reading">
Reading
</label>


</div>

</div>



<div class="input-box">

<label>Address</label>

<textarea name="address" rows="3" placeholder="Enter your address"></textarea>

</div>


<button type="submit">
Register Now
</button>


</form>


</div>


</body>
</html>