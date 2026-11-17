"""
Room Monitor bridge (Firebase version).

Replaces ingest.php, rules.php, actions.php, helpers.php and the old bridge.py.
It reads the Arduino over USB, decides the label / LED state / records, writes
them to Firestore, and carries out the commands the website writes there.

Install:   pip install pyserial firebase-admin
Run:       python bridge_firebase.py            (real Arduino)
           python bridge_firebase.py --fake     (no Arduino, 29.5 C)
           python bridge_firebase.py --fake 38  (no Arduino, start at 38 C)

The service account key file must stay OUTSIDE the repo (see SERVICE_ACCOUNT_FILE).
"""
import argparse
import queue
import re
import sys
import threading
import time
from datetime import timedelta, timezone, datetime

import serial
import firebase_admin
from firebase_admin import auth, credentials, firestore
from google.cloud.firestore_v1.base_query import FieldFilter

import json
import urllib.parse
import urllib.request

# ---------------------------------------------------------------- settings
PORT = "COM8"
BAUD = 9600
SERVICE_ACCOUNT_FILE = r"C:\keys\room-monitor-25a3f-firebase-adminsdk-fbsvc-1c9f7e7f14.json"  # keep out of git

BASELINE_MINUTES = 30
CALIBRATE_MINUTES = 5
WARM_RISE = 2.0
SPIKE_RISE = 5.0
HYSTERESIS = 2.0
CUSTOM_HYSTERESIS = 0.5
POSSIBLE_FIRE_TEMP = 35.0
FIRE_TEMP = 40.0
READINGS_KEEP_MINUTES = 120
SIM_NORMAL = 29.0
SIM_ELEVATED = 36.0
SIM_FIRE = 42.0
SIM_SPIKE_ADD = 6.0

HEARTBEAT_SECONDS = 30        # state is rewritten at least this often while readings arrive
TEMP_PUSH_DELTA = 0.5         # a temperature change this big is written right away
DAILY_WRITE_BUDGET = 15000    # past this, small temperature changes are not written

MNL = timezone(timedelta(hours=8))  # Asia/Manila
REPLY_MAP = {"OFF": "O", "ON": "N", "BLINK": "B"}
STARTING_LABEL = {"general": "Calibrating", "fire": "Safe", "custom": "Within Range"}

NOTIFY_FILE = r"C:\keys\notify.json"   # keep out of git
NOTIFY_TEST_DATA = False               # True = also alert for simulated readings
NOTIFY_MIN_GAP_SECONDS = 60            # never send more often than this
SITE_URL = "https://room-monitor-25a3f.web.app/"

# ------------------------------------------------------------------- state
PUBLIC_FIELDS = [
    "running", "mode", "room_name", "silenced", "sim_on", "sim_temp",
    "custom_low", "custom_high", "custom_log_style", "custom_interval_min",
    "last_temp", "last_label", "last_led", "last_sensor_ok",
]

S = {
    "running": False, "mode": "general", "room_name": None, "silenced": False,
    "sim_on": False, "sim_temp": None,
    "custom_low": 25.0, "custom_high": 32.0,
    "custom_log_style": "crossing", "custom_interval_min": 5,
    "last_temp": None, "last_label": "Idle", "last_led": "OFF", "last_sensor_ok": True,
    # internal only, never written to Firestore
    "calibration_start": None, "episode_state": None,
    "last_hour_bucket": None, "last_periodic_at": None,
}

readings = []                 # (datetime, temp, is_test), kept in memory only
command_queue = queue.Queue()
fs = None

last_pushed = {}
last_push_mono = 0.0
last_reading_mono = 0.0
writes_today = 0
writes_day = None


def now():
    return datetime.now(MNL)


def stamp():
    return time.strftime("%H:%M:%S")


# ------------------------------------------------------------- firestore io
def count_write(n=1):
    global writes_today, writes_day
    today = now().date()
    if today != writes_day:
        writes_day, writes_today = today, 0
    writes_today += n


