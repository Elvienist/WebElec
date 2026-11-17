import {
  requireAdmin, db, $, flash, formatTime,
  collection, query, orderBy, limit, getDocs, writeBatch
} from "./common.js";

await requireAdmin("records");

const PAGE_SIZE = 50;
const LOAD_LIMIT = 500;
const EVENT = {
  HOURLY: "Hourly", SPIKE: "Spike", POSSIBLE_FIRE: "Possible fire", FIRE: "Fire detected",
  LIMIT_HIGH: "Above limit", LIMIT_LOW: "Below limit", PERIODIC: "Periodic"
};
const MODE = { general: "General", fire: "Fire", custom: "Custom" };

let rows = [];
let page = 1;

function fill(select, map) {
  Object.entries(map).forEach(([v, t]) => select.add(new Option(t, v)));
}
fill($("f-mode"), MODE);
fill($("f-event"), EVENT);

const num = (v) => (typeof v === "number" ? v.toFixed(1) : null);

function tempText(r) {
  if (r.event === "HOURLY" && num(r.min_temp) !== null && num(r.max_temp) !== null) {
    return num(r.temp) + " (" + num(r.min_temp) + " to " + num(r.max_temp) + ")";
  }
  return num(r.temp) ?? "--";
}

function cell(row, text, label) {
  const td = document.createElement("td");
  td.textContent = text;
  td.dataset.label = label || "";
  row.appendChild(td);
  return td;
}

function filtered() {
  const room = $("f-room").value, mode = $("f-mode").value, event = $("f-event").value;
  return rows.filter((r) => (!room || r.room_name === room) && (!mode || r.mode === mode) && (!event || r.event === event));
}

function render() {
  const list = filtered();
  const pages = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
  page = Math.min(page, pages);
  const area = $("records-area");
  area.replaceChildren();
  $("pager").replaceChildren();

  if (!list.length) {
    const p = document.createElement("p");
    p.className = "empty";
    p.textContent = "No records found.";
    area.appendChild(p);
    return;
  }

  const wrap = document.createElement("div");
  wrap.className = "table-wrap";
  const table = document.createElement("table");
  table.id = "records-table";
  table.className = "table";
  const head = table.createTHead().insertRow();
  ["Time", "Room", "Mode", "Temperature", "Event", ""].forEach((t) => {
    const th = document.createElement("th");
    th.textContent = t;
    head.appendChild(th);
  });
  const body = table.createTBody();
  list.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE).forEach((r) => {
    const tr = body.insertRow();
    if (r.is_test) tr.className = "row-test";
    cell(tr, formatTime(r.recorded_at), "Time");
    cell(tr, r.room_name || "", "Room");
    cell(tr, MODE[r.mode] || r.mode || "", "Mode");
    cell(tr, tempText(r) + " °C", "Temperature");
    cell(tr, EVENT[r.event] || r.event || "", "Event");
    const last = cell(tr, "");
    if (r.is_test) {
      const b = document.createElement("span");
      b.className = "badge badge-test";
      b.textContent = "TEST";
      last.appendChild(b);
    }
  });
  wrap.appendChild(table);
  area.appendChild(wrap);

  const pager = $("pager");
  const link = (text, to) => {
    const a = document.createElement("a");
    a.className = "pager-link";
    a.href = "#";
    a.textContent = text;
    a.addEventListener("click", (e) => { e.preventDefault(); page = to; render(); });
    pager.appendChild(a);
  };
  if (page > 1) link("Previous", page - 1);
  const info = document.createElement("span");
  info.className = "pager-info";
  info.textContent = "Page " + page + " of " + pages + " · " + list.length + " records" +
    (rows.length >= LOAD_LIMIT ? " (newest " + LOAD_LIMIT + " loaded)" : "");
  pager.appendChild(info);
  if (page < pages) link("Next", page + 1);
}

async function load() {
  try {
    const snap = await getDocs(query(collection(db, "records"), orderBy("recorded_at", "desc"), limit(LOAD_LIMIT)));
    rows = snap.docs.map((d) => d.data());
  } catch (e) {
    flash("error", "Cannot load records. Check your connection and permissions.");
    rows = [];
  }
  const keep = $("f-room").value;
  const rooms = [...new Set(rows.map((r) => r.room_name).filter(Boolean))].sort();
  $("f-room").replaceChildren(new Option("All rooms", ""));
  rooms.forEach((r) => $("f-room").add(new Option(r, r)));
  $("f-room").value = rooms.includes(keep) ? keep : "";
  render();
}

$("filters").addEventListener("submit", (e) => { e.preventDefault(); page = 1; render(); });
$("f-reset").addEventListener("click", () => setTimeout(() => { page = 1; render(); }, 0));

// ---- Export: build the CSV, download it, then delete exactly what was exported ----
const esc = (v) => '"' + String(v ?? "").replace(/"/g, '""') + '"';
const safe = (v) => { const s = String(v ?? ""); return s && "=+-@".includes(s[0]) ? "'" + s : s; };
const temp1 = (v) => (typeof v === "number" ? v.toFixed(1) : "");

$("export-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!window.confirm($("export-form").getAttribute("data-confirm"))) return;
  const btn = e.target.querySelector("button");
  btn.disabled = true;
  try {
    const snap = await getDocs(query(collection(db, "records"), orderBy("recorded_at", "asc")));
    if (snap.empty) { flash("error", "Nothing to export"); return; }

    const lines = [["Time", "Room", "Mode", "Temperature", "Min", "Max", "Event", "Test"].map(esc).join(",")];
    snap.docs.forEach((d) => {
      const r = d.data();
      lines.push([
        formatTime(r.recorded_at), safe(r.room_name), r.mode, temp1(r.temp),
        temp1(r.min_temp), temp1(r.max_temp), r.event, r.is_test ? "TEST" : ""
      ].map(esc).join(","));
    });
    if (lines.length - 1 !== snap.size) throw new Error("row count mismatch");

    const stamp = new Date().toLocaleString("sv-SE", { timeZone: "Asia/Manila" }).replace(/\D/g, "");
    const name = "records_" + stamp.slice(0, 8) + "_" + stamp.slice(8) + ".csv";
    const blob = new Blob(["\uFEFF" + lines.join("\r\n") + "\r\n"], { type: "text/csv;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);

    for (let i = 0; i < snap.docs.length; i += 400) {
      const batch = writeBatch(db);
      snap.docs.slice(i, i + 400).forEach((d) => batch.delete(d.ref));
      await batch.commit();
    }
    flash("success", "Exported " + snap.size + " records to " + name + " and cleared them.");
    await load();
  } catch (err) {
    flash("error", "Export failed. Nothing was deleted if the file did not download.");
  } finally {
    btn.disabled = false;
  }
});

await load();
