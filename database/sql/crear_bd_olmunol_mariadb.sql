-- Ejecutar en phpMyAdmin o en MariaDB antes de correr las migraciones de Laravel.
CREATE DATABASE IF NOT EXISTS olmunol
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

-- Usuario local opcional. En XAMPP normalmente basta con root sin contraseña.
-- Si deseas crear un usuario propio, descomenta estas líneas:
-- CREATE USER IF NOT EXISTS 'olmunol_user'@'localhost' IDENTIFIED BY 'Olmunol12345';
-- GRANT ALL PRIVILEGES ON olmunol.* TO 'olmunol_user'@'localhost';
-- FLUSH PRIVILEGES;