def push_state(force=False):
    """Write state/current only when something changed, or on the heartbeat."""
    global last_pushed, last_push_mono
    recent = time.monotonic() - last_reading_mono <= HEARTBEAT_SECONDS + 5
    key_now = {k: S[k] for k in PUBLIC_FIELDS if k != "last_temp"}
    key_before = {k: last_pushed.get(k) for k in key_now}
    temp, before = S["last_temp"], last_pushed.get("last_temp")
    if temp is None or before is None:
        moved = temp != before
    else:
        moved = abs(temp - before) >= TEMP_PUSH_DELTA
    heartbeat_due = recent and time.monotonic() - last_push_mono >= HEARTBEAT_SECONDS
    over_budget = writes_today >= DAILY_WRITE_BUDGET
    if not (force or key_now != key_before or heartbeat_due or (moved and not over_budget)):
        return
    doc = {k: S[k] for k in PUBLIC_FIELDS}
    if recent:
        doc["last_reading_at"] = firestore.SERVER_TIMESTAMP
    try:
        fs.collection("state").document("current").set(doc, merge=True)
        count_write()
        last_pushed = {k: S[k] for k in PUBLIC_FIELDS}
        last_push_mono = time.monotonic()
    except Exception as e:
        print(f"{stamp()} State write failed: {e}")


def add_record(event, temp, is_test, recorded_at, tmin=None, tmax=None, mode=None):
    doc = {
        "recorded_at": recorded_at,
        "room_name": S["room_name"],
        "mode": mode or S["mode"],
        "temp": temp,
        "min_temp": tmin,
        "max_temp": tmax,
        "event": event,
        "is_test": bool(is_test),
    }
    try:
        fs.collection("records").add(doc)
        count_write()
    except Exception as e:
        print(f"{stamp()} Record write failed: {e}")


def load_state():
    """Restore settings after a restart. Tracking (baseline, episodes) starts fresh."""
    snap = fs.collection("state").document("current").get()
    if snap.exists:
        data = snap.to_dict()
        for k in PUBLIC_FIELDS:
            if k in data:
                S[k] = data[k]
    S["last_led"] = "OFF"
    S["silenced"] = False
    reset_tracking()
    if S["running"] and S["room_name"]:
        S["last_label"] = STARTING_LABEL.get(S["mode"], "Normal")
    push_state(force=True)


def reset_tracking():
    S["calibration_start"] = None
    S["episode_state"] = None
    S["silenced"] = False
    S["last_periodic_at"] = None
    S["last_hour_bucket"] = None


# ------------------------------------------------------------------- rules
def evaluate(temp, is_test, t):
    """Mode rules. Runs before the current reading is stored, so the baseline excludes it."""
    mode = S["mode"]
    label, led, event = "Normal", "OFF", None
    episode = S["episode_state"]

    cutoff = t - timedelta(minutes=BASELINE_MINUTES)
    vals = [v for (ts, v, test) in readings if test == is_test and cutoff <= ts < t]
    baseline = sum(vals) / len(vals) if vals else None

    if mode == "general":
        if S["calibration_start"] is None:
            S["calibration_start"] = t
        if not is_test and (t - S["calibration_start"]) < timedelta(minutes=CALIBRATE_MINUTES):
            return {"label": "Calibrating", "led": "OFF", "event": None}
        if baseline is None:
            baseline = temp
        warming = baseline + WARM_RISE
        spike = baseline + SPIKE_RISE
        spike_end = spike - HYSTERESIS
        in_spike = episode == "SPIKE"
        if not in_spike and temp >= spike:
            label, led, event, episode = "Spike", "BLINK", "SPIKE", "SPIKE"
        elif in_spike and temp >= spike_end:
            label, led = "Spike", "BLINK"
        else:
            if in_spike and temp < spike_end:
                episode = None
            label = "Warming" if temp >= warming else "Normal"

    elif mode == "fire":
        in_fire = episode == "FIRE"
        in_elevated = episode == "POSSIBLE_FIRE"
        fire_end = FIRE_TEMP - HYSTERESIS
        elevated_end = POSSIBLE_FIRE_TEMP - HYSTERESIS
        if temp >= FIRE_TEMP or (in_fire and temp >= fire_end):
            label, led = "Fire Detected", "BLINK"
            if not in_fire:
                event, episode = "FIRE", "FIRE"
        elif temp >= POSSIBLE_FIRE_TEMP or ((in_fire or in_elevated) and temp >= elevated_end):
            label = "Elevated"
            if not in_fire and not in_elevated:
                event, episode = "POSSIBLE_FIRE", "POSSIBLE_FIRE"
            elif in_fire and temp < fire_end:
                episode = "POSSIBLE_FIRE"
        else:
            label, episode = "Safe", None

    elif mode == "custom":
        low, high = float(S["custom_low"]), float(S["custom_high"])
        style = S["custom_log_style"]
        low_threshold = low + CUSTOM_HYSTERESIS
        high_threshold = high - CUSTOM_HYSTERESIS
        in_high = episode == "TOO_HIGH"
        in_low = episode == "TOO_LOW"
        if temp > high or (in_high and temp > high_threshold):
            label, led = "Too High", "BLINK"
            if not in_high:
                episode = "TOO_HIGH"
                if style == "crossing":
                    event = "LIMIT_HIGH"
        elif temp < low or (in_low and temp < low_threshold):
            label, led = "Too Low", "BLINK"
            if not in_low:
                episode = "TOO_LOW"
                if style == "crossing":
                    event = "LIMIT_LOW"
        else:
            label, episode = "Within Range", None
        if style == "periodic":
            last = S["last_periodic_at"]
            gap = (t - last).total_seconds() if last else float("inf")
            if gap >= int(S["custom_interval_min"]) * 60:
                event = "PERIODIC"
                S["last_periodic_at"] = t

    S["episode_state"] = episode
    return {"label": label, "led": led, "event": event}


