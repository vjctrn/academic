const themeButton = document.querySelector("#theme-toggle");
const savedTheme = localStorage.getItem("theme") || "light";

const updateTheme = (theme) => {
  document.documentElement.setAttribute("data-bs-theme", theme);
  themeButton.textContent = theme === "dark" ? "☀ Light mode" : "☾ Night mode";
  themeButton.setAttribute("aria-pressed", theme === "dark");
};

updateTheme(savedTheme);

themeButton.addEventListener("click", () => {
  const nextTheme =
    document.documentElement.getAttribute("data-bs-theme") === "dark"
      ? "light"
      : "dark";
  localStorage.setItem("theme", nextTheme);
  updateTheme(nextTheme);
});
