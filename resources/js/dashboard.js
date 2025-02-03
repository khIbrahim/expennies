import "../css/dashboard.scss";
import Chart from "chart.js/auto";
import { get } from "./ajax";

document.addEventListener("DOMContentLoaded", function () {
    const canvas = document.getElementById("monthlySummaryChart");
    const ctx = canvas.getContext("2d");

    get("/stats/monthlySummaryChart")
        .then((response) => response.json())
        .then((response) => {
            const expensesData = Array(12).fill(0);
            const incomesData = Array(12).fill(0);

            response.forEach(({ month, expense, income }) => {
                expensesData[month - 1] = expense;
                incomesData[month - 1] = income;
            });

            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: [
                        "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
                    ],
                    datasets: [
                        {
                            label: "Expenses",
                            data: expensesData,
                            backgroundColor: "rgba(255, 99, 132, 0.2)",
                            borderColor: "rgba(255, 99, 132, 1)", // Darker red border for expenses
                            borderWidth: 1.5, // Slightly thicker borders
                            fill: true,
                            tension: 0.4, // Smooth curve effect
                            hoverBackgroundColor: "rgba(255, 99, 132, 0.4)", // Lighter red on hover
                            hoverBorderColor: "rgba(255, 99, 132, 1)", // Darker red on hover
                        },
                        {
                            label: "Incomes",
                            data: incomesData,
                            backgroundColor: "rgba(54, 162, 235, 0.2)", // Blue for incomes
                            borderColor: "rgba(54, 162, 235, 1)", // Darker blue border for incomes
                            borderWidth: 1.5, // Slightly thicker borders
                            fill: true,
                            tension: 0.4, // Smooth curve effect
                            hoverBackgroundColor: "rgba(54, 162, 235, 0.4)", // Lighter blue on hover
                            hoverBorderColor: "rgba(54, 162, 235, 1)", // Darker blue on hover
                        },
                    ],
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString(); // Formatting Y-axis with dollar symbol
                                },
                            },
                        },
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                title: function(tooltipItem) {
                                    return `Month: ${tooltipItem[0].label}`;
                                },
                                label: function(tooltipItem) {
                                    const value = tooltipItem.raw;
                                    const label = tooltipItem.dataset.label;
                                    return `${label}: $${value.toLocaleString()}`;
                                },
                            },
                            backgroundColor: "rgba(0, 0, 0, 0.7)", // Dark tooltip background
                            titleColor: "#fff", // White title
                            bodyColor: "#fff", // White text color
                            footerColor: "#fff", // White footer color
                        },
                    },
                    interaction: {
                        mode: "nearest", // Nearest data point on hover
                        axis: "x", // Horizontal hover interaction
                    },
                    elements: {
                        bar: {
                            borderRadius: 8, // Rounded corners for the bars
                        },
                    },
                    animation: {
                        duration: 1000, // Smooth transition for animations
                    },
                },
            });
        })
});
