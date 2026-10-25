import sys
import time
import urllib.error
import urllib.parse
import urllib.request

import serial

# Constants from Contracts (Serial and ingest)
PORT = "COM8"
BAUD = 9600
URL = "http://localhost/room_monitor/ingest.php"
KEY = "change-me-bridge-key"
HTTP_TIMEOUT = 3  # seconds

REPLY_MAP = {"OFF": "O", "ON": "N", "BLINK": "B"}


def stamp():
    return time.strftime("%H:%M:%S")


def open_port():
    ser = serial.Serial(PORT, BAUD, timeout=1)
    time.sleep(2.5)            # the Uno resets when the port opens
    ser.reset_input_buffer()   # discard what arrived meanwhile
    return ser


def connect():
    """Keep trying until the port opens."""
    while True:
        try:
            ser = open_port()
            print(f"{stamp()} Serial port {PORT} opened at {BAUD} baud")
            return ser
        except (serial.SerialException, OSError) as e:
            print(f"{stamp()} Could not open {PORT}: {e}")
            print("  Likely reasons: Serial Monitor or another program is using the port, "
                  "the Arduino is unplugged, or the port number is different.")
            time.sleep(3)


def post_reading(fields):
    """Send one reading to ingest.php. Returns OFF, ON, or BLINK, or None on failure."""
    data = urllib.parse.urlencode({"key": KEY, **fields}).encode("ascii")
    try:
        with urllib.request.urlopen(URL, data=data, timeout=HTTP_TIMEOUT) as resp:
            if resp.status != 200:
                print(f"{stamp()} Server returned HTTP {resp.status}")
                return None
            word = resp.read().decode("ascii", errors="ignore").strip()
    except urllib.error.HTTPError as e:
        print(f"{stamp()} Server returned HTTP {e.code}")
        return None
    except Exception as e:  # timeout, connection refused, etc.
        print(f"{stamp()} Request failed: {e}")
        return None

    if word not in REPLY_MAP:
        print(f"{stamp()} Unexpected reply: {word!r}")
        return None
    return word


def main():
    ser = connect()
    try:
        while True:
            try:
                raw = ser.readline()
            except (serial.SerialException, OSError) as e:
                print(f"{stamp()} Port lost: {e}. Reconnecting in 3 s...")
                try:
                    ser.close()
                except Exception:
                    pass
                time.sleep(3)
                ser = connect()
                continue

            line = raw.decode("ascii", errors="ignore").strip()
            if not line:
                continue

            if line.startswith("T:"):
                value = line[2:]
                try:
                    float(value)
                except ValueError:
                    print(f"{stamp()} Ignored: {line!r}")
                    continue
                fields = {"temp": value}
            elif line == "E":
                fields = {"error": "1"}
            else:
                print(f"{stamp()} Ignored: {line!r}")
                continue

            word = post_reading(fields)
            if word is None:
                continue  # send nothing; the Arduino failsafe covers this

            try:
                ser.write(REPLY_MAP[word].encode("ascii"))
            except (serial.SerialException, OSError) as e:
                print(f"{stamp()} Write failed: {e}")
                continue

            print(f"{stamp()} {line} -> {word}")
    except KeyboardInterrupt:
        print("\nStopping bridge.")
    finally:
        try:
            ser.close()
        except Exception:
            pass
        sys.exit(0)


if __name__ == "__main__":
    main()