const root = document.documentElement;
const toggle = document.querySelector("#theme-toggle");
const navToggle = document.querySelector("#nav-toggle");
const mainNavigation = document.querySelector("#main-navigation");
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

navToggle.addEventListener("click", () => {
  const isOpen = mainNavigation.classList.toggle("show");
  navToggle.setAttribute("aria-expanded", String(isOpen));
  navToggle.setAttribute(
    "aria-label",
    isOpen ? "Close navigation menu" : "Open navigation menu",
  );
});

mainNavigation.querySelectorAll("a").forEach((link) => {
  link.addEventListener("click", () => {
    mainNavigation.classList.remove("show");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Open navigation menu");
  });
});
