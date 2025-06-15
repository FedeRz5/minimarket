<?php

// Constantes del sistema - Evitar magic numbers
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_LOGIN_ATTEMPTS', 3);
define('SESSION_TIMEOUT', 3600); // 1 hora
define('DEFAULT_ITEMS_PER_PAGE', 10);

// Roles de usuario
define('ROLE_EMPLOYEE', 'empleado');
define('ROLE_MANAGER', 'jefe');

// Estados de caja
define('CASH_REGISTER_OPEN', 'abierta');
define('CASH_REGISTER_CLOSED', 'cerrada');

// Tipos de movimiento
define('MOVEMENT_SALE', 'venta');
define('MOVEMENT_PURCHASE', 'compra');
define('MOVEMENT_ADJUSTMENT', 'ajuste');
