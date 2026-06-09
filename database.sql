CREATE DATABASE IF NOT EXISTS logbook_kkn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE logbook_kkn;

DROP TABLE IF EXISTS logbooks;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS themes;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS activity_types;
DROP TABLE IF EXISTS locations;

CREATE TABLE students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  google_id VARCHAR(120) NULL,
  email VARCHAR(150) UNIQUE NOT NULL,
  name VARCHAR(150) NOT NULL,
  birth_place VARCHAR(100) NULL,
  birth_date DATE NULL,
  faculty VARCHAR(150) NULL,
  study_program VARCHAR(150) NULL,
  phone VARCHAR(50) NULL,
  emergency_contact VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE themes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) UNIQUE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE programs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) UNIQUE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE activity_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) UNIQUE NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(180) UNIQUE NOT NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE logbooks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  theme_id INT NOT NULL,
  program_id INT NOT NULL,
  activity_type_id INT NOT NULL,
  location_id INT NOT NULL,
  log_date DATETIME NOT NULL,
  community_count INT DEFAULT 0,
  health_status VARCHAR(100) NOT NULL DEFAULT 'Normal',
  progress_note TEXT NOT NULL,
  personal_info TEXT NULL,
  documentation VARCHAR(255) NULL,
  status VARCHAR(50) NOT NULL DEFAULT 'Submitted',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (theme_id) REFERENCES themes(id),
  FOREIGN KEY (program_id) REFERENCES programs(id),
  FOREIGN KEY (activity_type_id) REFERENCES activity_types(id),
  FOREIGN KEY (location_id) REFERENCES locations(id)
) ENGINE=InnoDB;

INSERT INTO students(email, name, birth_place, birth_date, faculty, study_program, phone, emergency_contact) VALUES
('azmi@student.demo', 'M. Azmi', 'Bekasi', '1999-06-07', 'Fakultas Teknik', 'Teknik Informatika', '081234567890', 'Keluarga - 081111111111'),
('alya@student.demo', 'Alya Putri', 'Yogyakarta', '2001-05-12', 'Fakultas Ekonomika dan Bisnis', 'Manajemen', '082222222222', 'Ibu - 082000000000'),
('nadi@student.demo', 'Nadia Salsabila', 'Sleman', '2002-01-21', 'Fakultas Kedokteran', 'Kesehatan Masyarakat', '083333333333', 'Ayah - 083000000000'),
('rafi@student.demo', 'Rafi Pratama', 'Bantul', '2001-08-19', 'Fakultas Ilmu Budaya', 'Sastra Indonesia', '084444444444', 'Ibu - 084000000000'),
('dimas@student.demo', 'Dimas Arya', 'Semarang', '2000-12-03', 'Fakultas Pertanian', 'Agribisnis', '085555555555', 'Ayah - 085000000000'),
('mira@student.demo', 'Mira Lestari', 'Magelang', '2002-03-14', 'Fakultas Psikologi', 'Psikologi', '086666666666', 'Kakak - 086000000000');

INSERT INTO themes(name) VALUES
('Pemberdayaan UMKM'),('Kesehatan Masyarakat'),('Pendidikan dan Literasi'),('Lingkungan'),('Digitalisasi Desa');

INSERT INTO programs(name) VALUES
('Digitalisasi UMKM Desa'),('Sosialisasi Pencegahan Stunting'),('Literasi Digital Sekolah'),('Pendataan Potensi Desa'),('Pengelolaan Sampah Terpadu');

INSERT INTO activity_types(name) VALUES
('Program Individu'),('Program Kelompok'),('Program Bantu'),('Rapat Koordinasi'),('Monitoring Lapangan');

INSERT INTO locations(name, latitude, longitude) VALUES
('Desa Candirejo, Sleman', -7.7956000, 110.3695000),
('Desa Wukirsari, Sleman', -7.8022000, 110.3921000),
('Desa Guwosari, Bantul', -7.8580000, 110.3180000),
('Desa Tirtonirmolo, Bantul', -7.8324000, 110.3541000),
('Desa Sumberadi, Sleman', -7.7608000, 110.3501000);

INSERT INTO logbooks(student_id, theme_id, program_id, activity_type_id, location_id, log_date, community_count, health_status, progress_note, personal_info, status) VALUES
(1, 1, 1, 1, 1, NOW() - INTERVAL 1 DAY, 27, 'Normal', 'Pendampingan UMKM untuk membuat katalog produk dan simulasi promosi digital.', 'Kondisi baik. Perlu koordinasi lanjutan dengan perangkat desa.', 'Submitted'),
(1, 5, 4, 4, 2, NOW() - INTERVAL 2 DAY, 12, 'Kendala Lapangan', 'Pendataan potensi desa belum lengkap karena sebagian warga belum tersedia.', 'Butuh jadwal ulang dengan kepala dusun.', 'Draft'),
(2, 3, 3, 2, 3, NOW() - INTERVAL 3 DAY, 35, 'Normal', 'Literasi digital untuk siswa SD tentang keamanan internet dan penggunaan aplikasi belajar.', 'Kegiatan berjalan lancar.', 'Reviewed'),
(3, 2, 2, 1, 4, NOW() - INTERVAL 4 DAY, 42, 'Sakit Ringan', 'Sosialisasi PHBS dan pencegahan stunting bersama kader posyandu.', 'Istirahat sore karena kurang fit.', 'Submitted'),
(4, 4, 5, 2, 2, NOW() - INTERVAL 5 DAY, 18, 'Normal', 'Pelatihan pengelolaan sampah dan pemilahan organik non-organik.', 'Kegiatan dibantu karang taruna.', 'Reviewed'),
(5, 1, 1, 1, 5, NOW() - INTERVAL 6 DAY, 21, 'Izin', 'Pendampingan branding produk tani lokal.', 'Izin setengah hari untuk urusan keluarga.', 'Submitted'),
(6, 2, 2, 3, 1, NOW() - INTERVAL 7 DAY, 30, 'Normal', 'Pendampingan kader kesehatan untuk pendataan balita.', 'Koordinasi baik dengan posyandu.', 'Reviewed');
