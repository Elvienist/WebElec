#include <DHT.h>

#define DHT_PIN   2
#define DHT_TYPE  DHT11
#define LED_PIN   8

const unsigned long SEND_INTERVAL  = 2000;   // send a reading every 2 s
const unsigned long BLINK_INTERVAL = 500;    // blink every 500 ms
const unsigned long FAILSAFE_MS    = 10000;  // LED off after 10 s without a reply

DHT dht(DHT_PIN, DHT_TYPE);

char ledMode = 'O';              // O = off, N = steady on, B = blinking
bool blinkState = false;
unsigned long lastSend  = 0;
unsigned long lastBlink = 0;
unsigned long lastReply = 0;

void setup() {
  Serial.begin(9600);
  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);    // LED is off at power-up
  dht.begin();
  lastReply = millis();
}

void loop() {
  unsigned long now = millis();

  // Receive single characters without blocking
  while (Serial.available() > 0) {
    char c = Serial.read();
    if (c == 'O' || c == 'N' || c == 'B') {
      if (c == 'B' && ledMode != 'B') {
        blinkState = true;       // start the blink with the LED on
        lastBlink = now;
      }
      ledMode = c;
      lastReply = now;
    }                            // anything else is ignored
  }

  // Failsafe: no valid reply for 10 s turns the LED off
  if (now - lastReply >= FAILSAFE_MS) {
    ledMode = 'O';
  }

  // Drive the LED (blinking is timed here, so a slow PHP reply never stutters it)
  if (ledMode == 'N') {
    digitalWrite(LED_PIN, HIGH);
  } else if (ledMode == 'B') {
    if (now - lastBlink >= BLINK_INTERVAL) {
      lastBlink = now;
      blinkState = !blinkState;
    }
    digitalWrite(LED_PIN, blinkState ? HIGH : LOW);
  } else {
    digitalWrite(LED_PIN, LOW);
  }

  // Send a reading every 2 s
  if (now - lastSend >= SEND_INTERVAL) {
    lastSend = now;
    float t = dht.readTemperature();   // °C, no scaling or averaging
    if (isnan(t)) {
      Serial.println("E");
    } else {
      Serial.print("T:");
      Serial.println(t, 1);
    }
  }
}
