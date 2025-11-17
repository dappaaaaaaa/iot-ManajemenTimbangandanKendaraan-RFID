/*
   ------------------- WIRING -------------------
   RFID MFRC522
   RST/Reset : RST  --> GPIO 4
   SDA(SS)   : SS   --> GPIO 5
   MOSI      : MOSI --> GPIO 23
   MISO      : MISO --> GPIO 19
   SCK       : SCK  --> GPIO 18

   Load Cell HX711
   DT (Data) : DT_PIN  --> GPIO 26
   SCK       : SCK_PIN --> GPIO 27
   VCC       --> 5V
   GND       --> GND

   Servo SG90
   Signal (Orange) : SERVO_PIN --> GPIO 17
   VCC (Red)       --> 5V (supply terpisah disarankan)
   GND (Brown/Black) --> GND (hubungkan ke GND ESP32)

   Sensor IR Flying Fish (Digital Output)
   VCC    --> 5V
   GND    --> GND
   OUT    : IR_PIN --> GPIO 16

   LCD I2C 16x2
   SDA    --> GPIO 21
   SCL    --> GPIO 22
   VCC    --> 5V
   GND    --> GND
*/

/* Libraries */
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <SPI.h>
#include <MFRC522.h>
#include "HX711.h"
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <ESP32Servo.h>
#include <WebServer.h>

/* WiFi */
const char* ssid = "Dap";
const char* pass = "123456789";

/* API */
const char* API_TAP  = "http://10.111.172.81:8000/api/measurement/tap";
const char* API_SCAN = "http://10.111.172.81:8000/api/rfid/scan";

/* Pins */
#define RST_PIN 4
#define SS_PIN 5
#define SCK_HW 18
#define MISO_HW 19
#define MOSI_HW 23
#define DT_PIN 26
#define SCK_PIN 27
#define SERVO_PIN 17
#define IR_PIN 16

/* Objects */
MFRC522 rfid(SS_PIN, RST_PIN);
HX711 scale;
Servo gateServo;
LiquidCrystal_I2C lcd(0x27, 16, 2);
WebServer server(8000);   // Webserver di port 8000

/* Load cell / logic */
float calibration_factor = 22540.32;
float lastStableWeight = 0;
float stableWeight = 0;
unsigned long stableStartTime = 0;
const unsigned long stableDelay = 2000; // ms required stable
bool waitingForRFID = false;

/* Servo positions */
const int SERVO_TUTUP = 90;
const int SERVO_BUKA  = 0;

/* Timeouts */
const unsigned long RFID_SCAN_TIMEOUT = 90000UL; // ms

/* RFID Blocker */
String lastUID = "";
bool timbangSelesai = true; // awalnya true supaya langsung bisa mulai

/* Helpers */
bool kendaraanLewat() {
  return digitalRead(IR_PIN) == LOW;
}

/* UID tanpa leading zero */
String uidToStringNoPad(const MFRC522::Uid &uid) {
  String out = "";
  for (byte i = 0; i < uid.size; i++) {
    out += String(uid.uidByte[i], HEX);
  }
  out.toUpperCase();
  out.trim();
  return out;
}

/* WiFi connect */
void connectWiFi() {
  lcd.clear();
  lcd.setCursor(0,0);
  lcd.print("WiFi: connecting");
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, pass);

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED) {
    delay(300);
    Serial.print(".");
    if (millis() - start > 20000) {
      lcd.setCursor(0,1);
      lcd.print("WiFi gagal!");
      Serial.println("\nWiFi gagal tersambung.");
      return;
    }
  }
  lcd.clear();
  lcd.setCursor(0,0);
  lcd.print("WiFi OK:");
  lcd.setCursor(0,1);
  lcd.print(WiFi.localIP().toString());
  Serial.println("\nWiFi tersambung");
  Serial.print("IP ESP32: ");
  Serial.println(WiFi.localIP());
}

