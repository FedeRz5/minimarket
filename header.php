<?php
if (!isset($_SESSION)) session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<title>MiniMarket</title>
<style>
/* Simple CSS para el header */
header {
  background: #007BFF;
  color: white;
  padding: 10px;
}
nav a {
  color: white;
  margin-right: 10px;
  text-decoration: none;
}
</style>
</head>
<body>
<header>
  <nav>
    <a href="dashboard.php">Inicio</a>
    <a href="productos.php">Productos</a>
    <a href="ventas.php">Ventas</a>
    <a href="cajas.php">Cajas</a>
    <a href="informes.php">Informes</a>
    <?php if ($_SESSION['rol'] === 'jefe'): ?>
      <a href="empleados.php">Empleados</a>
    <?php endif; ?>
    <span style="float:right;">Hola, <?=htmlspecialchars($_SESSION['nombre'])?> | <a href="logout.php" style="color:#ffc;">Salir</a></span>
  </nav>
</header>