let usdToPhpRatePromise;

const loadUsdRates = (url) =>
  fetch(url).then((response) => {
    if (!response.ok) throw new Error("Unable to load currency rates.");
    return response.json();
  });

const getUsdToPhpRate = () => {
  if (!usdToPhpRatePromise) {
    usdToPhpRatePromise = loadUsdRates(
      "https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/usd.json",
    )
      .catch(() =>
        loadUsdRates(
          "https://latest.currency-api.pages.dev/v1/currencies/usd.json",
        ),
      )
      .then((data) => {
        const rate = Number(data.usd?.php);
        if (!Number.isFinite(rate)) {
          throw new Error("The USD to PHP rate was unavailable.");
        }
        return rate;
      });
  }
  return usdToPhpRatePromise;
};

const formatPhp = (usd, rate) =>
  `₱${(Number(usd) * rate).toLocaleString("en-PH", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