/* Fungsi buka gate (reusable) */
void bukaGate() {
  Serial.println("Gate open -> buka palang");
  lcd.clear();
  lcd.setCursor(0,0);
  lcd.print("Gerbang: BUKA");
  gateServo.write(SERVO_BUKA);

  unsigned long enterStart = millis();
  while (!kendaraanLewat()) {
    if (millis() - enterStart > 60000UL) break;
    delay(50);
  }

  if (kendaraanLewat()) {
    unsigned long leaveStart = millis();
    while (kendaraanLewat()) {
      if (millis() - leaveStart > 120000UL) break;
      delay(50);
    }
  }

  delay(800);
  gateServo.write(SERVO_TUTUP);
  lcd.clear();
  lcd.setCursor(0,0);
  lcd.print("Gerbang: TUTUP");
}

/* Handler API lokal untuk buka gate */
void handleOpenGate() {
  bukaGate();
  String json = "{\"success\":true,\"message\":\"Gate opened by API\"}";
  server.send(200, "application/json", json);
}

/* Kirim data timbang + UID */
void sendTap(const String &tagId, float weight) {
  Serial.println("Mengirim data ke API Tap...");
  HTTPClient http;
  WiFiClient client;
  http.begin(client, API_TAP);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  StaticJsonDocument<192> doc;
  doc["tag_id"] = tagId;
  doc["weight"] = weight;
  doc["ir_blocked"] = kendaraanLewat();

  String body;
  serializeJson(doc, body);
  Serial.println("Payload: " + body);

  int code = http.POST(body);
  Serial.println("HTTP Response code: " + String(code));
  if (code > 0) {
    String resp = http.getString();
    Serial.println("Response: " + resp);

    StaticJsonDocument<512> parsed;
    DeserializationError err = deserializeJson(parsed, resp);
    if (err == DeserializationError::Ok) {
      bool gateOpen = parsed["gate_open"] | parsed["open_gate"] | false;
      String message = parsed["message"] | "";

      lcd.clear();
      lcd.setCursor(0,0);
      lcd.print(tagId.substring(0, min(8, (int)tagId.length())));
      lcd.setCursor(0,1);
      lcd.print(message.substring(0,16));

      if (gateOpen) {
        bukaGate();
      }

      if (message.indexOf("Semua tahap timbang sudah lengkap") != -1) {
        Serial.println("Tahap timbang selesai, reset UID");
        timbangSelesai = true;
        lastUID = "";
      }

    } else {
      Serial.println("Gagal parse JSON respon.");
    }
  } else {
    Serial.println("HTTP Request gagal. Cek koneksi/API.");
  }
  http.end();
}

/* Kirim UID ke API Scan */
void sendScan(const String &tagId) {
  Serial.println("Mengirim UID ke API Scan...");
  
  HTTPClient http;
  WiFiClient client;

  http.begin(client, API_SCAN);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");

  StaticJsonDocument<128> doc;
  doc["tag_id"] = tagId;

  String body;
  serializeJson(doc, body);
  Serial.println("Payload: " + body);

  int code = http.POST(body);
  Serial.println("HTTP Response code: " + String(code));

  if (code > 0) {
    String resp = http.getString();
    Serial.println("Response: " + resp);

    StaticJsonDocument<512> docResp;
    DeserializationError error = deserializeJson(docResp, resp);
    if (!error) {
      bool success = docResp["success"];
      if (success) {
        const char* mode    = docResp["mode"];
        const char* owner   = docResp["owner_name"];
        const char* vehicle = docResp["vehicle_number"];

        Serial.println("Mode: " + String(mode));
        if (String(mode) == "register") {
          Serial.println("Kartu baru, perlu didaftarkan.");
        } else if (String(mode) == "check") {
          Serial.println("Kartu valid, data terdaftar:");
          Serial.println("Owner   : " + String(owner ? owner : "-"));
          Serial.println("Vehicle : " + String(vehicle ? vehicle : "-"));
        }
      } else {
        Serial.println("API mengembalikan gagal.");
      }
    } else {
      Serial.println("Gagal parsing JSON.");
    }
  } else {
    Serial.println("HTTP Request gagal.");
  }

  http.end();
}

