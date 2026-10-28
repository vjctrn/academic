// Cache the theme and mobile-navigation controls.
const root = document.documentElement;
const toggle = document.querySelector("#theme-toggle");
const navToggle = document.querySelector("#nav-toggle");
const mainNavigation = document.querySelector("#main-navigation");
const savedTheme = localStorage.getItem("blockbound-theme") || "light";

function setTheme(theme) {
  // Apply the Bootstrap theme attribute and persist the visitor's preference.
  const dark = theme === "dark";
  root.setAttribute("data-bs-theme", theme);
  toggle.textContent = dark ? "Light Mode" : "Dark Mode";
  toggle.setAttribute("aria-pressed", String(dark));
  localStorage.setItem("blockbound-theme", theme);
}

toggle.addEventListener("click", () => {
  // Switch between the two supported Bootstrap color modes.
  const nextTheme =
    root.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  setTheme(nextTheme);
});

setTheme(savedTheme);

// The navigation uses Bootstrap classes while this handler updates accessibility state.
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
    // Close the mobile menu after an in-page link is selected.
    mainNavigation.classList.remove("show");
    navToggle.setAttribute("aria-expanded", "false");
    navToggle.setAttribute("aria-label", "Open navigation menu");
  });
});
