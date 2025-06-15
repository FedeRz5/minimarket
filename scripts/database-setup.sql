-- Script para crear la base de datos del MiniMarket
-- Ejecutar este script en PHPMyAdmin

CREATE DATABASE IF NOT EXISTS minimarket CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE minimarket;

-- Tabla usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('empleado', 'jefe') NOT NULL DEFAULT 'empleado',
    salario DECIMAL(10,2) NOT NULL DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_usuario (usuario),
    INDEX idx_rol (rol)
);

-- Tabla productos
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    categoria VARCHAR(50),
    precio DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_nombre (nombre),
    INDEX idx_categoria (categoria),
    INDEX idx_stock (stock)
);

-- Tabla clientes
CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    telefono VARCHAR(20),
    direccion TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_nombre (nombre),
    INDEX idx_email (email)
);

-- Tabla ventas
CREATE TABLE ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_cliente INT NULL,
    fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    descuento DECIMAL(10,2) DEFAULT 0,
    impuesto DECIMAL(10,2) DEFAULT 0,
    estado ENUM('completada', 'cancelada', 'pendiente') DEFAULT 'completada',
    metodo_pago ENUM('efectivo', 'tarjeta', 'transferencia') DEFAULT 'efectivo',
    notas TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_cliente) REFERENCES clientes(id) ON DELETE SET NULL,
    INDEX idx_fecha (fecha),
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado)
);

-- Tabla detalle de ventas
CREATE TABLE ventas_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_venta INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento_item DECIMAL(10,2) DEFAULT 0,
    subtotal DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario - descuento_item) STORED,
    FOREIGN KEY (id_venta) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE RESTRICT,
    INDEX idx_venta (id_venta),
    INDEX idx_producto (id_producto)
);

-- Tabla cajas
CREATE TABLE cajas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    fecha_apertura DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre DATETIME NULL,
    monto_apertura DECIMAL(10,2) NOT NULL,
    monto_cierre DECIMAL(10,2) NULL,
    monto_esperado DECIMAL(10,2) NULL,
    diferencia DECIMAL(10,2) NULL,
    estado ENUM('abierta', 'cerrada') NOT NULL DEFAULT 'abierta',
    observaciones TEXT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_usuario (id_usuario),
    INDEX idx_estado (estado),
    INDEX idx_fecha_apertura (fecha_apertura)
);

-- Tabla movimientos de caja
CREATE TABLE movimientos_caja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_caja INT NOT NULL,
    tipo ENUM('entrada', 'salida') NOT NULL,
    concepto VARCHAR(100) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    observaciones TEXT,
    FOREIGN KEY (id_caja) REFERENCES cajas(id) ON DELETE CASCADE,
    INDEX idx_caja (id_caja),
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fecha)
);

-- Tabla categorías de productos
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Agregar relación con categorías
ALTER TABLE productos ADD COLUMN id_categoria INT NULL;
ALTER TABLE productos ADD FOREIGN KEY (id_categoria) REFERENCES categorias(id) ON DELETE SET NULL;

-- Tabla proveedores
CREATE TABLE proveedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(20),
    email VARCHAR(100),
    direccion TEXT,
    activo BOOLEAN DEFAULT TRUE,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla compras
CREATE TABLE compras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT NOT NULL,
    id_usuario INT NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('pendiente', 'recibida', 'cancelada') DEFAULT 'pendiente',
    numero_factura VARCHAR(50),
    observaciones TEXT,
    FOREIGN KEY (id_proveedor) REFERENCES proveedores(id) ON DELETE RESTRICT,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_proveedor (id_proveedor),
    INDEX idx_fecha (fecha),
    INDEX idx_estado (estado)
);

-- Tabla detalle de compras
CREATE TABLE compras_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_compra INT NOT NULL,
    id_producto INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) GENERATED ALWAYS AS (cantidad * precio_unitario) STORED,
    FOREIGN KEY (id_compra) REFERENCES compras(id) ON DELETE CASCADE,
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE RESTRICT,
    INDEX idx_compra (id_compra),
    INDEX idx_producto (id_producto)
);

-- Tabla historial de precios
CREATE TABLE historial_precios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_producto INT NOT NULL,
    precio_anterior DECIMAL(10,2) NOT NULL,
    precio_nuevo DECIMAL(10,2) NOT NULL,
    fecha_cambio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    id_usuario INT NOT NULL,
    motivo VARCHAR(255),
    FOREIGN KEY (id_producto) REFERENCES productos(id) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_producto (id_producto),
    INDEX idx_fecha (fecha_cambio)
);

-- Triggers para auditoría
DELIMITER //

CREATE TRIGGER tr_productos_precio_update
    AFTER UPDATE ON productos
    FOR EACH ROW
BEGIN
    IF OLD.precio != NEW.precio THEN
        INSERT INTO historial_precios (id_producto, precio_anterior, precio_nuevo, id_usuario, motivo)
        VALUES (NEW.id, OLD.precio, NEW.precio, @user_id, 'Actualización de precio');
    END IF;
END//

CREATE TRIGGER tr_ventas_items_stock_update
    AFTER INSERT ON ventas_items
    FOR EACH ROW
BEGIN
    UPDATE productos 
    SET stock = stock - NEW.cantidad 
    WHERE id = NEW.id_producto;
END//

DELIMITER ;

-- Vistas útiles
CREATE VIEW vista_productos_bajo_stock AS
SELECT p.*, c.nombre as categoria_nombre
FROM productos p
LEFT JOIN categorias c ON p.id_categoria = c.id
WHERE p.stock <= p.stock_minimo AND p.activo = TRUE;

CREATE VIEW vista_ventas_diarias AS
SELECT 
    DATE(fecha) as fecha,
    COUNT(*) as total_ventas,
    SUM(total) as ingresos_totales,
    AVG(total) as venta_promedio
FROM ventas 
WHERE estado = 'completada'
GROUP BY DATE(fecha)
ORDER BY fecha DESC;

CREATE VIEW vista_productos_mas_vendidos AS
SELECT 
    p.id,
    p.nombre,
    p.categoria,
    SUM(vi.cantidad) as total_vendido,
    SUM(vi.subtotal) as ingresos_generados
FROM productos p
JOIN ventas_items vi ON p.id = vi.id_producto
JOIN ventas v ON vi.id_venta = v.id
WHERE v.estado = 'completada'
GROUP BY p.id, p.nombre, p.categoria
ORDER BY total_vendido DESC;
