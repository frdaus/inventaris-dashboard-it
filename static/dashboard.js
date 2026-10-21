const canvas = document.getElementById("categoryChart");

if (canvas && typeof Chart !== "undefined") {
    const labels = categoryData.map(item => item.kategori);
    const values = categoryData.map(item => Number(item.total));

    new Chart(canvas, {
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
                    position: "bottom"
                },
                tooltip: {
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
