"use strict";

(function () {
  var page = document.querySelector(".kelulusan-hasil-page");
  if (!page) return;

  var status = page.getAttribute("data-status") || "";
  var env = document.getElementById("hasil-envelope");
  if (env) {
    setTimeout(function () {
      env.classList.add("open");
    }, 320);
  }

  if (status !== "LULUS") return;

  var layer = document.getElementById("celebration-layer");
  if (!layer) return;

  var colors = ["#22c55e", "#facc15", "#60a5fa", "#f472b6", "#fb7185", "#a78bfa"];

  function random(min, max) {
    return Math.random() * (max - min) + min;
  }

  function spawnConfetti(count) {
    for (var i = 0; i < count; i++) {
      var piece = document.createElement("span");
      piece.className = "confetti-piece";
      piece.style.left = random(0, 100) + "vw";
      piece.style.background = colors[Math.floor(Math.random() * colors.length)];
      piece.style.animationDuration = random(2.4, 4.8) + "s";
      piece.style.animationDelay = random(0, 1.8) + "s";
      piece.style.transform = "rotate(" + random(0, 360) + "deg)";
      layer.appendChild(piece);

      (function (el) {
        setTimeout(function () {
          if (el && el.parentNode) el.parentNode.removeChild(el);
        }, 7000);
      })(piece);
    }
  }

  function spawnFirework() {
    var fw = document.createElement("span");
    fw.className = "firework";
    fw.style.left = random(10, 90) + "vw";
    fw.style.top = random(10, 55) + "vh";
    fw.style.color = colors[Math.floor(Math.random() * colors.length)];
    layer.appendChild(fw);

    setTimeout(function () {
      if (fw && fw.parentNode) fw.parentNode.removeChild(fw);
    }, 1100);
  }

  spawnConfetti(70);
  var burstInterval = setInterval(function () {
    spawnConfetti(12);
  }, 1200);

  var fireworkInterval = setInterval(function () {
    spawnFirework();
    if (Math.random() > 0.5) spawnFirework();
  }, 700);

  setTimeout(function () {
    clearInterval(burstInterval);
    clearInterval(fireworkInterval);
  }, 9000);
})();
