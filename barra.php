<?php if (!isset($_SESSION)) session_start(); ?>
<aside style="width:200px; background:#f0f0f0; padding:10px; float:left; height:100vh;">
  <ul style="list-style:none; padding-left:0;">
    <li><a href="dashboard.php">Dashboard</a></li>
    <li><a href="productos.php">Productos</a></li>
    <li><a href="ventas.php">Ventas</a></li>
    <li><a href="cajas.php">Cajas</a></li>
    <li><a href="informes.php">Informes</a></li>
    <?php if ($_SESSION['rol'] === 'jefe'): ?>
      <li><a href="empleados.php">Empleados</a></li>
    <?php endif; ?>
  </ul>
</aside>