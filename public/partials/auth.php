<?php
session_start();
$dsn="mysql:host=127.0.0.1;dbname=sems;charset=utf8mb4";
$db=new PDO($dsn,"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);

function login_required(){ if(empty($_SESSION['uid'])){ header("Location: /sems/public/auth/login.php"); exit; } }

function current_user(PDO $db){
  if(empty($_SESSION['uid'])) return null;
  $sql="SELECT u.id,u.username,u.role,u.employee_id,
               COALESCE(CONCAT(e.fname,' ',e.lname), u.username) AS name,
               e.position
        FROM users u
        LEFT JOIN employees e ON e.id=u.employee_id
        WHERE u.id=?";
  $st=$db->prepare($sql); $st->execute([$_SESSION['uid']]);
  return $st->fetch(PDO::FETCH_ASSOC);
}

function require_role($roles){
  global $db; login_required();
  $u=current_user($db); $roles=(array)$roles;
  if(!$u || !in_array($u['role'],$roles)){ http_response_code(403); exit('Forbidden'); }
}
