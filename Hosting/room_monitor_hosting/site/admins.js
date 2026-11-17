import { requireAdmin, db, $, wireForm, formatTime, collection, getDocs } from "./common.js";

await requireAdmin("admins");

async function load() {
  const body = $("admin-list").tBodies[0];
  body.replaceChildren();
  let list = [];
  try {
    const snap = await getDocs(collection(db, "admins"));
    list = snap.docs.map((d) => ({ uid: d.id, ...d.data() }));
  } catch (e) {
    return;
  }
  list.sort((a, b) => (a.created_at?.toMillis?.() ?? 0) - (b.created_at?.toMillis?.() ?? 0));
  const names = Object.fromEntries(list.map((a) => [a.uid, a.username]));
  list.forEach((a) => {
    const tr = body.insertRow();
    const labels = ["Username", "Email", "Created by", "Created at"];
    [a.username || "", a.email || "", names[a.created_by] || "-", formatTime(a.created_at)]
      .forEach((text, i) => {
      const td = tr.insertCell();
      td.textContent = text;
      td.dataset.label = labels[i];
    });
  });
}

wireForm($("form-admin"), (fd) => ["create_admin", {
  username: String(fd.get("username")).trim(),
  email: String(fd.get("email")).trim()
}], (result) => {
  if (result.ok) { $("form-admin").reset(); load(); }
});

await load();
