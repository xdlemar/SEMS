<?php
require __DIR__.'/../partials/auth.php';
session_destroy();
header("Location: /sems/public/auth/login.php");
