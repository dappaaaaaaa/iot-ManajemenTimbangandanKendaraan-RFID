# 🚚 IoT Manajemen Timbangan & Kendaraan Berbasis RFID  
Purwarupa Sistem Timbangan Otomatis Pertambangan Batu Bara  
ESP32 • Load Cell • HX711 • RFID RC522 • Laravel • PostgreSQL • Filament

---

## 📖 Deskripsi Proyek
Proyek ini merupakan sistem Internet of Things (IoT) untuk mengotomatisasi:

- Identifikasi kendaraan menggunakan RFID
- Penimbangan otomatis dengan Load Cell + HX711
- Pengiriman data ke website secara real-time
- Pembukaan palang otomatis menggunakan servo
- Monitoring dan histori pada dashboard Laravel

Tujuan sistem:
- Mengurangi antrean di jembatan timbang
- Menghilangkan pencatatan manual
- Meningkatkan akurasi operasional
- Integrasi IoT & sistem informasi perusahaan

---

## 🏗️ Arsitektur Sistem

### Hardware
- ESP32
- RFID RC522
- Load Cell 50kg (4 unit)
- HX711 Amplifier
- Infrared Sensor
- Servo SG90
- LCD I2C 20x4

### Software
- Laravel 10/11
- PostgreSQL
- FilamentPHP
- TailwindCSS
- REST API (IoT → Web)

---

## 📦 Komponen

| Komponen | Jumlah |
|---------|--------|
| ESP32 | 1 |
| Load Cell | 4 |
| HX711 | 1 |
| RFID RC522 | 1 |
| Kartu RFID | 1 |
| Servo SG90 | 1 |
| IR Sensor | 1 |
| LCD I2C 20x4 | 1 |

---

## ⚙️ Instalasi Proyek Laravel

### 1. Clone Repository
git clone https://github.com/USERNAME/REPO.git
cd REPO

### 2. Install Dependency
composer install
npm install
npm run build

### 3. Konfigurasi Environment
cp .env.example .env

Isi bagian database dalam `.env`:
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=nama_database
DB_USERNAME=postgres
DB_PASSWORD=password

Generate key:
php artisan key:generate

---

## 🗄️ Import Struktur Database PostgreSQL

File struktur database:
database/structure.sql

Import menggunakan:
psql -U postgres -d nama_database -f database/structure.sql

Jika menggunakan contoh database:
psql -U postgres -d nama_database -f database/example.sql

---

## 🔌 Setup ESP32 IoT

### Library yang diperlukan
- MFRC522  
- HX711  
- LiquidCrystal_I2C  
- WiFi.h  
- HTTPClient  
- Servo  

### Langkah Upload
1. Buka Arduino IDE / PlatformIO  
2. Atur SSID WiFi, password & URL API Laravel  
3. Upload program ke ESP32  
4. Hubungkan sensor sesuai wiring diagram  

---

## 📡 Alur Kerja Sistem

1. Kendaraan terdeteksi sensor IR  
2. Pengemudi tap kartu RFID  
3. ESP32 mengirim ID ke server  
4. Kendaraan naik ke timbangan  
5. Load Cell membaca berat  
6. Data dikirim ke Laravel via REST API  
7. Jika valid → servo buka palang otomatis  
8. Data tersimpan sebagai histori penimbangan  

---

## 📊 Fitur Sistem

### IoT
- Pembacaan RFID  
- Penimbangan otomatis  
- Servo palang otomatis  
- Sensor IR  
- LCD 20x4  
- API HTTP  

### Website Laravel
- Dashboard realtime  
- Data kendaraan & RFID  
- Histori penimbangan  
- Export Excel & PDF  
- Hak akses Admin/Operator  

---

## 🛠️ Teknologi

IoT:
ESP32, HX711, RFID RC522, IR Sensor, Servo SG90

Web:
Laravel, FilamentPHP, PostgreSQL, TailwindCSS, REST API

---

## 👤 Pengembang
Nama: Muhammad Daffa Febriyan  
Program Studi: D3 Teknik Informatika  
Politeknik Negeri Pontianak — 2025

---

## 📄 Lisensi
Proyek ini dibuat untuk keperluan Tugas Akhir.
