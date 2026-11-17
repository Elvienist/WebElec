import { initializeApp } from "https://www.gstatic.com/firebasejs/11.0.2/firebase-app.js";
import {
  getAuth, onAuthStateChanged, signInWithEmailAndPassword, signOut
} from "https://www.gstatic.com/firebasejs/11.0.2/firebase-auth.js";
import {
  getFirestore, doc, getDoc, getDocs, addDoc, collection, query, orderBy, limit,
  onSnapshot, serverTimestamp, writeBatch
} from "https://www.gstatic.com/firebasejs/11.0.2/firebase-firestore.js";
import { firebaseConfig } from "./firebase-config.js";

export const app = initializeApp(firebaseConfig);
export const auth = getAuth(app);
export const db = getFirestore(app);
export {
  onAuthStateChanged, signInWithEmailAndPassword, signOut,
  doc, getDoc, getDocs, addDoc, collection, query, orderBy, limit,
  onSnapshot, serverTimestamp, writeBatch
};

export const $ = (id) => document.getElementById(id);

// Time as "2026-10-24 14:03:22" in Asia/Manila.
export function formatTime(ts) {
  if (!ts || !ts.toDate) return "--";
  return ts.toDate().toLocaleString("sv-SE", { timeZone: "Asia/Manila" });
}

export function flash(type, text) {
  const el = $("flash");
  if (!el) return;
  el.className = "message message-" + (type === "success" ? "success" : "error");
  el.textContent = text;
}

function renderHeader(active) {
  const bar = $("topbar");
  bar.className = "topbar";
  bar.innerHTML =
    '<h1 class="brand">Room Monitor</h1>' +
    '<nav class="tabs">' +
    '<a class="tab' + (active === "dashboard" ? " active" : "") + '" href="index.html">Dashboard</a>' +
    '<a class="tab' + (active === "records" ? " active" : "") + '" href="records.html">Records</a>' +
    '<a class="tab' + (active === "admins" ? " active" : "") + '" href="admins.html">Admins</a>' +
    '<a class="tab tab-logout" id="logout" href="#">Logout</a>' +
    "</nav>";
  $("logout").addEventListener("click", async (e) => {
    e.preventDefault();
    await signOut(auth);
    location.replace("login.html");
  });
}

// Resolves with the signed-in admin, or sends the visitor to the login page.
export function requireAdmin(active) {
  return new Promise((resolve) => {
    onAuthStateChanged(auth, async (user) => {
      if (!user) { location.replace("login.html"); return; }
      try {
        const snap = await getDoc(doc(db, "admins", user.uid));
        if (!snap.exists()) throw new Error("not an admin");
        renderHeader(active);
        document.body.style.visibility = "visible";
        resolve(user);
      } catch (e) {
        await signOut(auth);
        location.replace("login.html?denied=1");
      }
    });
  });
}

// Writes a command for the bridge and waits for its answer.
export async function sendCommand(action, fields = {}) {
  const ref = await addDoc(collection(db, "commands"), {
    action, fields,
    created_by: auth.currentUser.uid,
    created_at: serverTimestamp(),
    status: "pending"
  });
  return new Promise((resolve) => {
    let unsub = null;
    const timer = setTimeout(() => {
      if (unsub) unsub();
      resolve({ ok: false, message: "The bridge did not answer. Check that it is running." });
    }, 15000);
    unsub = onSnapshot(ref, (snap) => {
      const d = snap.data();
      if (d && d.status !== "pending") {
        clearTimeout(timer);
        unsub();
        resolve({ ok: d.status === "done", message: d.message || "" });
      }
    });
  });
}

// Turns a form into a command. build(formData, submitter) returns [action, fields].
export function wireForm(form, build, after) {
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const question = form.getAttribute("data-confirm");
    if (question && !window.confirm(question)) return;
    const [action, fields] = build(new FormData(form), e.submitter);
    const buttons = document.querySelectorAll("button");
    buttons.forEach((b) => { b.disabled = true; });
    flash("success", "Working...");
    try {
      const result = await sendCommand(action, fields);
      flash(result.ok ? "success" : "error", result.message);
      if (after) after(result);
    } catch (err) {
      flash("error", "Could not send the command. Check your connection and permissions.");
    }
    buttons.forEach((b) => { b.disabled = false; });
  });
}
