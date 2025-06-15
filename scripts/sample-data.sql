
USE minimarket;

-- Insertar usuarios de ejemplo
INSERT INTO usuarios (usuario, password, nombre, rol, salario) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'jefe', 3000.00),
('empleado1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Pérez', 'empleado', 1500.00),
('empleado2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María García', 'empleado', 1500.00);

-- Insertar categorías
INSERT INTO categorias (nombre, descripcion) VALUES
('Bebidas', 'Bebidas alcohólicas y no alcohólicas'),
('Snacks', 'Aperitivos y golosinas'),
('Lácteos', 'Productos lácteos y derivados'),
('Panadería', 'Pan y productos de panadería'),
('Limpieza', 'Productos de limpieza e higiene'),
('Frutas', 'Frutas frescas'),
('Verduras', 'Verduras y hortalizas'),
('Carnes', 'Carnes y embutidos'),
('Cereales', 'Cereales y granos'),
('Condimentos', 'Especias y condimentos');

-- Insertar productos de ejemplo
INSERT INTO productos (nombre, categoria, precio, stock, stock_minimo, id_categoria) VALUES
('Coca Cola 500ml', 'Bebidas', 2.50, 50, 10, 1),
('Pepsi 500ml', 'Bebidas', 2.30, 45, 10, 1),
('Agua Mineral 1L', 'Bebidas', 1.20, 100, 20, 1),
('Papas Fritas Lays', 'Snacks', 3.50, 30, 5, 2),
('Chocolate Snickers', 'Snacks', 2.80, 25, 5, 2),
('Leche Entera 1L', 'Lácteos', 4.20, 40, 8, 3),
('Yogurt Natural', 'Lácteos', 1.80, 35, 10, 3),
('Pan Integral', 'Panadería', 2.00, 20, 5, 4),
('Galletas Oreo', 'Snacks', 4.50, 15, 3, 2),
('Detergente Líquido', 'Limpieza', 8.90, 12, 3, 5),
('Manzanas Rojas (kg)', 'Frutas', 3.20, 25, 5, 6),
('Tomates (kg)', 'Verduras', 2.80, 30, 8, 7),
('Pollo Entero (kg)', 'Carnes', 12.50, 15, 3, 8),
('Arroz Blanco (kg)', 'Cereales', 4.80, 50, 10, 9),
('Sal de Mesa', 'Condimentos', 1.50, 40, 8, 10);

-- Insertar clientes de ejemplo
INSERT INTO clientes (nombre, email, telefono, direccion) VALUES
('Carlos Rodríguez', 'carlos@email.com', '555-0101', 'Av. Principal 123'),
('Ana Martínez', 'ana@email.com', '555-0102', 'Calle Secundaria 456'),
('Luis González', 'luis@email.com', '555-0103', 'Plaza Central 789'),
('Carmen López', 'carmen@email.com', '555-0104', 'Barrio Norte 321'),
('Roberto Silva', 'roberto@email.com', '555-0105', 'Zona Sur 654');

-- Insertar proveedores de ejemplo
INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) VALUES
('Distribuidora Central', 'Pedro Ramírez', '555-1001', 'ventas@distcentral.com', 'Zona Industrial A-1'),
('Alimentos Frescos SA', 'María Fernández', '555-1002', 'pedidos@alimentosfrescos.com', 'Mercado Mayorista 45'),
('Bebidas del Norte', 'Juan Carlos', '555-1003', 'distribución@bebidasnorte.com', 'Av. Industrial 890'),
('Lácteos Premium', 'Sofia Herrera', '555-1004', 'ventas@lacteospremium.com', 'Granja Los Alpes'),
('Limpieza Total', 'Miguel Ángel', '555-1005', 'comercial@limpiezatotal.com', 'Polígono Industrial B-7');

-- Insertar algunas ventas de ejemplo
INSERT INTO ventas (id_usuario, id_cliente, total, metodo_pago) VALUES
(2, 1, 15.80, 'efectivo'),
(2, 2, 23.40, 'tarjeta'),
(3, 3, 8.90, 'efectivo'),
(2, NULL, 12.60, 'efectivo'),
(3, 4, 31.20, 'transferencia');

-- Insertar items de las ventas
INSERT INTO ventas_items (id_venta, id_producto, cantidad, precio_unitario) VALUES
-- Venta 1
(1, 1, 2, 2.50),  -- 2 Coca Colas
(1, 4, 3, 3.50),  -- 3 Papas Fritas
-- Venta 2
(2, 6, 2, 4.20),  -- 2 Leches
(2, 9, 3, 4.50),  -- 3 Galletas Oreo
-- Venta 3
(3, 10, 1, 8.90), -- 1 Detergente
-- Venta 4
(4, 3, 5, 1.20),  -- 5 Aguas
(4, 8, 3, 2.00),  -- 3 Panes
-- Venta 5
(5, 13, 2, 12.50), -- 2 kg Pollo
(5, 11, 2, 3.20);  -- 2 kg Manzanas

-- Insertar algunas cajas de ejemplo
INSERT INTO cajas (id_usuario, monto_apertura, estado) VALUES
(2, 100.00, 'abierta'),
(3, 150.00, 'cerrada');

-- Actualizar la caja cerrada
UPDATE cajas SET 
    fecha_cierre = DATE_ADD(fecha_apertura, INTERVAL 8 HOUR),
    monto_cierre = 380.50,
    monto_esperado = 375.00,
    diferencia = 5.50
WHERE id = 2;

-- Insertar movimientos de caja
INSERT INTO movimientos_caja (id_caja, tipo, concepto, monto, observaciones) VALUES
(1, 'entrada', 'Venta #001', 15.80, 'Venta en efectivo'),
(1, 'entrada', 'Venta #003', 8.90, 'Venta en efectivo'),
(1, 'entrada', 'Venta #004', 12.60, 'Venta en efectivo'),
(1, 'salida', 'Cambio cliente', 2.20, 'Cambio entregado'),
(2, 'entrada', 'Apertura caja', 150.00, 'Monto inicial'),
(2, 'entrada', 'Ventas del día', 225.50, 'Total ventas en efectivo');

-- Insertar algunas compras de ejemplo
INSERT INTO compras (id_proveedor, id_usuario, total, estado, numero_factura) VALUES
(1, 1, 250.00, 'recibida', 'FAC-001'),
(2, 1, 180.50, 'recibida', 'FAC-002'),
(3, 1, 320.00, 'pendiente', 'FAC-003');

-- Insertar items de compras
INSERT INTO compras_items (id_compra, id_producto, cantidad, precio_unitario) VALUES
-- Compra 1
(1, 1, 50, 1.80),  -- 50 Coca Colas a precio de compra
(1, 2, 40, 1.60),  -- 40 Pepsis
-- Compra 2
(2, 6, 30, 3.00),  -- 30 Leches
(2, 7, 25, 1.20),  -- 25 Yogurts
-- Compra 3
(3, 13, 20, 9.50), -- 20 kg Pollo
(3, 14, 40, 3.20); -- 40 kg Arroz
