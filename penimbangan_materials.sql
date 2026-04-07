-- Setup table material untuk Penimbangan DB
USE Penimbangan;

CREATE TABLE IF NOT EXISTS `material` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL UNIQUE,
  `nama` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Test insert
INSERT IGNORE INTO material (kode, nama) VALUES ('MAT-001', 'Test Material');

SELECT * FROM material;

