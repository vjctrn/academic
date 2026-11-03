const API = "https://api.regvault.org/api/v1";
const ASSET_URL = "https://api.regvault.org";
const state = { page: 1, limit: 12, query: "", system: "" };
const gamesEl = document.querySelector("#games");
const paginationEl = document.querySelector("#pagination");
const summaryEl = document.querySelector("#results-summary");
const statusEl = document.querySelector("#status");

const setStatus = (message, type = "danger") => {
  statusEl.textContent = message;
  statusEl.className = `alert alert-${type}`;
};

const clearStatus = () => {
  statusEl.textContent = "";
  statusEl.className = "alert d-none";
};

const formatSystem = (system) =>
  system
    .replace(/[-_]/g, " ")
    .replace(/\b\w/g, (letter) => letter.toUpperCase());

const loadSystems = async () => {
  const response = await fetch(`${API}/systems`);
  if (!response.ok) throw new Error("Unable to load systems.");
  const data = await response.json();
  const select = document.querySelector("#system");
  data.systems.forEach(({ system, count }) => {
    const option = document.createElement("option");
    option.value = system;
    option.textContent = `${formatSystem(system)} (${count.toLocaleString()})`;
    select.append(option);
  });
};

const renderGames = (games) => {
  gamesEl.replaceChildren();
  games.forEach((game) => {
    const art = game.has_box_front
      ? `${ASSET_URL}/assets/images/${game.system}/${game.rom_hash}/box_front.png`
      : "";
    const column = document.createElement("div");
    column.className = "col-12 col-sm-6 col-lg-4 col-xl-3";
    column.innerHTML = `
      <article class="card game-card border-0 shadow-sm overflow-hidden">
        ${art ? `<img class="card-img-top game-art" src="${art}" alt="">` : '<div class="game-art-placeholder">No cover art</div>'}
        <div class="card-body d-flex flex-column">
          <p class="small text-secondary mb-1">${formatSystem(game.system)}${game.year ? ` · ${game.year}` : ""}</p>
          <h2 class="h5 card-title">${game.title_en || "Untitled game"}</h2>
          <p class="small text-secondary mb-3">${game.developer || game.publisher || "Developer unknown"}</p>
          <a class="btn btn-outline-dark btn-sm mt-auto" href="view.html?system=${encodeURIComponent(game.system)}&hash=${encodeURIComponent(game.rom_hash)}&page=${state.page}">View details</a>
        </div>
      </article>`;
    gamesEl.append(column);
  });
};

const renderPagination = (total) => {
  const pages = Math.ceil(total / state.limit);
  paginationEl.replaceChildren();
  if (pages <= 1) return;
  const addPage = (label, page, disabled = false, active = false) => {
    const item = document.createElement("li");
    item.className = `page-item${disabled ? " disabled" : ""}${active ? " active" : ""}`;
    item.innerHTML = `<button class="page-link" type="button">${label}</button>`;
    item.querySelector("button").disabled = disabled;
    item.querySelector("button").addEventListener("click", () => {
      state.page = page;
      loadGames();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
    paginationEl.append(item);
  };
  addPage("Previous", state.page - 1, state.page === 1);
  for (let page = 1; page <= pages; page += 1) {
    if (
      pages > 7 &&
      page > 2 &&
      page < pages - 1 &&
      Math.abs(page - state.page) > 1
    )
      continue;
    addPage(page, page, false, page === state.page);
  }
  addPage("Next", state.page + 1, state.page === pages);
};

const loadGames = async () => {
  clearStatus();
  summaryEl.textContent = "Loading games...";
  gamesEl.replaceChildren();
  const params = new URLSearchParams({ page: state.page, limit: state.limit });
  if (state.query) params.set("q", state.query);
  if (state.system) params.set("system", state.system);
  try {
    const response = await fetch(`${API}/browse?${params}`);
    if (!response.ok) throw new Error("Unable to load games.");
    const data = await response.json();
    renderGames(data.games);
    summaryEl.textContent = `${data.total.toLocaleString()} games found`;
    renderPagination(data.total);
  } catch (error) {
    summaryEl.textContent = "";
    setStatus(error.message);
  }
};

document.querySelector("#search-form").addEventListener("submit", (event) => {
  event.preventDefault();
  state.query = document.querySelector("#search").value.trim();
  state.system = document.querySelector("#system").value;
  state.page = 1;
  loadGames();
});

document.querySelector("#limit").addEventListener("change", (event) => {
  state.limit = Number(event.target.value);
  state.page = 1;
  loadGames();
});

Promise.all([loadSystems(), loadGames()]).catch((error) =>
  setStatus(error.message),
);