def check_hourly(is_test, t):
    """General mode: when the clock hour rolls over, write one HOURLY record for the hour that ended."""
    if S["mode"] != "general":
        return
    current = t.replace(minute=0, second=0, microsecond=0)
    last = S["last_hour_bucket"]
    if last is None:
        S["last_hour_bucket"] = current
        return
    if current != last:
        vals = [v for (ts, v, test) in readings if test == is_test and last <= ts < current]
        if vals:
            add_record("HOURLY", round(sum(vals) / len(vals), 1), is_test, last,
                       min(vals), max(vals), mode="general")
        S["last_hour_bucket"] = current


def write_partial_hour():
    """HOURLY record for the current hour so far (used on Off, Change Room, leaving General)."""
    if not S["running"] or S["mode"] != "general" or not S["room_name"]:
        return
    t = now()
    start = t.replace(minute=0, second=0, microsecond=0)
    vals = [v for (ts, v, test) in readings if test == S["sim_on"] and ts >= start]
    if vals:
        add_record("HOURLY", round(sum(vals) / len(vals), 1), S["sim_on"], t,
                   min(vals), max(vals), mode="general")

notify_cfg = {}
last_notify_mono = 0.0


def load_notify_config():
    global notify_cfg
    try:
        with open(NOTIFY_FILE, encoding="utf-8") as f:
            notify_cfg = json.load(f)
        on = [k for k in ("telegram",) if notify_cfg.get(k)]
        print(f"{stamp()} Notifications: {', '.join(on) or 'none configured'}")
    except FileNotFoundError:
        print(f"{stamp()} Notifications off (no {NOTIFY_FILE})")
    except Exception as e:
        print(f"{stamp()} Notification settings unreadable: {type(e).__name__}")


def send_telegram(text):
    cfg = notify_cfg.get("telegram")
    if not cfg:
        return
    url = f"https://api.telegram.org/bot{cfg['bot_token']}/sendMessage"
    for chat_id in cfg.get("chat_ids", []):
        data = urllib.parse.urlencode({"chat_id": chat_id, "text": text}).encode()
        try:
            urllib.request.urlopen(url, data=data, timeout=10).read()
        except Exception as e:
            print(f"{stamp()} Telegram failed for {chat_id}: {type(e).__name__}")

def notify_alert(label, temp, is_test):
    """Runs in a background thread so a slow network never delays the Arduino's reply."""
    global last_notify_mono
    if is_test and not NOTIFY_TEST_DATA:
        return
    if time.monotonic() - last_notify_mono < NOTIFY_MIN_GAP_SECONDS:
        return
    last_notify_mono = time.monotonic()
    room = S["room_name"] or "?"
    text = (f"{'[TEST] ' if is_test else ''}ALERT: {label}\n"
            f"Room: {room}\nTemperature: {temp:.1f} C\nMode: {S['mode']}\n"
            f"Time: {now().strftime('%Y-%m-%d %H:%M:%S')}\n{SITE_URL}")

    def work():
        send_telegram(text)

    threading.Thread(target=work, daemon=True).start()

