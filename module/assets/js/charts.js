// Charts.js - Charts untuk Dashboard Siswa
$(document).ready(function() {
    initGenderChart();
    initGradeAnimation();
    initMajorAnimation();
});

// Gender Distribution Pie Chart
function initGenderChart() {
    const ctx = document.getElementById('genderPieChart');
    if (!ctx) return;

    // Data dari PHP atau default
    const maleCount = window.chartData ? window.chartData.male : 670;
    const femaleCount = window.chartData ? window.chartData.female : 575;
    const total = maleCount + femaleCount;

    const chart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Laki-laki', 'Perempuan'],
            datasets: [{
                data: [maleCount, femaleCount],
                backgroundColor: [
                    '#3498db', // Blue for male
                    '#e91e63'  // Pink for female
                ],
                borderColor: [
                    '#2980b9',
                    '#c2185b'
                ],
                borderWidth: 2,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        color: '#fff',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.parsed;
                            const percentage = ((value / total) * 100).toFixed(1);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#fff',
                    borderWidth: 1
                }
            },
            cutout: '60%',
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });

    // Add center text
    Chart.register({
        id: 'centerText',
        beforeDraw: function(chart) {
            if (chart.config.type === 'doughnut') {
                const ctx = chart.ctx;
                const centerX = (chart.chartArea.left + chart.chartArea.right) / 2;
                const centerY = (chart.chartArea.top + chart.chartArea.bottom) / 2;
                
                ctx.restore();
                ctx.font = 'bold 16px Arial';
                ctx.fillStyle = '#fff';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                
                const text = total.toLocaleString();
                const text2 = 'Total Siswa';
                
                ctx.fillText(text, centerX, centerY - 5);
                ctx.font = '12px Arial';
                ctx.fillText(text2, centerX, centerY + 15);
                ctx.save();
            }
        }
    });
}

// Grade bars animation
function initGradeAnimation() {
    // Animate grade bars when they come into view
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bars = entry.target.querySelectorAll('.grade-bar .bar-male, .grade-bar .bar-female');
                bars.forEach((bar, index) => {
                    setTimeout(() => {
                        bar.style.transform = 'scaleX(1)';
                        bar.style.opacity = '1';
                    }, index * 200);
                });
            }
        });
    }, {
        threshold: 0.3
    });

    document.querySelectorAll('.grade-bars').forEach(el => {
        observer.observe(el);
    });

    // Initialize bars to scale 0
    document.querySelectorAll('.grade-bar .bar-male, .grade-bar .bar-female').forEach(bar => {
        bar.style.transformOrigin = 'left center';
        bar.style.transform = 'scaleX(0)';
        bar.style.opacity = '0';
        bar.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    });
}

// Major items animation
function initMajorAnimation() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const items = entry.target.querySelectorAll('.major-item');
                items.forEach((item, index) => {
                    setTimeout(() => {
                        item.style.transform = 'translateY(0) scale(1)';
                        item.style.opacity = '1';
                    }, index * 100);
                });

                // Animate major bars
                const bars = entry.target.querySelectorAll('.major-bar-male, .major-bar-female');
                bars.forEach((bar, index) => {
                    setTimeout(() => {
                        bar.style.transform = 'scaleX(1)';
                        bar.style.opacity = '1';
                    }, 500 + (index * 50));
                });
            }
        });
    }, {
        threshold: 0.2
    });

    document.querySelectorAll('.majors-grid').forEach(el => {
        observer.observe(el);
    });

    // Initialize major items
    document.querySelectorAll('.major-item').forEach(item => {
        item.style.transform = 'translateY(30px) scale(0.9)';
        item.style.opacity = '0';
        item.style.transition = 'all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    });

    // Initialize major bars
    document.querySelectorAll('.major-bar-male, .major-bar-female').forEach(bar => {
        bar.style.transformOrigin = 'left center';
        bar.style.transform = 'scaleX(0)';
        bar.style.opacity = '0';
        bar.style.transition = 'all 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275)';
    });
}

// Mini stats cards animation
$(window).on('scroll', function() {
    $('.stats-mini-card').each(function() {
        const elementTop = $(this).offset().top;
        const elementBottom = elementTop + $(this).outerHeight();
        const viewportTop = $(window).scrollTop();
        const viewportBottom = viewportTop + $(window).height();
        
        if (elementBottom > viewportTop && elementTop < viewportBottom) {
            if (!$(this).hasClass('animated')) {
                $(this).addClass('animated');
                
                // Number counting animation
                const numberElement = $(this).find('.stats-mini-number');
                const finalNumber = numberElement.text().replace(/,/g, '');
                
                if (!isNaN(finalNumber) && finalNumber !== '') {
                    let startNumber = 0;
                    const increment = Math.ceil(finalNumber / 50);
                    const timer = setInterval(() => {
                        startNumber += increment;
                        if (startNumber >= finalNumber) {
                            startNumber = finalNumber;
                            clearInterval(timer);
                        }
                        
                        // Format number with commas if it's large
                        const formattedNumber = startNumber > 999 ? 
                            startNumber.toLocaleString() : startNumber;
                        numberElement.text(formattedNumber);
                    }, 30);
                }
            }
        }
    });
});

// Chart responsive behavior
$(window).on('resize', function() {
    // Trigger chart resize if needed
    Chart.helpers.each(Chart.instances, function(instance) {
        instance.resize();
    });
});

// Initialize tooltips for chart elements
$(function () {
    $('[data-bs-toggle="tooltip"]').tooltip();
});

// Add hover effects for stats cards
$('.stats-mini-card').hover(
    function() {
        $(this).addClass('hover-effect');
    },
    function() {
        $(this).removeClass('hover-effect');
    }
);

// Add click tracking for analytics
$('.stats-mini-card').on('click', function() {
    const cardType = $(this).attr('class').match(/\b(total-\w+|\w+-\w+)\b/);
    if (cardType && typeof gtag !== 'undefined') {
        gtag('event', 'stats_card_click', {
            'event_category': 'engagement',
            'event_label': cardType[0]
        });
    }
});

// Refresh chart data function (for dynamic updates)
window.refreshChartData = function() {
    // This can be called to update chart data from server
    // Implementation depends on your backend API
    console.log('Chart data refresh requested');
};

// Export chart as image function
window.exportChart = function(chartId, filename = 'chart') {
    const chart = Chart.getChart(chartId);
    if (chart) {
        const url = chart.toBase64Image();
        const link = document.createElement('a');
        link.download = filename + '.png';
        link.href = url;
        link.click();
    }
};