jQuery(document).ready(function($){
function countdownTimer() {
        const difference = +new Date("2022-09-11") - +new Date();
        let remaining = "";

        if (difference > 0) {
          const parts = {
            day: Math.floor(difference / (1000 * 60 * 60 * 24)),
            //hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
            //minutes: Math.floor((difference / 1000 / 60) % 60),
            //seconds: Math.floor((difference / 1000) % 60)
          };

          remaining = Object.keys(parts)
            .map(part => {
              if (!parts[part]) return;
              return `${parts[part]} ${part}`;
            })
            .join(" ");
        }

        document.getElementById("countdown").innerHTML = remaining;
      }

      countdownTimer();
      setInterval(countdownTimer, 86400000);

});