def handle_reading(raw_temp, error):
    """One reading in, one LED word out (the old ingest.php)."""
    global last_reading_mono
    last_reading_mono = time.monotonic()
    t = now()

    is_test = bool(S["sim_on"]) and S["sim_temp"] is not None
    temp = None
    if is_test:
        temp, error = float(S["sim_temp"]), False
    elif not error:
        if raw_temp is None or not (-40 <= raw_temp <= 80):
            error = True
        else:
            temp = round(raw_temp, 1)

    if not (S["running"] and S["room_name"]):
        S["last_led"] = "OFF"
        push_state()
        return "OFF"

    if error:
        S.update(last_sensor_ok=False, last_temp=None, last_label="Sensor Error", last_led="OFF")
        push_state()
        return "OFF"

    check_hourly(is_test, t)
    previous_episode = S["episode_state"]
    if S["episode_state"] in ("SPIKE", "FIRE", "TOO_HIGH", "TOO_LOW") \
        and S["episode_state"] != previous_episode:
            notify_alert(result["label"], temp, is_test)
    result = evaluate(temp, is_test, t)

    readings.append((t, temp, is_test))
    cut = t - timedelta(minutes=READINGS_KEEP_MINUTES)
    readings[:] = [r for r in readings if r[0] >= cut]

    led = result["led"]
    if S["episode_state"] is not None and S["episode_state"] != previous_episode:
        S["silenced"] = False  # a new alert episode clears an old silence
    if led == "BLINK" and S["silenced"]:
        led = "OFF"
    elif led != "BLINK" and S["silenced"]:
        S["silenced"] = False

    if result["event"]:
        add_record(result["event"], temp, is_test, t)

    S.update(last_temp=temp, last_label=result["label"], last_led=led, last_sensor_ok=True)
    push_state()
    return led


# ---------------------------------------------------------------- commands
def to_float(v):
    try:
        return float(v)
    except (TypeError, ValueError):
        return None


def to_int(v):
    f = to_float(v)
    return int(f) if f is not None and f == int(f) else None


def truthy(v):
    return str(v).lower() in ("1", "true", "yes", "on")


def cmd_set_mode(f):
    mode = str(f.get("mode", ""))
    if mode not in ("general", "fire", "custom"):
        return False, "Choose a valid mode."
    new = {"mode": mode}
    if mode == "custom":
        low, high = to_float(f.get("custom_low")), to_float(f.get("custom_high"))
        style = str(f.get("custom_log_style", ""))
        interval = to_int(f.get("custom_interval_min"))
        if low is None or high is None or low >= high:
            return False, "The lower limit must be a number below the upper limit."
        if low < 0 or high > 60:
            return False, "Limits must be between 0 and 60."
        if style not in ("crossing", "periodic"):
            return False, "Choose a logging style."
        if interval is None or not 1 <= interval <= 60:
            return False, "The interval must be 1 to 60 minutes."
        new.update(custom_low=low, custom_high=high,
                   custom_log_style=style, custom_interval_min=interval)
    if S["mode"] == "general" and mode != "general":
        write_partial_hour()
    S.update(new)
    S["last_label"] = STARTING_LABEL[mode]
    reset_tracking()
    return True, "Mode saved."


def cmd_set_room(f):
    if S["room_name"]:
        return False, "A room is already set. Use Change Room first."
    room = str(f.get("room_name", "")).strip()
    if not 1 <= len(room) <= 100:
        return False, "Room name must be 1 to 100 characters."
    S.update(room_name=room, running=True, last_led="OFF",
             last_label=STARTING_LABEL.get(S["mode"], "Normal"))
    reset_tracking()
    return True, "Room set. Monitoring started."


def cmd_change_room(f):
    if not S["room_name"]:
        return False, "No room is set."
    write_partial_hour()
    S.update(running=False, room_name=None, last_led="OFF", last_label="Idle")
    reset_tracking()
    return True, "Room cleared. Enter a new room name to start."


def cmd_toggle_system(f):
    if S["running"]:
        write_partial_hour()
        S.update(running=False, last_led="OFF", last_label="Off")
        reset_tracking()
        return True, "System off."
    if not S["room_name"]:
        return False, "Enter a room name first."
    S.update(running=True, last_led="OFF", last_label=STARTING_LABEL.get(S["mode"], "Normal"))
    reset_tracking()
    return True, "System on."