/* Setup */
void setup() {
  Serial.begin(115200);
  delay(200);

  Wire.begin(21, 22);
  lcd.init();
  lcd.backlight();
  lcd.setCursor(0,0);
  lcd.print("Inisialisasi...");

  connectWiFi();

  // aktifkan WebServer
  server.on("/gate/open", HTTP_ANY, handleOpenGate);
  server.begin();
  Serial.println("WebServer aktif di port 8000");

  SPI.begin(SCK_HW, MISO_HW, MOSI_HW, SS_PIN);
  rfid.PCD_Init();
  Serial.println("RFID siap");

  scale.begin(DT_PIN, SCK_PIN);
  scale.set_scale(calibration_factor);
  scale.tare();
  Serial.println("Load Cell siap");

  gateServo.attach(SERVO_PIN);
  gateServo.write(SERVO_TUTUP);

  pinMode(IR_PIN, INPUT);

  lcd.clear();
  lcd.setCursor(0,0);
  lcd.print("Siap");
  lcd.setCursor(0,1);
  lcd.print("Menunggu beban...");
}

/* Loop */
void loop() {
  server.handleClient();   // biar API lokal jalan
  if (WiFi.status() != WL_CONNECTED) connectWiFi();

  float currentWeight = scale.get_units(10);
  if (currentWeight < 0) currentWeight = 0;
  Serial.printf("Berat sekarang: %.2f kg\n", currentWeight);

  lcd.setCursor(0,0);
  lcd.print("Berat:");
  lcd.setCursor(6,0);
  lcd.print(currentWeight, 2);
  lcd.print(" kg   ");

  if (currentWeight < 0.1) {
    lcd.setCursor(0,1);
    lcd.print("Tap kartu daftar ");

    if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
      String uidStr = uidToStringNoPad(rfid.uid);
      Serial.println("UID (daftar): " + uidStr);

      lcd.clear();
      lcd.setCursor(0,0);
      lcd.print("UID:");
      lcd.print(uidStr.substring(0, min(8, (int)uidStr.length())));
      lcd.setCursor(0,1);
      lcd.print("Daftar...");

      sendScan(uidStr);

      rfid.PICC_HaltA();
      rfid.PCD_StopCrypto1();
      delay(800);
    }
  } else {
    if (!waitingForRFID) {
      if (abs(currentWeight - lastStableWeight) < 0.05) {
        if (stableStartTime == 0) stableStartTime = millis();
        if (millis() - stableStartTime >= stableDelay) {
          stableWeight = currentWeight;
          waitingForRFID = true;

          lcd.setCursor(0,1);
          lcd.print("Stabil: ");
          lcd.print(stableWeight, 2);
          lcd.print("kg   ");
          delay(800);
          lcd.clear();
          lcd.setCursor(0,0);
          lcd.print("Berat stabil");
          lcd.setCursor(0,1);
          lcd.print("Tap kartu...");
        }
      } else {
        stableStartTime = 0;
        lastStableWeight = currentWeight;
        lcd.setCursor(0,1);
        lcd.print("Status: berubah  ");
      }
    } else {
      if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
        String uidStr = uidToStringNoPad(rfid.uid);
        Serial.println("UID (timbang): " + uidStr);

        lcd.clear();
        lcd.setCursor(0,0);
        lcd.print("UID:");
        lcd.print(uidStr.substring(0, min(8, (int)uidStr.length())));
        lcd.setCursor(0,1);
        lcd.print("Kirim timbang...");

        sendTap(uidStr, stableWeight);

        waitingForRFID = false;
        lastStableWeight = 0;
        stableStartTime = 0;

        rfid.PICC_HaltA();
        rfid.PCD_StopCrypto1();
        delay(800);
      }
    }
  }

  delay(200);
}
