<?php
session_start();
include 'db.php';

$email=$_POST['email'];
$password=$_POST['password'];

$stmt=$conn->prepare("SELECT * FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();
$result=$stmt->get_result();

if($row=$result->fetch_assoc()){
 if(password_verify($password,$row['password_hash'])){
   $_SESSION['user_id']=$row['id'];
   header("Location: profile.php");
   exit;
 }
}
echo "Invalid login";
?>
