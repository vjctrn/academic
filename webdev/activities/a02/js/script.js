// Local data is the single source for the mob and biome cards.
const mobs = [
  {
    name: "Allay",
    type: "Friendly",
    image: "assets/allay.gif",
    description:
      "A helpful blue spirit that collects items matching what you give it.",
    habitat: "Woodland Mansion",
    difficulty: 1,
    color: "info",
  },
  {
    name: "Creeper",
    type: "Hostile",
    image: "assets/creeper.png",
    description:
      "A silent green surprise with a talent for rearranging your builds.",
    habitat: "Overworld",
    difficulty: 4,
    color: "success",
  },
  {
    name: "Axolotl",
    type: "Friendly",
    image: "assets/axolotl.gif",
    description:
      "A tiny aquatic companion that joins your underwater adventures.",
    habitat: "Lush Caves",
    difficulty: 1,
    color: "info",
  },
  {
    name: "Enderman",
    type: "Neutral",
    image: "assets/enderman.png",
    description:
      "A mysterious tall traveler that dislikes eye contact and water.",
    habitat: "The End",
    difficulty: 3,
    color: "dark",
  },
];

const biomes = [
  {
    name: "Cherry Grove",
    image: "assets/cherry.png",
    detail: "Pink trees and peaceful hills",
    status: "Rare find",
  },
  {
    name: "Deep Dark",
    image: "assets/shrieker.gif",
    detail: "Quiet caves beneath the world",
    status: "Dangerous",
  },
  {
    name: "Mushroom Fields",
    image: "assets/mushroom.png",
    detail: "A strange island without monsters",
    status: "Unusual",
  },
];

// Cache the page targets used by rendering and interaction.
const themeToggle = document.querySelector("#theme-toggle");
const navigation = document.querySelector("#main-navigation");
const theme = document.documentElement;

function renderMobs() {
  document.querySelector("#mob-list").innerHTML = mobs
    .map(
      (mob) => `
        <div class="col-md-6 col-xl-3">
          <article class="mob-card p-3">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <span class="mob-icon bg-${mob.color}">
                <img src="${mob.image}" alt="${mob.name}" />
              </span>
              <span class="badge text-bg-${mob.color}">${mob.type}</span>
            </div>
            <h3 class="h5 pixel-title">${mob.name}</h3>
            <p class="small">${mob.description}</p>
            <div class="d-flex justify-content-between small border-top pt-2">
              <span>Habitat</span><strong>${mob.habitat}</strong>
            </div>
            <div class="mt-3">
              <div class="d-flex justify-content-between small mb-1">
                <span>Adventure level</span><span>${mob.difficulty}/5</span>
              </div>
              <div class="progress" role="progressbar" aria-label="${mob.name} adventure level" aria-valuenow="${mob.difficulty}" aria-valuemin="0" aria-valuemax="5">
                <div class="progress-bar difficulty-bar" style="width: ${mob.difficulty * 20}%"></div>
              </div>
            </div>
          </article>
        </div>`,
    )
    .join("");
}

// Each renderer converts one local data set into Bootstrap grid markup.
function renderBiomes() {
  document.querySelector("#biome-list").innerHTML = biomes
    .map(
      (biome) => `
        <div class="col-md-4">
          <article class="biome-card p-4 d-flex gap-3 align-items-center">
            <img class="biome-icon" src="${biome.image}" alt="${biome.name}" />
            <div>
              <h3 class="h5 mb-1">${biome.name}</h3>
              <p class="small mb-2">${biome.detail}</p>
              <span class="badge text-bg-secondary">${biome.status}</span>
            </div>
          </article>
        </div>`,
    )
    .join("");
}

function renderStats() {
  document.querySelector("#atlas-stats").innerHTML = [
    ["mobs", mobs.length],
    ["biomes", biomes.length],
  ]
    .map(
      ([label, value]) => `
        <div class="col-6">
          <div class="border border-dark rounded p-3">
            <span class="stat-number">${value}</span>
            <small>${label}</small>
          </div>
        </div>`,
    )
    .join("");
}

themeToggle.addEventListener("click", () => {
  const nextTheme =
    theme.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  theme.setAttribute("data-bs-theme", nextTheme);
  themeToggle.textContent = nextTheme === "dark" ? "Light mode" : "Dark mode";
  themeToggle.setAttribute("aria-pressed", String(nextTheme === "dark"));
});

// Close the Bootstrap menu after an in-page navigation click.
navigation.addEventListener("click", (event) => {
  if (event.target.matches(".nav-link")) {
    bootstrap.Collapse.getOrCreateInstance(navigation).hide();
  }
});

renderMobs();
renderBiomes();
renderStats();