def cmd_silence(f):
    if S["running"] and S["last_led"] == "BLINK":
        S.update(silenced=True, last_led="OFF")
        return True, "LED silenced until the alert ends."
    return False, "Nothing to silence."


def cmd_test_sim(f):
    if truthy(f.get("sim_on", "0")):
        S["sim_on"] = True
        if S["sim_temp"] is None:
            S["sim_temp"] = SIM_NORMAL
        return True, "Simulation on."
    S.update(sim_on=False, sim_temp=None, episode_state=None)
    return True, "Simulation off."


def cmd_test_scenario(f):
    scenario = str(f.get("scenario", ""))
    temps = {
        "normal": SIM_NORMAL, "elevated": SIM_ELEVATED, "fire": SIM_FIRE,
        "too_low": float(S["custom_low"]) - 3, "too_high": float(S["custom_high"]) + 3,
    }
    if scenario == "spike":
        t = now()
        cut = t - timedelta(minutes=BASELINE_MINUTES)
        if sum(1 for (ts, _, test) in readings if test and ts >= cut) < 60:
            readings.extend((t - timedelta(seconds=12 * i), 29.0, True) for i in range(1, 121))
        vals = [v for (ts, v, test) in readings if test and ts >= cut]
        temps["spike"] = round(sum(vals) / len(vals) + SIM_SPIKE_ADD, 1)
    if scenario not in temps:
        return False, "Unknown scenario."
    S.update(sim_on=True, sim_temp=temps[scenario])
    return True, "Scenario applied."


def cmd_test_manual(f):
    v = to_float(f.get("temp"))
    if v is None or not 0 <= v <= 60:
        return False, "Enter a temperature from 0 to 60."
    S.update(sim_on=True, sim_temp=round(v, 1))
    return True, "Temperature set."


def cmd_test_force_hourly(f):
    if S["mode"] != "general":
        return False, "Switch to General mode first."
    if not S["room_name"]:
        return False, "Set a room first."
    t = now()
    start = t.replace(minute=0, second=0, microsecond=0)
    vals = [v for (ts, v, test) in readings if test == S["sim_on"] and ts >= start]
    if not vals:
        return False, "No readings yet."
    add_record("HOURLY", round(sum(vals) / len(vals), 1), S["sim_on"], t,
               min(vals), max(vals), mode="general")
    return True, "Hourly record written."


def cmd_test_clear(f):
    deleted = 0
    for doc in fs.collection("records").where(filter=FieldFilter("is_test", "==", True)).stream():
        doc.reference.delete()
        deleted += 1
    count_write(deleted)
    readings[:] = [r for r in readings if not r[2]]
    S.update(sim_on=False, sim_temp=None, episode_state=None)
    return True, "Test data cleared."


def cmd_create_admin(f, requester_uid):
    username = str(f.get("username", "")).strip()
    email = str(f.get("email", "")).strip().lower()
    if not re.fullmatch(r"[A-Za-z0-9_.-]{3,50}", username):
        return False, "Username must be 3 to 50 characters of letters, digits, _, ., -."
    if not re.fullmatch(r"[^@\s]+@[^@\s]+\.[^@\s]+", email):
        return False, "Enter a valid email address."
    taken = list(fs.collection("admins").where(filter=FieldFilter("username", "==", username)).limit(1).stream())
    if taken:
        return False, "Username already exists."
    try:
        user = auth.create_user(email=email)
    except auth.EmailAlreadyExistsError:
        return False, "That email already has an account."
    link = auth.generate_password_reset_link(email)
    fs.collection("admins").document(user.uid).set({
        "email": email, "username": username,
        "created_by": requester_uid, "created_at": now(),
    })
    count_write(2)
    return True, f"Admin created. Give them this link to set a password: {link}"


HANDLERS = {
    "set_mode": cmd_set_mode, "set_room": cmd_set_room, "change_room": cmd_change_room,
    "toggle_system": cmd_toggle_system, "silence": cmd_silence,
    "test_sim": cmd_test_sim, "test_scenario": cmd_test_scenario,
    "test_manual": cmd_test_manual, "test_force_hourly": cmd_test_force_hourly,
    "test_clear": cmd_test_clear,
}


