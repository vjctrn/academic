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

const mobList = document.querySelector("#mob-list");
const biomeList = document.querySelector("#biome-list");
const themeToggle = document.querySelector("#theme-toggle");
const navigation = document.querySelector("#main-navigation");
const navigationToggle = document.querySelector(".navbar-toggler");

function renderMobs() {
  let mobMarkup = "";

  for (let i = 0; i < mobs.length; i += 1) {
    const mob = mobs[i];
    mobMarkup += `
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
        </div>
      `;
  }

  mobList.innerHTML = mobMarkup;
}

function renderBiomes() {
  let biomeMarkup = "";

  for (let i = 0; i < biomes.length; i += 1) {
    const biome = biomes[i];
    biomeMarkup += `
        <div class="col-md-4">
          <article class="biome-card p-4 d-flex gap-3 align-items-center">
            <img class="biome-icon" src="${biome.image}" alt="${biome.name}" />
            <div>
              <h3 class="h5 mb-1">${biome.name}</h3>
              <p class="small mb-2">${biome.detail}</p>
              <span class="badge text-bg-secondary">${biome.status}</span>
            </div>
          </article>
        </div>
      `;
  }

  biomeList.innerHTML = biomeMarkup;
}

function renderStats() {
  const stats = [
    { value: mobs.length, label: "mobs" },
    { value: biomes.length, label: "biomes" },
  ];

  let statsMarkup = "";
  for (let i = 0; i < stats.length; i += 1) {
    const stat = stats[i];
    statsMarkup += `
        <div class="col-6">
          <div class="border border-dark rounded p-3">
            <span class="stat-number">${stat.value}</span>
            <small>${stat.label}            </small>
          </div>
        </div>
      `;
  }

  document.querySelector("#atlas-stats").innerHTML = statsMarkup;
}

themeToggle.addEventListener("click", () => {
  const nextTheme =
    document.documentElement.getAttribute("data-bs-theme") === "dark"
      ? "light"
      : "dark";
  document.documentElement.setAttribute("data-bs-theme", nextTheme);
  themeToggle.textContent = nextTheme === "dark" ? "Light mode" : "Dark mode";
});

navigation.addEventListener("shown.bs.collapse", () => {
  navigationToggle.setAttribute("aria-label", "Close navigation menu");
});

navigation.addEventListener("hidden.bs.collapse", () => {
  navigationToggle.setAttribute("aria-label", "Open navigation menu");
});

const navigationLinks = navigation.querySelectorAll(".nav-link");
for (let i = 0; i < navigationLinks.length; i += 1) {
  const link = navigationLinks[i];
  link.addEventListener("click", () => {
    bootstrap.Collapse.getOrCreateInstance(navigation).hide();
  });
}

renderMobs();
renderBiomes();
renderStats();
