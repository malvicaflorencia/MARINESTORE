CREATE TABLE productos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100),
  descripcion TEXT,
  precio DECIMAL(10,2),
  descuento DECIMAL(10,2),
  imagen VARCHAR(255),
  estrellas INT,
  stock INT
);
