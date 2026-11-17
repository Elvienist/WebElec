import { requireAdmin, db, $, doc, onSnapshot, wireForm, formatTime, flash } from "./common.js";

await requireAdmin("dashboard");

const SIGNAL_SECONDS = 65;   // heartbeat is written every 30 s, so allow one missed beat
const STATE = { running: "Running", off: "Off", idle: "Idle (enter a room name)" };
const LED = { OFF: "Off", ON: "On", BLINK: "Blinking" };
const MODE = { general: "General", fire: "Fire", custom: "Custom" };
const TONE = {
  Normal: "good", Safe: "good", "Within Range": "good",
  Warming: "warn", Elevated: "warn",
  Spike: "danger", "Fire Detected": "danger", "Too High": "danger",
  "Too Low": "low"
};

let latest = null;
let formsFilled = false;

// The same decisions the old status.php made, now worked out in the browser.
function derive(s) {
  const lastMs = s.last_reading_at && s.last_reading_at.toMillis ? s.last_reading_at.toMillis() : 0;
  const signal = lastMs > 0 && Date.now() - lastMs <= SIGNAL_SECONDS * 1000;
  const hasRoom = !!s.room_name;
  const state = s.running && hasRoom ? "running" : hasRoom ? "off" : "idle";
  let label = s.last_label || "Idle";
  let temp = typeof s.last_temp === "number" ? s.last_temp : null;
  let led = s.last_led || "OFF";
  if (state === "running" && !signal) { label = "No Signal"; temp = null; led = "OFF"; }
  else if (state === "off") { label = "Off"; temp = null; led = "OFF"; }
  else if (state === "idle") { label = "Idle"; temp = null; led = "OFF"; }
  return { state, label, tone: TONE[label] || "muted", temp, led, signal, hasRoom, lastMs };
}

function render(s) {
  const d = derive(s);
  $("temp-value").textContent = d.temp !== null ? d.temp.toFixed(1) : "--";
  const label = $("temp-label");
  label.textContent = d.label;
  label.className = "tone-" + d.tone;
  $("mode-name").textContent = MODE[s.mode] || "--";
  $("room-name").textContent = s.room_name || "--";
  $("system-state").textContent = STATE[d.state];
  $("led-state").textContent = LED[d.led] || "--";
  $("updated-at").textContent = d.lastMs ? formatTime({ toDate: () => new Date(d.lastMs) }).slice(11) : "--";
  $("signal-note").classList.toggle("hidden", d.signal);
  $("sim-banner").classList.toggle("hidden", !s.sim_on);

  $("form-room").classList.toggle("hidden", d.hasRoom);
  $("form-change-room").classList.toggle("hidden", !d.hasRoom);

  const toggle = $("toggle-btn");
  toggle.textContent = d.state === "running" ? "Turn system off" : "Turn system on";
  toggle.className = "btn " + (d.state === "running" ? "btn-red" : "btn-green");

  const sim = $("sim-btn");
  sim.textContent = s.sim_on ? "Turn simulation off" : "Turn simulation on";
  sim.className = "btn " + (s.sim_on ? "btn-red" : "btn-green");
  sim.value = s.sim_on ? "0" : "1";
  $("sim-hint").textContent = "Simulation is " + (s.sim_on ? "ON" : "OFF") + ". When on, the real sensor is ignored.";

  if (!formsFilled) {            // fill the mode form once, so edits are not overwritten
    const f = $("form-mode");
    f.elements.mode.value = s.mode || "general";
    f.elements.custom_low.value = s.custom_low ?? 25;
    f.elements.custom_high.value = s.custom_high ?? 32;
    f.elements.custom_log_style.value = s.custom_log_style || "crossing";
    f.elements.custom_interval_min.value = s.custom_interval_min ?? 5;
    syncCustom();
    formsFilled = true;
  }
}

function syncCustom() {
  $("custom-fields").classList.toggle("hidden", $("form-mode").elements.mode.value !== "custom");
}
$("form-mode").elements.mode.addEventListener("change", syncCustom);

onSnapshot(doc(db, "state", "current"), (snap) => {
  if (!snap.exists()) { flash("error", "No state yet. Start the bridge once so it creates it."); return; }
  latest = snap.data();
  render(latest);
}, () => flash("error", "Cannot read live data. Check your connection and permissions."));

// No new data arrives when the bridge dies, so re-check the signal on a timer.
setInterval(() => { if (latest) render(latest); }, 5000);

wireForm($("form-mode"), (fd) => {
  const fields = { mode: fd.get("mode") };
  if (fields.mode === "custom") {
    fields.custom_low = Number(fd.get("custom_low"));
    fields.custom_high = Number(fd.get("custom_high"));
    fields.custom_log_style = fd.get("custom_log_style");
    fields.custom_interval_min = Number(fd.get("custom_interval_min"));
  }
  return ["set_mode", fields];
});
wireForm($("form-room"), (fd) => ["set_room", { room_name: fd.get("room_name") }]);
wireForm($("form-change-room"), () => ["change_room", {}]);
wireForm($("form-toggle"), () => ["toggle_system", {}]);
wireForm($("form-silence"), () => ["silence", {}]);
wireForm($("form-sim"), (fd, submitter) => ["test_sim", { sim_on: submitter.value }]);
wireForm($("form-scenarios"), (fd, submitter) => ["test_scenario", { scenario: submitter.value }]);
wireForm($("form-manual"), (fd) => ["test_manual", { temp: Number(fd.get("temp")) }]);
wireForm($("form-force-hourly"), () => ["test_force_hourly", {}]);
wireForm($("form-clear-test"), () => ["test_clear", {}]);
