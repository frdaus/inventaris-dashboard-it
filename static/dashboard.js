const canvas = document.getElementById("categoryChart");
const themeToggle = document.getElementById("theme-toggle");
const themeStorageKey = "inventaris-dashboard-theme";

function applyTheme(theme) {
    const isDark = theme === "dark";

    document.body.classList.toggle("dark-mode", isDark);

    if (themeToggle) {
        themeToggle.textContent = isDark
            ? "☀️ Mode Terang"
            : "🌙 Mode Gelap";

        themeToggle.setAttribute("aria-pressed", String(isDark));
    }

    if (window.categoryChartInstance) {
        const chart = window.categoryChartInstance;
        const textColor = isDark ? "#e6edf7" : "#58647b";
        const tooltipBg = isDark ? "#24344e" : "#ffffff";
        const tooltipText = isDark ? "#e6edf7" : "#172033";

        chart.options.plugins.legend.labels.color = textColor;
        chart.options.plugins.tooltip.backgroundColor = tooltipBg;
        chart.options.plugins.tooltip.titleColor = tooltipText;
        chart.options.plugins.tooltip.bodyColor = tooltipText;
        chart.options.plugins.tooltip.borderColor =
            isDark ? "#394a65" : "#e2e8f2";
        chart.options.plugins.tooltip.borderWidth = 1;

        chart.update();
    }
}

let savedTheme = "light";

try {
    savedTheme = localStorage.getItem(themeStorageKey) || "light";
} catch (error) {
    console.warn("Preferensi tema tidak dapat dibaca dari browser.");
}

applyTheme(savedTheme);

if (themeToggle) {
    themeToggle.addEventListener("click", function () {
        const nextTheme = document.body.classList.contains("dark-mode")
            ? "light"
            : "dark";

        applyTheme(nextTheme);

        try {
            localStorage.setItem(themeStorageKey, nextTheme);
        } catch (error) {
            console.warn("Preferensi tema tidak dapat disimpan.");
        }
    });
}

if (canvas && typeof Chart !== "undefined") {
    const labels = categoryData.map(item => item.kategori);
    const values = categoryData.map(item => Number(item.total));

    window.categoryChartInstance = new Chart(canvas, {
        type: "doughnut",
        data: {
            labels: labels,
            datasets: [{
                label: "Jumlah unit",
                data: values,
                borderWidth: 2,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "65%",
            plugins: {
                legend: {
                    position: "bottom",
                    labels: {
                        color: document.body.classList.contains("dark-mode")
                            ? "#e6edf7"
                            : "#58647b",
                        padding: 18
                    }
                },
                tooltip: {
                    backgroundColor: document.body.classList.contains("dark-mode")
                        ? "#24344e"
                        : "#ffffff",
                    titleColor: document.body.classList.contains("dark-mode")
                        ? "#e6edf7"
                        : "#172033",
                    bodyColor: document.body.classList.contains("dark-mode")
                        ? "#e6edf7"
                        : "#172033",
                    borderColor: document.body.classList.contains("dark-mode")
                        ? "#394a65"
                        : "#e2e8f2",
                    borderWidth: 1,
                    callbacks: {
                        label: function (context) {
                            return `${context.label}: ${context.raw} unit`;
                        }
                    }
                }
            }
        }
    });
}
