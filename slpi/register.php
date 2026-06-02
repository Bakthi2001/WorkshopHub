<?php
include 'db.php';
if($_SERVER['REQUEST_METHOD']=='POST'){
$full_name=$_POST['full_name'];
$email=$_POST['email'];
$phone=$_POST['phone'];
$nic=$_POST['nic'];
$password=password_hash($_POST['password'], PASSWORD_DEFAULT);

$stmt=$conn->prepare("INSERT INTO users(full_name,email,phone,nic,password_hash) VALUES (?,?,?,?,?)");
$stmt->bind_param("sssss",$full_name,$email,$phone,$nic,$password);

if($stmt->execute()){
 echo "Registration successful";
}else{
 echo $conn->error;
}
}
?>
