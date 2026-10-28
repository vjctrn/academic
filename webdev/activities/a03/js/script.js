const form = document.querySelector("#serverForm");
const input = document.querySelector("#serverInput");
const refreshButton = document.querySelector("#refreshButton");
const themeToggle = document.querySelector("#themeToggle");
const serverTypeInputs = document.querySelectorAll("input[name='serverType']");
let activeServer = "";
let activeServerType = "java";

const elements = {
  title: document.querySelector("#serverTitle"),
  badge: document.querySelector("#statusBadge"),
  error: document.querySelector("#errorMessage"),
  online: document.querySelector("#onlineValue"),
  onlineNote: document.querySelector("#onlineNote"),
  players: document.querySelector("#playerValue"),
  maxPlayers: document.querySelector("#maxPlayers"),
  progress: document.querySelector("#playerProgress"),
  version: document.querySelector("#versionValue"),
  protocol: document.querySelector("#protocolValue"),
  address: document.querySelector("#addressValue"),
  port: document.querySelector("#portValue"),
  motd: document.querySelector("#motdValue"),
  checked: document.querySelector("#lastChecked"),
  playerTag: document.querySelector("#playerTag"),
  playerList: document.querySelector("#playerList"),
};

function setLoading(isLoading) {
  const button = form.querySelector("button");
  button.disabled = isLoading;
  button.innerHTML = isLoading
    ? '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Scanning'
    : 'Scan server <i class="bi bi-arrow-up-right ms-1"></i>';
}

function cleanMotd(lines) {
  return (
    (lines || [])
      .join(" ")
      .replace(/§[0-9a-fk-or]/gi, "")
      .trim() || "No message of the day provided."
  );
}

function renderPlayers(players = []) {
  elements.playerList.innerHTML = players.length
    ? players
        .slice(0, 6)
        .map(
          (player) => `
      <div class="player-item col d-flex align-items-center gap-2">
        <span class="player-avatar" aria-hidden="true">${player.name.slice(0, 2).toUpperCase()}</span>
        <span>${player.name}</span>
      </div>`,
        )
        .join("")
    : '<div class="empty-state d-flex align-items-center gap-2"><i class="bi bi-person-plus"></i><span>No player preview available</span></div>';
}

function renderServer(server, address) {
  const online = Boolean(server.online);
  const playersOnline = server.players?.online ?? 0;
  const maxPlayers = server.players?.max ?? 0;
  const percentage = maxPlayers
    ? Math.min((playersOnline / maxPlayers) * 100, 100)
    : 0;

  elements.title.textContent = server.hostname || address;
  elements.badge.textContent = online ? "ONLINE" : "OFFLINE";
  elements.badge.className = `status-badge ${online ? "status-online" : "status-offline"}`;
  elements.online.textContent = online ? "ONLINE" : "OFFLINE";
  elements.onlineNote.textContent = online
    ? "Server is responding"
    : "No signal detected";
  elements.players.textContent = playersOnline.toLocaleString();
  elements.maxPlayers.textContent = maxPlayers.toLocaleString();
  elements.progress.style.width = `${percentage}%`;
  elements.version.textContent = server.version || "Unknown";
  const protocol =
    typeof server.protocol === "object"
      ? (server.protocol?.version ?? server.protocol?.name)
      : server.protocol;
  elements.protocol.textContent = `Protocol ${protocol ?? "—"}`;
  elements.address.textContent = server.ip || address;
  elements.port.textContent = `Port ${server.port || (activeServerType === "bedrock" ? "19132" : "25565")}`;
  elements.motd.textContent = cleanMotd(server.motd?.clean || server.motd?.raw);
  elements.checked.textContent = new Date().toLocaleTimeString([], {
    hour: "2-digit",
    minute: "2-digit",
  });
  elements.playerTag.textContent = `${playersOnline} PLAYER${playersOnline === 1 ? "" : "S"}`;
  renderPlayers(server.players?.list);
}

async function fetchServer(address, type = activeServerType) {
  const normalizedAddress = address.trim();
  if (!normalizedAddress) return;
  activeServer = normalizedAddress;
  activeServerType = type;
  elements.error.classList.add("d-none");
  setLoading(true);
  try {
    const apiPath = type === "bedrock" ? "bedrock/3" : "3";
    const response = await fetch(
      `https://api.mcsrvstat.us/${apiPath}/${encodeURIComponent(normalizedAddress)}`,
    );
    if (!response.ok) throw new Error("The status service returned an error.");
    const server = await response.json();
    renderServer(server, normalizedAddress);
  } catch (error) {
    elements.error.textContent = `Unable to scan ${normalizedAddress}. Check the address and try again.`;
    elements.error.classList.remove("d-none");
    elements.badge.textContent = "ERROR";
    elements.badge.className = "status-badge status-offline";
    console.error(error);
  } finally {
    setLoading(false);
  }
}

form.addEventListener("submit", (event) => {
  event.preventDefault();
  fetchServer(input.value, document.querySelector("input[name='serverType']:checked").value);
});

refreshButton.addEventListener("click", () =>
  fetchServer(activeServer || input.value, activeServerType),
);
serverTypeInputs.forEach((typeInput) => {
  typeInput.addEventListener("change", () => {
    input.placeholder = typeInput.value === "bedrock"
      ? "geo.hivebedrock.network"
      : "play.example.net";
  });
});
document.querySelectorAll(".server-chip").forEach((chip) => {
  chip.addEventListener("click", () => {
    input.value = chip.dataset.server;
    document.querySelector(`#${chip.dataset.serverType}Type`).checked = true;
    fetchServer(input.value, chip.dataset.serverType);
  });
});
themeToggle.addEventListener("click", () => {
  document.body.classList.toggle("dark-mode");
  themeToggle.innerHTML = document.body.classList.contains("dark-mode")
    ? '<i class="bi bi-moon-fill"></i>'
    : '<i class="bi bi-sun-fill"></i>';
});
