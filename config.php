<?php
$host = "localhost";
$dbname = "mysql";
$username = "root"; 
$password = ""; // vide si tu es en local avec XAMPP

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur connexion : " . $e->getMessage());
}
?>