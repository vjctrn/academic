const API = "https://www.cheapshark.com/api/1.0";
const state = { page: 1, limit: 12, query: "", storeID: "" };
const gamesEl = document.querySelector("#games");
const paginationEl = document.querySelector("#pagination");
const summaryEl = document.querySelector("#results-summary");
const statusEl = document.querySelector("#status");
const stores = new Map();
const fetchLimit = 100;
const usdToPhpRate = getUsdToPhpRate();

const setStatus = (message, type = "danger") => {
  statusEl.textContent = message;
  statusEl.className = `alert alert-${type}`;
};

const clearStatus = () => {
  statusEl.textContent = "";
  statusEl.className = "alert d-none";
};

const request = async (path) => {
  const response = await fetch(`${API}${path}`);
  if (!response.ok) throw new Error("Unable to load CheapShark data.");
  return response.json();
};

const loadStores = async () => {
  const data = await request("/stores");
  const select = document.querySelector("#store");
  data
    .filter((store) => store.isActive)
    .forEach((store) => {
      stores.set(store.storeID, store.storeName);
      const option = document.createElement("option");
      option.value = store.storeID;
      option.textContent = store.storeName;
      select.append(option);
    });
};

const renderDeals = (deals, rate) => {
  gamesEl.replaceChildren();
  deals.forEach((deal) => {
    const column = document.createElement("div");
    column.className = "col-12 col-sm-6 col-lg-4 col-xl-3";
    const savings = Number(deal.savings);
    column.innerHTML = `
      <article class="card game-card border-0 shadow-sm overflow-hidden">
        <img class="card-img-top game-art" src="${deal.thumb}" alt="" loading="lazy">
        <div class="card-body">
          <p class="small text-secondary mb-1">${stores.get(deal.storeID) || "Online store"}</p>
          <h2 class="h5 card-title">${deal.title}</h2>
          <p class="mb-3"><strong>${formatPhp(deal.salePrice, rate)}</strong> <del class="small text-secondary">${formatPhp(deal.normalPrice, rate)}</del></p>
          <span class="badge text-bg-success align-self-start mb-3">${savings.toFixed(0)}% off</span>
          <a class="btn btn-outline-dark btn-sm" href="view.html?id=${encodeURIComponent(deal.gameID)}&page=${state.page}">View deals</a>
        </div>
      </article>`;
    gamesEl.append(column);
  });
};

const renderPagination = (hasNext) => {
  paginationEl.replaceChildren();
  const addPage = (label, page, disabled = false, active = false) => {
    const item = document.createElement("li");
    item.className = `page-item${disabled ? " disabled" : ""}${active ? " active" : ""}`;
    item.innerHTML = `<button class="page-link" type="button">${label}</button>`;
    item.querySelector("button").disabled = disabled;
    item.querySelector("button").addEventListener("click", () => {
      state.page = page;
      loadDeals();
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
    paginationEl.append(item);
  };
  addPage("Previous", state.page - 1, state.page === 1);
  addPage(state.page, state.page, false, true);
  addPage("Next", state.page + 1, !hasNext);
};

const loadDeals = async () => {
  clearStatus();
  summaryEl.textContent = "Loading deals...";
  gamesEl.replaceChildren();
  const params = new URLSearchParams({
    page: 0,
    pageSize: fetchLimit,
    sortBy: "Deal Rating",
  });
  if (state.query) params.set("title", state.query);
  if (state.storeID) params.set("storeID", state.storeID);
  try {
    // ponytail: CheapShark currently repeats page results, so paginate one bounded API batch locally.
    const [allDeals, rate] = await Promise.all([
      request(`/deals?${params}`),
      usdToPhpRate,
    ]);
    const start = (state.page - 1) * state.limit;
    const deals = allDeals.slice(start, start + state.limit);
    renderDeals(deals, rate);
    summaryEl.textContent = allDeals.length
      ? `${start + 1}-${start + deals.length} of ${allDeals.length} deals`
      : "0 deals found";
    renderPagination(start + state.limit < allDeals.length);
  } catch (error) {
    summaryEl.textContent = "";
    setStatus(error.message);
  }
};

document.querySelector("#search-form").addEventListener("submit", (event) => {
  event.preventDefault();
  state.query = document.querySelector("#search").value.trim();
  state.storeID = document.querySelector("#store").value;
  state.page = 1;
  loadDeals();
});

document.querySelector("#limit").addEventListener("change", (event) => {
  state.limit = Number(event.target.value);
  state.page = 1;
  loadDeals();
});

Promise.all([loadStores(), loadDeals()]).catch((error) =>
  setStatus(error.message),
);
