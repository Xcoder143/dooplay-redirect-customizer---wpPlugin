/**
 * Final rc.js — Option D + AdBlock + First-Click Popup
 */

document.addEventListener("DOMContentLoaded", function () {
  const cfg = window.dprcServer || window.dprcOptions || {};
  const timeout = parseInt(cfg.timeout || 5, 10);
  const target = cfg.target || window.dprc_target || '';
  const behavior = cfg.timer_behavior || cfg.timerBehavior || 'continue-btn';

  // DOM Elements
  const card = document.querySelector('.dprc-card');
  const desktopTimerNum = document.querySelector('.dprc-timer-number');
  const progressBar = document.querySelector('.dprc-progress-bar');
  const desktopTimerWrap = document.querySelector('.dprc-desktop-timer');
  const mobileCircleWrap = document.querySelector('.dprc-circle-timer');
  const circleProgress = document.querySelector('.dprc-circle-progress');
  const circleNumber = document.querySelector('.dprc-circle-number');
  const bigBtn = document.querySelector('.dprc-progress-btn');
  const footer = document.querySelector('.dprc-footer');

  const tplTarget = (window.dprcServer && window.dprcServer.target) ? window.dprcServer.target : target;
  const finalTarget = tplTarget || cfg.target || '';

  if (!finalTarget) {
    console.warn('DPRC: no redirect target found');
    return;
  }

  // Init UI
  card && setTimeout(()=>card.classList.add('dprc-ready'), 60);
  if (bigBtn) {
    bigBtn.style.display = 'none';
    bigBtn.classList.remove('dprc-show');
  }

  // --- 1. AdBlock Detection ---
  let isAdBlockActive = false;
  const bait = document.querySelector('.adsbox'); 
  if (bait) {
      // AdBlockers usually set display:none or height:0 on elements with class 'adsbox'
      if (bait.offsetHeight === 0 || bait.clientHeight === 0 || window.getComputedStyle(bait).display === 'none') {
          isAdBlockActive = true;
          // Removed intrusive alert: alert('AdBlock Detected! Please disable AdBlock to continue.');
          console.log('DPRC: AdBlock detected, timer paused.');
      }
  }

  // Countdown Logic
  const isMobile = window.matchMedia('(max-width:730px)').matches;
  const endAt = Date.now() + (timeout * 1000);
  const circleMax = 314;

  function tick() {
    if (isAdBlockActive) return; // PAUSE TIMER if AdBlock is on

    const now = Date.now();
    const timeLeftF = Math.max(0, (endAt - now) / 1000);
    const timeLeft = Math.ceil(timeLeftF - 0.0001);

    if (!isMobile && desktopTimerNum) desktopTimerNum.textContent = timeLeft;
    if (isMobile && circleNumber) circleNumber.textContent = timeLeft;

    const percent = Math.min(100, ((timeout - timeLeftF) / timeout) * 100);

    if (!isMobile && progressBar) progressBar.style.width = percent + '%';
    if (isMobile && circleProgress) {
      const offset = circleMax - (circleMax * (percent/100));
      circleProgress.style.strokeDashoffset = offset;
    }

    if (timeLeftF <= 0) {
      finish();
      return;
    }
    const msToNext = Math.max(200, 1000 - (Date.now() % 1000));
    setTimeout(tick, msToNext);
  }

  function finish() {
    try {
      if (desktopTimerWrap) {
          desktopTimerWrap.classList.add('dprc-hide');
          desktopTimerWrap.style.display = 'none'; 
      }
      if (progressBar) {
          progressBar.parentElement && progressBar.parentElement.classList.add('dprc-hide');
          if (progressBar.parentElement) progressBar.parentElement.style.display = 'none'; 
      }
      if (mobileCircleWrap) {
          mobileCircleWrap.classList.add('dprc-hide');
          mobileCircleWrap.style.display = 'none'; 
      }

      if (bigBtn) {
        bigBtn.style.display = 'block';
        setTimeout(()=>{
          bigBtn.classList.add('dprc-show');
          bigBtn.style.opacity = '1';
        }, 120);
      }

      if (footer) footer.textContent = 'You may continue now.';

      if (behavior === 'auto') {
        window.location.href = finalTarget;
      }
    } catch (e) {
      console.error('DPRC finish error', e);
      window.location.href = finalTarget;
    }
  }

  // --- 2. First-Click Popup Logic ---
  let btnClickedOnce = false;

  if (bigBtn) {
    bigBtn.addEventListener('click', function (e) {
      e.preventDefault();

      // STEP A: First Click (Trigger Popup/Ad)
      if (!btnClickedOnce) {
          btnClickedOnce = true;
          
          // We let the click happen (which opens popunders on body), but we DO NOT redirect yet.
          
          bigBtn.textContent = "Click one more time"; 
          bigBtn.style.opacity = '0.8';
          setTimeout(()=>bigBtn.style.opacity='1', 200); // Flash effect
          
          return; 
      }

      // STEP B: Second Click (Track & Redirect)
      bigBtn.style.opacity = "0.7";
      bigBtn.style.pointerEvents = "none";
      bigBtn.textContent = "Processing...";

      const data = new FormData();
      data.append('action', 'dprc_track_click');
      data.append('post_id', window.dprcServer.post_id);
      data.append('security', window.dprcServer.nonce); // Added Security Nonce

      fetch(window.dprcServer.ajax_url, {
          method: 'POST',
          body: data
      })
      .then(response => {
          window.location.href = finalTarget;
      })
      .catch(err => {
          console.error('Click track failed', err);
          window.location.href = finalTarget;
      });
    });
  }

  // Start ticking ONLY if AdBlock is NOT detected
  if (!isAdBlockActive) {
      tick();
  }
});