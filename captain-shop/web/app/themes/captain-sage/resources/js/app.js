import.meta.glob([
  '../images/**',
  '../fonts/**',
]);

const initNavigation = () => {
  const navWrapper = document.querySelector('[data-nav-wrapper]');
  const navToggle = document.querySelector('[data-nav-toggle]');

  if (!navWrapper || !navToggle) {
    return;
  }

  const closeMenu = () => {
    navWrapper.classList.remove('is-open');
    navToggle.classList.remove('is-active');
    navToggle.setAttribute('aria-expanded', 'false');
  };

  const openMenu = () => {
    navWrapper.classList.add('is-open');
    navToggle.classList.add('is-active');
    navToggle.setAttribute('aria-expanded', 'true');
  };

  navToggle.addEventListener('click', () => {
    if (navWrapper.classList.contains('is-open')) {
      closeMenu();
      return;
    }

    openMenu();
  });

  document.addEventListener('click', (event) => {
    if (!(event.target instanceof Node)) {
      return;
    }

    if (!navWrapper.contains(event.target) && !navToggle.contains(event.target)) {
      closeMenu();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeMenu();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 860) {
      closeMenu();
    }
  });
};

const initHeaderScrollState = () => {
  const header = document.querySelector('[data-site-header]');
  if (!header) {
    return;
  }

  const syncHeaderState = () => {
    if (window.scrollY > 18) {
      header.classList.add('is-scrolled');
      return;
    }

    header.classList.remove('is-scrolled');
  };

  syncHeaderState();
  window.addEventListener('scroll', syncHeaderState, { passive: true });
};

const initShopMetalFilters = () => {
  const filterWrappers = document.querySelectorAll('[data-metal-filters]');
  if (!filterWrappers.length) {
    return;
  }

  filterWrappers.forEach((wrapper) => {
    const targetSelector = wrapper.getAttribute('data-target-selector') || '.captain-product-card';
    const cards = Array.from(document.querySelectorAll(targetSelector));
    const buttons = Array.from(wrapper.querySelectorAll('[data-metal-filter]'));

    if (!cards.length || !buttons.length) {
      return;
    }

    const setActiveFilter = (selectedValue) => {
      buttons.forEach((button) => {
        const isSelected = button.getAttribute('data-metal-filter') === selectedValue;
        button.classList.toggle('is-active', isSelected);
        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });

      cards.forEach((card) => {
        const cardMetals = (card.getAttribute('data-metal') || '')
          .split(',')
          .map((value) => value.trim())
          .filter(Boolean);

        const shouldShow = selectedValue === 'all'
          || cardMetals.includes(selectedValue)
          || cardMetals.length === 0;

        card.hidden = !shouldShow;
      });
    };

    buttons.forEach((button) => {
      button.addEventListener('click', () => {
        const selectedValue = button.getAttribute('data-metal-filter') || 'all';
        setActiveFilter(selectedValue);
      });
    });

    setActiveFilter('all');
  });
};

