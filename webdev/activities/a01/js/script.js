const root = document.documentElement;
const toggle = document.querySelector("#theme-toggle");
const savedTheme = localStorage.getItem("blockbound-theme") || "light";

function setTheme(theme) {
  const dark = theme === "dark";
  root.setAttribute("data-bs-theme", theme);
  toggle.textContent = dark ? "Light Mode" : "Dark Mode";
  toggle.setAttribute("aria-pressed", String(dark));
  localStorage.setItem("blockbound-theme", theme);
}

toggle.addEventListener("click", () => {
  const nextTheme =
    root.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  setTheme(nextTheme);
});

setTheme(savedTheme);
