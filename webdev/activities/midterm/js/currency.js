let usdToPhpRatePromise;

const getUsdToPhpRate = () => {
  if (!usdToPhpRatePromise) {
    usdToPhpRatePromise = fetch(
      "https://api.frankfurter.app/latest?from=USD&to=PHP",
    )
      .then((response) => {
        if (!response.ok)
          throw new Error("Unable to load the USD to PHP rate.");
        return response.json();
      })
      .then((data) => {
        const rate = Number(data.rates?.PHP);
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
