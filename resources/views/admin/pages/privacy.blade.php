<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Privacy Policy</title>

<style>
body{
    font-family: Arial, sans-serif;
    background: #f7f7f7;
    margin:0;
    padding: 40px 20px;
}
.container{
    max-width: 800px;
    margin:auto;
    background:#fff;
    padding:40px;
    border-radius:8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
h1{margin-top:0;}
h2{margin-top:28px;}
p{line-height:1.7;}
.footer{
    margin-top:40px;
    font-size:14px;
    color:#666;
}
</style>
</head>
<body>

<div class="container">

<h1>Privacy Policy</h1>
<p>Last updated: {{ date('Y-m-d') }}</p>

<h2>Introduction</h2>
<p>This application is an internal administrative dashboard used for managing company operations.</p>

<h2>Information We Collect</h2>
<p>The app may collect basic information such as login credentials and system usage required for authentication and functionality.</p>

<h2>How We Use Information</h2>
<p>The collected data is used for internal administration and system management.</p>

<h2>Data Sharing</h2>
<p>We do not sell or share user data with third parties.</p>

<h2>Security</h2>
<p>We implement reasonable security measures to protect stored data.</p>

<h2>Contact</h2>
<p>If you have questions, contact us at <strong>admin@tullysmiths.com</strong></p>

<div class="footer">
© {{ date('Y') }} Tully Smiths. All rights reserved.
</div>

</div>

</body>
</html>