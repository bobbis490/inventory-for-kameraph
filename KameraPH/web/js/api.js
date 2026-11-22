(function () {
  try {
    const params = new URLSearchParams(location.search);
    const urlToken = params.get("token");
    if (urlToken) {
      localStorage.setItem("kph-auth-token", urlToken);
    }
  } catch (e) { /* ignore storage errors */ }
})();

async function request(method, path, body) {
  let res;
  const headers = { "Content-Type": "application/json" };
  try {
    const token = localStorage.getItem("kph-auth-token");
    if (token) headers["Authorization"] = "Bearer " + token;
  } catch (e) { /* ignore storage errors */ }
  if (typeof KPH !== "undefined" && KPH.testUserId) {
    headers["X-Test-User-Id"] = KPH.testUserId;
  }
  try {
    res = await fetch(KPH.api + path, {
      method: method,
      headers: headers,
      body: body === undefined ? undefined : JSON.stringify(body)
    });
  } catch (e) {
    throw new Error("Cannot reach the server. Check that Apache is running and the address in config.js is correct.");
  }
  let data = null;
  try { data = await res.json(); } catch (e) { /* the answer was not JSON */ }
  if (!res.ok) {
    throw new Error((data && data.error) || "Something went wrong (status " + res.status + ").");
  }
  return data;
}

function apiGet(path) { return request("GET", path); }
function apiSend(method, path, body) { return request(method, path, body); }

function escapeHtml(text) {
  const d = document.createElement("div");
  d.textContent = text == null ? "" : String(text);
  return d.innerHTML;
}

function formatPeso(amount) {
  const n = Number(amount);
  if (Number.isNaN(n)) return "";
  return "₱" + n.toLocaleString("en-PH", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
