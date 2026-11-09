/*
 * FIRE ALARM SYSTEM - ESP32 IOT FIRMWARE
 * 
 * Hardware Requirements:
 * 1. ESP32 Development Board (NodeMCU ESP32 / ESP-WROOM-32)
 * 2. MQ-2 Gas / Smoke Sensor (Analog Output connected to GPIO 34 / ADC1)
 * 3. Flame Sensor IR (Digital Output DO connected to GPIO 35 / ADC1)
 * 4. Active Buzzer 5V/3.3V (connected to GPIO 18)
 * 5. LED Status Indikator (connected to GPIO 2 - Builtin LED)
 * 
 * Dependencies (Install via Arduino Library Manager):
 * - ArduinoJson (by Benoit Blanchon, v6.x or v7.x)
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ================= KONFIGURASI WIFI & SERVER =================
const char* WIFI_SSID     = "NAMA_WIFI_ANDA";
const char* WIFI_PASSWORD = "PASSWORD_WIFI";

// Ganti IP dengan IP lokal komputer/server Laravel Anda (cek dengan 'ipconfig' di cmd)
// Contoh: "http://192.168.1.100:8000/api/sensor-data"
const char* API_ENDPOINT  = "http://192.168.1.100:8000/api/sensor-data";
const char* DEVICE_CODE   = "ESP32-FIRE-01";

// ================= KONFIGURASI PIN =================
const int PIN_MQ2_ANALOG   = 34; // Pin Analog MQ-2 (ADC1)
const int PIN_FLAME_DIGITAL = 35; // Pin Digital Flame Sensor (DO)
const int PIN_BUZZER       = 18; // Pin Buzzer Alarm
const int PIN_LED_STATUS   = 2;  // Pin LED Bawaan / Indikator

// ================= PARAMETER AMBANG BATAS =================
const int MQ2_THRESHOLD_DANGER = 400; // Ambang batas bahaya asap/gas
const unsigned long SEND_INTERVAL_MS = 3000; // Kirim data setiap 3 detik
unsigned long lastSendTime = 0;

void setup() {
    Serial.begin(115200);
    delay(1000);

    Serial.println("\n====================================");
    Serial.println("  FIRE ALARM SYSTEM - ESP32 NODE   ");
    Serial.println("====================================");

    // Konfigurasi pin I/O
    pinMode(PIN_MQ2_ANALOG, INPUT);
    pinMode(PIN_FLAME_DIGITAL, INPUT);
    pinMode(PIN_BUZZER, OUTPUT);
    pinMode(PIN_LED_STATUS, OUTPUT);

    digitalWrite(PIN_BUZZER, LOW);
    digitalWrite(PIN_LED_STATUS, LOW);

    // Hubungkan ke WiFi
    connectToWiFi();
}

void loop() {
    // Pastikan koneksi WiFi tetap terjaga
    if (WiFi.status() != WL_CONNECTED) {
        connectToWiFi();
    }

    // Baca sensor
    int mq2Raw = analogRead(PIN_MQ2_ANALOG);
    
    // Kebanyakan Flame Sensor Module: LOW = Ada Api, HIGH = Normal
    // Sesuaikan dengan modul Anda: jika modul aktif HIGH, ubah menjadi (digitalRead == HIGH)
    bool flameDetected = (digitalRead(PIN_FLAME_DIGITAL) == LOW);

    bool isDanger = (mq2Raw >= MQ2_THRESHOLD_DANGER) || flameDetected;

    // Alarm hardware lokal (Buzzer & LED)
    if (isDanger) {
        digitalWrite(PIN_BUZZER, HIGH);
        digitalWrite(PIN_LED_STATUS, HIGH);
    } else {
        digitalWrite(PIN_BUZZER, LOW);
        digitalWrite(PIN_LED_STATUS, LOW);
    }

    // Kirim data ke backend Laravel secara berkala
    if (millis() - lastSendTime >= SEND_INTERVAL_MS) {
        lastSendTime = millis();
        sendSensorData(mq2Raw, flameDetected);
    }

    delay(100);
}

void connectToWiFi() {
    Serial.print("Menghubungkan ke WiFi: ");
    Serial.println(WIFI_SSID);

    WiFi.mode(WIFI_STA);
    WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 30) {
        delay(500);
        Serial.print(".");
        digitalWrite(PIN_LED_STATUS, !digitalRead(PIN_LED_STATUS));
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        digitalWrite(PIN_LED_STATUS, HIGH);
        Serial.println("\n[WiFi] Berhasil Terhubung!");
        Serial.print("[WiFi] IP Address ESP32: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("\n[WiFi] Gagal terhubung ke WiFi, akan mencoba lagi.");
    }
}

void sendSensorData(int mq2Value, bool flameDetected) {
    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("[HTTP] WiFi tidak tersambung, skip pengiriman.");
        return;
    }

    HTTPClient http;
    http.begin(API_ENDPOINT);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");

    // Buat JSON payload
    StaticJsonDocument<256> doc;
    doc["device_code"]    = DEVICE_CODE;
    doc["mq2_value"]      = mq2Value;
    doc["flame_detected"] = flameDetected;

    String requestBody;
    serializeJson(doc, requestBody);

    Serial.printf("[HTTP] Mengirim data: MQ2=%d, Api=%s\n", mq2Value, flameDetected ? "ADA" : "TIDAK");

    int httpResponseCode = http.POST(requestBody);

    if (httpResponseCode > 0) {
        String response = http.getString();
        Serial.printf("[HTTP] Response code: %d\n", httpResponseCode);
        if (httpResponseCode == 201 || httpResponseCode == 200) {
            Serial.println("[HTTP] Data berhasil tercatat di database!");
        }
    } else {
        Serial.printf("[HTTP] Error pengiriman: %s\n", http.errorToString(httpResponseCode).c_str());
    }

    http.end();
}