const initPublicMarketChart = () => {
  const chartRoot = document.querySelector('[data-captain-public-chart]');
  if (!chartRoot) {
    return;
  }

  const productSelect = chartRoot.querySelector('[data-chart-product]');
  const vendorSelect = chartRoot.querySelector('[data-chart-vendor]');
  const rangeSelect = chartRoot.querySelector('[data-chart-range]');
  const fromInput = chartRoot.querySelector('[data-chart-from]');
  const toInput = chartRoot.querySelector('[data-chart-to]');
  const canvas = chartRoot.querySelector('[data-chart-canvas]');
  const emptyState = chartRoot.querySelector('[data-chart-empty]');
  const payloadNode = chartRoot.querySelector('[data-chart-payload]');

  if (!productSelect || !vendorSelect || !rangeSelect || !fromInput || !toInput || !canvas || !payloadNode) {
    return;
  }

  if (typeof window.Chart === 'undefined') {
    if (emptyState) {
      emptyState.hidden = false;
      emptyState.textContent = 'Chart library failed to load.';
    }
    return;
  }

  const parsePayload = () => {
    try {
      const parsed = JSON.parse(payloadNode.textContent || '{}');
      const products = Array.isArray(parsed.products) ? parsed.products : [];
      const series = parsed.series && typeof parsed.series === 'object' ? parsed.series : {};
      return { products, series };
    } catch (error) {
      return { products: [], series: {} };
    }
  };

  const payload = parsePayload();
  let chartInstance = null;

  const toDateInput = (value) => {
    if (!value) {
      return '';
    }

    const date = value instanceof Date ? value : new Date(value);
    if (Number.isNaN(date.getTime())) {
      return '';
    }

    return date.toISOString().slice(0, 10);
  };

  const setPresetRange = (rangeValue) => {
    if (rangeValue === 'custom') {
      return;
    }

    const now = new Date();
    const start = new Date(now);

    if (rangeValue === '7d') {
      start.setDate(start.getDate() - 7);
    } else if (rangeValue === '30d') {
      start.setDate(start.getDate() - 30);
    } else if (rangeValue === '90d') {
      start.setDate(start.getDate() - 90);
    }

    fromInput.value = toDateInput(start);
    toInput.value = toDateInput(now);
  };

  const normalizeRows = (rawRows) => {
    if (!Array.isArray(rawRows)) {
      return [];
    }

    return rawRows
      .map((row) => {
        if (!row || typeof row !== 'object') {
          return null;
        }

        const timestamp = row.timestamp || row.scraped_at || row.date || null;
        const parsedDate = timestamp ? new Date(timestamp) : null;
        if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
          return null;
        }

        const average = Number(row.average_sell_price);
        const vendors = row.vendors && typeof row.vendors === 'object' ? row.vendors : {};

        const normalizedVendors = Object.entries(vendors).reduce((accumulator, [name, value]) => {
          const numericValue = Number(value);
          if (Number.isFinite(numericValue)) {
            accumulator[name] = numericValue;
          }
          return accumulator;
        }, {});

        return {
          date: parsedDate,
          timestamp: parsedDate.toISOString(),
          average: Number.isFinite(average) ? average : null,
          vendors: normalizedVendors,
        };
      })
      .filter(Boolean)
      .sort((a, b) => a.date - b.date);
  };

  const getAvailableVendors = (rows) => {
    const vendorSet = new Set();
    rows.forEach((row) => {
      Object.keys(row.vendors || {}).forEach((vendorName) => vendorSet.add(vendorName));
    });
    return Array.from(vendorSet).sort((a, b) => a.localeCompare(b));
  };

  const rebuildVendorOptions = (rows) => {
    const current = vendorSelect.value;
    const vendors = getAvailableVendors(rows);

    vendorSelect.innerHTML = '';

    const marketOption = document.createElement('option');
    marketOption.value = 'market';
    marketOption.textContent = 'Market average';
    vendorSelect.appendChild(marketOption);

    vendors.forEach((vendorName) => {
      const option = document.createElement('option');
      option.value = vendorName;
      option.textContent = vendorName;
      vendorSelect.appendChild(option);
    });

    if (current && Array.from(vendorSelect.options).some((option) => option.value === current)) {
      vendorSelect.value = current;
      return;
    }

    vendorSelect.value = 'market';
  };

  const filterRowsByDate = (rows) => {
    const fromValue = fromInput.value;
    const toValue = toInput.value;

    let filtered = rows;

    if (fromValue) {
      const fromDate = new Date(`${fromValue}T00:00:00`);
      if (!Number.isNaN(fromDate.getTime())) {
        filtered = filtered.filter((row) => row.date >= fromDate);
      }
    }

    if (toValue) {
      const toDate = new Date(`${toValue}T23:59:59`);
      if (!Number.isNaN(toDate.getTime())) {
        filtered = filtered.filter((row) => row.date <= toDate);
      }
    }

    return filtered;
  };

  const buildDataset = (rows, vendorValue) => {
    return rows.map((row) => {
      if (vendorValue === 'market') {
        return row.average;
      }

      const vendorPrice = row.vendors?.[vendorValue];
      return Number.isFinite(vendorPrice) ? vendorPrice : null;
    });
  };

  const renderChart = () => {
    const selectedProductSlug = productSelect.value;
    const selectedVendor = vendorSelect.value || 'market';
    const rawRows = payload.series[selectedProductSlug] || [];
    const rows = normalizeRows(rawRows);

    rebuildVendorOptions(rows);

    const vendorAfterRebuild = vendorSelect.value || 'market';
    const filteredRows = filterRowsByDate(rows);
    const dataPoints = buildDataset(filteredRows, vendorAfterRebuild);

    const labels = filteredRows.map((row) =>
      row.date.toLocaleString([], {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      })
    );

    const hasData = dataPoints.some((value) => Number.isFinite(value));

    if (emptyState) {
      emptyState.hidden = hasData;
    }

    if (chartInstance) {
      chartInstance.destroy();
      chartInstance = null;
    }

    if (!hasData) {
      return;
    }

    const datasetLabel = vendorAfterRebuild === 'market'
      ? 'Market average'
      : `${vendorAfterRebuild} quote`;

    chartInstance = new window.Chart(canvas, {
      type: 'line',
      data: {
        labels,
        datasets: [
          {
            label: datasetLabel,
            data: dataPoints,
            spanGaps: true,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 3,
            borderColor: vendorAfterRebuild === 'market' ? '#d4b06b' : '#72b8ff',
            backgroundColor: vendorAfterRebuild === 'market' ? 'rgba(212, 176, 107, 0.2)' : 'rgba(114, 184, 255, 0.18)',
            tension: 0.25,
            fill: true,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: {
              color: '#e6edf8',
            },
          },
        },
        scales: {
          x: {
            ticks: {
              color: '#adb9ce',
            },
            grid: {
              color: 'rgba(170, 181, 201, 0.14)',
            },
          },
          y: {
            ticks: {
              color: '#adb9ce',
              callback(value) {
                return Number(value).toLocaleString();
              },
            },
            grid: {
              color: 'rgba(170, 181, 201, 0.14)',
            },
          },
        },
      },
    });
  };

  const syncRangeInputs = () => {
    const rangeValue = rangeSelect.value || '30d';
    setPresetRange(rangeValue);
    renderChart();
  };

  productSelect.addEventListener('change', renderChart);
  vendorSelect.addEventListener('change', renderChart);
  rangeSelect.addEventListener('change', syncRangeInputs);
  fromInput.addEventListener('change', () => {
    rangeSelect.value = 'custom';
    renderChart();
  });
  toInput.addEventListener('change', () => {
    rangeSelect.value = 'custom';
    renderChart();
  });

  if (!productSelect.value && payload.products.length > 0) {
    productSelect.value = payload.products[0].slug;
  }

  setPresetRange(rangeSelect.value || '30d');
  renderChart();
};

document.addEventListener('DOMContentLoaded', () => {
  initHeaderScrollState();
  initNavigation();
  initShopMetalFilters();
  initPublicMarketChart();
});
