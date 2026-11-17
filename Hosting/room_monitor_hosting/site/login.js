import {
  auth, db, $, flash, doc, getDoc, signInWithEmailAndPassword, signOut, onAuthStateChanged
} from "./common.js";

if (new URLSearchParams(location.search).get("denied")) {
  flash("error", "This account is not an admin.");
}

async function isAdmin(user) {
  try {
    return (await getDoc(doc(db, "admins", user.uid))).exists();
  } catch (e) {
    return false;
  }
}

onAuthStateChanged(auth, async (user) => {
  if (user && await isAdmin(user)) location.replace("index.html");
});

$("form-login").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = new FormData(e.target);
  try {
    const cred = await signInWithEmailAndPassword(auth, form.get("email"), form.get("password"));
    if (await isAdmin(cred.user)) {
      location.replace("index.html");
    } else {
      await signOut(auth);
      flash("error", "This account is not an admin.");
    }
  } catch (err) {
    flash("error", "Invalid email or password.");
  }
});
