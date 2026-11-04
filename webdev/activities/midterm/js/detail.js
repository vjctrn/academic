const params = new URLSearchParams(window.location.search);
const detailsEl = document.querySelector("#details");
const statusEl = document.querySelector("#status");

const showError = (message) => {
  statusEl.textContent = message;
  statusEl.className = "alert alert-danger";
};

const loadGame = async () => {
  const id = params.get("id");
  if (!id) return showError("A game was not specified.");
  const page = params.get("page");
  if (page) {
    document.querySelector("#back-link").href =
      `index.html?page=${encodeURIComponent(page)}`;
  }
  try {
    const [game, rate, storeList] = await Promise.all([
      request(`/games?id=${encodeURIComponent(id)}`),
      getUsdToPhpRate(),
      request("/stores"),
    ]);
    const storeNames = new Map(
      storeList.map((store) => [store.storeID, store.storeName]),
    );
    document.title = `${game.info.title || "Game"} | CheapShark`;
    detailsEl.innerHTML = `
      <div class="row g-0">
        <div class="col-12 col-lg-4 detail-art-panel p-3 p-lg-4">
          <p class="detail-label mb-2">GAME COVER</p>
          <img class="detail-art rounded shadow-sm" src="${game.info.thumb}" alt="Cover art for ${game.info.title}">
        </div>
        <div class="col-12 col-lg-8">
          <div class="card-body p-3 p-lg-4">
            <p class="text-secondary mb-1">DEAL RECORD / CURRENT OFFERS</p>
            <h2 class="display-6 fw-bold">${game.info.title}</h2>
            <p class="lead">Compare the available prices for this game.</p>
            <div class="list-group">
              ${game.deals.map((deal) => `<a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="https://www.cheapshark.com/redirect?dealID=${encodeURIComponent(deal.dealID)}" target="_blank" rel="noopener noreferrer"><span class="flex-grow-1 text-center">${storeNames.get(deal.storeID) || "Online store"}</span><strong>${formatPhp(deal.price, rate)} <small class="text-secondary fw-normal">($${deal.price})</small></strong></a>`).join("")}
            </div>
          </div>
        </div>
      </div>`;
  } catch (error) {
    showError(error.message);
  }
};

loadGame();
