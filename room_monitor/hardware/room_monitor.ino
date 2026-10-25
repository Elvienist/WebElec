#include <DHT.h>

#define DHTPIN 2
#define DHTTYPE DHT11
#define LED_PIN 8

DHT dht(DHTPIN, DHTTYPE);

unsigned long lastReadTime = 0;
const unsigned long readInterval = 2000; // 2 seconds

unsigned long lastReplyTime = 0;
const unsigned long failsafeTimeout = 10000; // 10 seconds

char currentCommand = 'O';
bool blinkState = false;
unsigned long lastBlinkToggle = 0;

void setup() {
  Serial.begin(9600);
  dht.begin();
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);
  lastReplyTime = millis();
}

void loop() {
  unsigned long currentMillis = millis();

  // 1. Send reading every 2 seconds
  if (currentMillis - lastReadTime >= readInterval) {
    lastReadTime = currentMillis;
    float temp = dht.readTemperature();

    if (isnan(temp)) {
      Serial.println("E");
    } else {
      Serial.print("T:");
      Serial.println(temp, 1);
    }
  }

  if (Serial.available() > 0) {
    char cmd = (char)Serial.read();
    if (cmd == 'O' || cmd == 'N' || cmd == 'B') {
      currentCommand = cmd;
      lastReplyTime = currentMillis; // Reset failsafe timer upon receiving a command
    }
  }

  if (currentMillis - lastReplyTime > failsafeTimeout) {
    currentCommand = 'O';
  }

  if (currentCommand == 'O') {
    digitalWrite(LED_PIN, LOW);
  } else if (currentCommand == 'N') {
    digitalWrite(LED_PIN, HIGH);
  } else if (currentCommand == 'B') {
    // Blink every 500ms when command is B
    if (currentMillis - lastBlinkToggle >= 500) {
      lastBlinkToggle = currentMillis;
      blinkState = !blinkState;
      digitalWrite(LED_PIN, blinkState ? HIGH : LOW);
    }
  }
}