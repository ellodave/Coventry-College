jQuery(document).ready(function($){
function countdownTimer() {
        const difference = +new Date("2021-05-15") - +new Date();
        let remaining = "The wait is over. Let the City of Culture 2021 Celebrations begin!";

        if (difference > 0) {
          const parts = {
            days: Math.floor(difference / (1000 * 60 * 60 * 24)),
            hours: Math.floor((difference / (1000 * 60 * 60)) % 24),
            minutes: Math.floor((difference / 1000 / 60) % 60),
            seconds: Math.floor((difference / 1000) % 60)
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
      setInterval(countdownTimer, 1000);

});

jQuery(document).ready(function($){
lottie.loadAnimation({
  container: document.getElementById('nurture-animation'),
  renderer: 'svg',
  loop: true,
  autoplay: true,
  path: '/wp-content/themes/dante-child/assets/pages/city-culture-21/lottie/nurture.json' // the path to the animation json
});
lottie.loadAnimation({
  container: document.getElementById('elevate-animation'),
  renderer: 'svg',
  loop: true,
  autoplay: true,
  name: "Elevate Events Animation",
  path:'/wp-content/themes/dante-child/assets/pages/city-culture-21/lottie/elevate.json'
  //path: 'https://assets5.lottiefiles.com/packages/lf20_kmo5guih.json'
});
lottie.loadAnimation({
  container: document.getElementById('celebrate-animation'),
  renderer: 'svg',
  loop: true,
  autoplay: true,
  path: '/wp-content/themes/dante-child/assets/pages/city-culture-21/lottie/celebrate.json' // the path to the animation json
});

});
