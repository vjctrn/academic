// Store the user's theme choice and update Bootstrap's theme attribute.
const root = document.documentElement;
const themeToggle = document.querySelector("#theme-toggle");
const savedTheme = localStorage.getItem("blockbound-theme") || "light";

function setTheme(theme) {
  const isDark = theme === "dark";
  root.setAttribute("data-bs-theme", isDark ? "dark" : "light");
  themeToggle.textContent = isDark ? "Light mode" : "Dark mode";
  themeToggle.setAttribute("aria-pressed", String(isDark));
  localStorage.setItem("blockbound-theme", isDark ? "dark" : "light");
}

// Toggle is the only custom interaction; Bootstrap handles the navigation.
themeToggle.addEventListener("click", () => {
  setTheme(root.getAttribute("data-bs-theme") === "dark" ? "light" : "dark");
});

setTheme(savedTheme);