def run_command(snap):
    data = snap.to_dict() or {}
    action = str(data.get("action", ""))
    fields = data.get("fields") or {}
    uid = data.get("created_by", "")
    try:
        if not fs.collection("admins").document(uid).get().exists:
            ok, message = False, "Not an admin."
        elif action == "create_admin":
            ok, message = cmd_create_admin(fields, uid)
        elif action in HANDLERS:
            ok, message = HANDLERS[action](fields)
        else:
            ok, message = False, "Unknown action."
    except Exception as e:
        print(f"{stamp()} Command {action} failed: {e}")
        ok, message = False, "Something went wrong. Please try again."
    try:
        snap.reference.update({"status": "done" if ok else "error", "message": message})
        count_write()
    except Exception as e:
        print(f"{stamp()} Could not update command: {e}")
    print(f"{stamp()} Command {action}: {message}")
    push_state(force=True)


def drain_commands():
    while True:
        try:
            snap = command_queue.get_nowait()
        except queue.Empty:
            return
        run_command(snap)


def on_commands(docs, changes, read_time):
    for change in changes:
        if change.type.name == "ADDED":
            command_queue.put(change.document)


# ------------------------------------------------------------------- loops
def open_port():
    ser = serial.Serial(PORT, BAUD, timeout=1)
    time.sleep(2.5)  # the Uno resets when the port opens
    ser.reset_input_buffer()
    return ser


def connect():
    while True:
        try:
            ser = open_port()
            print(f"{stamp()} Serial port {PORT} opened at {BAUD} baud")
            return ser
        except (serial.SerialException, OSError) as e:
            print(f"{stamp()} Could not open {PORT}: {e}")
            print("  Likely reasons: Serial Monitor or another program is using the port, "
                  "the Arduino is unplugged, or the port number is different.")
            drain_commands()
            time.sleep(3)


def run_serial():
    ser = connect()
    while True:
        drain_commands()
        push_state()
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
            value = to_float(line[2:])
            if value is None:
                print(f"{stamp()} Ignored: {line!r}")
                continue
            word = handle_reading(value, False)
        elif line == "E":
            word = handle_reading(None, True)
        else:
            print(f"{stamp()} Ignored: {line!r}")
            continue

        try:
            ser.write(REPLY_MAP[word].encode("ascii"))
        except (serial.SerialException, OSError) as e:
            print(f"{stamp()} Write failed: {e}")
            continue
        print(f"{stamp()} {line} -> {word}")


def run_fake(start_temp):
    fake = {"temp": start_temp, "error": False}

    def listen():
        print("Type a temperature + Enter to change it, 'e' for a sensor error.")
        for text in sys.stdin:
            text = text.strip().lower()
            if text == "e":
                fake["error"] = True
                continue
            value = to_float(text)
            if value is None:
                print("Not a number.")
            else:
                fake["temp"], fake["error"] = value, False

    threading.Thread(target=listen, daemon=True).start()
    while True:
        drain_commands()
        word = handle_reading(fake["temp"], fake["error"])
        shown = "E" if fake["error"] else f"T:{fake['temp']:.1f}"
        print(f"{stamp()} {shown} -> {word}")
        for _ in range(4):
            time.sleep(0.5)
            drain_commands()


def main():
    global fs
    parser = argparse.ArgumentParser()
    parser.add_argument("--fake", nargs="?", const=29.5, type=float, metavar="TEMP",
                        help="send fake readings instead of using the Arduino")
    parser.add_argument("--notify-test", action="store_true", help="send one test message and exit")
    args = parser.parse_args()
    if args.notify_test:
        load_notify_config()
        send_telegram("Room Monitor test message")
        return

    firebase_admin.initialize_app(credentials.Certificate(SERVICE_ACCOUNT_FILE))
    fs = firestore.client()
    load_state()
    load_notify_config()
    fs.collection("commands").where(filter=FieldFilter("status", "==", "pending")).on_snapshot(on_commands)
    print(f"{stamp()} Bridge started" + (" (fake readings)" if args.fake is not None else ""))

    try:
        if args.fake is not None:
            run_fake(args.fake)
        else:
            run_serial()
    except KeyboardInterrupt:
        print("\nStopping bridge.")
    finally:
        try:
            S["last_led"] = "OFF"
            push_state(force=True)
        except Exception:
            pass
        sys.exit(0)


if __name__ == "__main__":
    main()