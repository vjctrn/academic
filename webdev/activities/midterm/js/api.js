const API = "https://www.cheapshark.com/api/1.0";

const request = async (path) => {
  const response = await fetch(`${API}${path}`);
  if (!response.ok) throw new Error("Unable to load CheapShark data.");
  return response.json();
};

const loadStores = async (select, stores) => {
  const data = await request("/stores");
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
