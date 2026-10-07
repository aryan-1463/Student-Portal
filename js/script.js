// StudentHub Portal - script.js
document.addEventListener("DOMContentLoaded", function () {

  // Authentication and navigation
  var body = document.body;
  var header = document.querySelector("header");
  var nav = document.querySelector("nav");

  var isLoggedIn = localStorage.getItem("studentHubLoggedIn") === "true";
  var currentUser = null;
  try {
    currentUser = JSON.parse(localStorage.getItem("studentHubUser") || "null");
  } catch (e) {
    currentUser = null;
  }

  // Tag body for CSS visibility controls
  if (isLoggedIn) {
    body.classList.add("is-logged-in");
  } else {
    body.classList.remove("is-logged-in");
  }

  // Current page detection
  var currentPath = window.location.pathname;
  var currentPage = currentPath.split("/").pop().toLowerCase() || "index.html";
  if (currentPage === "") currentPage = "index.html";

  // Route Guard: Protected pages require student login
  var protectedPages = ["dashboard.html", "profile.html", "admin.html", "feedback.html"];
  var guestPages = ["register.html", "login.html"];

  if (!isLoggedIn && (currentPage === "dashboard.html" || currentPage === "profile.html" || currentPage === "admin.html")) {
    sessionStorage.setItem("loginNotice", "Please log in first to access the Student Portal (" + currentPage.replace(".html", "").toUpperCase() + ").");
    window.location.href = "login.html";
    return;
  }

  // Common UI navigation
  if (header) {
    // Top Notice Banner
    var notice = document.createElement("div");
    notice.className = "notice-banner";
    if (isLoggedIn) {
      var displayName = (currentUser && currentUser.name) ? currentUser.name : "Aryan";
      var displayId = (currentUser && currentUser.id) ? currentUser.id : "D26DCE156";
      notice.innerHTML = "<span>Welcome back, " + displayName + " (" + displayId + ")! Student Portal is active.</span><button type='button'>&times;</button>";
    } else {
      notice.innerHTML = "<span>Welcome to StudentHub! Please <a href='login.html' style='color:#ffffff; text-decoration:underline; font-weight:bold;'>Login</a> to access Student Portal & Dashboard.</span><button type='button'>&times;</button>";
    }
    body.insertBefore(notice, header);
    notice.querySelector("button").onclick = function () { notice.classList.add("hidden"); };

    // Setup Navigation Links based on login state
    if (nav) {
      var menuBtn = document.createElement("button");
      menuBtn.className = "menu-button";
      menuBtn.innerHTML = "&#9776; Menu";
      header.insertBefore(menuBtn, nav);
      menuBtn.onclick = function () {
        var open = nav.classList.toggle("nav-open");
        menuBtn.innerHTML = open ? "&#10005; Close" : "&#9776; Menu";
      };

      var navUl = nav.querySelector("ul");
      if (navUl) {
        var allProtectedLinks = ["dashboard.html", "profile.html", "events.html", "feedback.html", "admin.html"];
        var allGuestLinks = ["register.html", "login.html"];

        var navItems = navUl.querySelectorAll("li");
        navItems.forEach(function (li) {
          var a = li.querySelector("a");
          if (!a) return;
          var href = (a.getAttribute("href") || "").split("?")[0].split("#")[0].split("/").pop().toLowerCase();

          if (allProtectedLinks.indexOf(href) !== -1) {
            li.classList.add("nav-auth-required");
            li.style.display = isLoggedIn ? "" : "none";
          } else if (allGuestLinks.indexOf(href) !== -1) {
            li.classList.add("nav-guest-only");
            li.style.display = isLoggedIn ? "none" : "";
          }
        });

        // When logged in: Add student badge and Logout button
        if (isLoggedIn) {
          var existingBadge = navUl.querySelector(".nav-user-badge");
          if (!existingBadge) {
            var badgeLi = document.createElement("li");
            badgeLi.className = "nav-user-badge";
            var badgeSpan = document.createElement("span");
            badgeSpan.className = "user-badge-nav";
            var sName = (currentUser && currentUser.name) ? currentUser.name : "Aryan";
            var sId = (currentUser && currentUser.id) ? currentUser.id : "D26DCE156";
            badgeSpan.innerHTML = "&#128100; " + sName + " (" + sId + ")";
            badgeLi.appendChild(badgeSpan);
            navUl.appendChild(badgeLi);
          }

          var existingLogout = navUl.querySelector(".nav-logout-item");
          if (!existingLogout) {
            var logoutLi = document.createElement("li");
            logoutLi.className = "nav-logout-item";
            var logoutA = document.createElement("a");
            logoutA.href = "#";
            logoutA.className = "logout-btn";
            logoutA.innerHTML = "&#128682; Logout";
            logoutA.title = "Log out of StudentHub";
            logoutA.onclick = function (e) {
              e.preventDefault();
              if (confirm("Are you sure you want to log out of StudentHub?")) {
                localStorage.removeItem("studentHubLoggedIn");
                localStorage.removeItem("studentHubUser");
                alert("You have been logged out successfully.");
                window.location.href = "index.html";
              }
            };
            logoutLi.appendChild(logoutA);
            navUl.appendChild(logoutLi);
          }
        }
      }
    }

    // Theme Switcher (Dark / Light)
    var themeBtn = document.createElement("button");
    themeBtn.className = "theme-button";
    header.appendChild(themeBtn);

    function setTheme(theme) {
      body.classList.toggle("dark-theme", theme === "dark");
      themeBtn.innerHTML = theme === "dark" ? "&#9728; Light" : "&#127769; Dark";
      localStorage.setItem("studentHubTheme", theme);
    }
    setTheme(localStorage.getItem("studentHubTheme") || "light");
    themeBtn.onclick = function () {
      setTheme(body.classList.contains("dark-theme") ? "light" : "dark");
    };
  }

  // Handle Login Form on login.html
  var loginForm = document.getElementById("loginForm") || document.querySelector("form[action='dashboard.html']") || (document.querySelector(".form-container") && document.querySelector(".form-container form"));
  if (loginForm && (currentPage === "login.html" || document.title.indexOf("Login") !== -1)) {
    var noticeMsg = sessionStorage.getItem("loginNotice");
    if (noticeMsg) {
      sessionStorage.removeItem("loginNotice");
      var alertBox = document.getElementById("loginAlert");
      if (!alertBox) {
        alertBox = document.createElement("div");
        alertBox.id = "loginAlert";
        alertBox.className = "login-alert-box";
        loginForm.parentNode.insertBefore(alertBox, loginForm);
      }
      alertBox.style.display = "flex";
      alertBox.innerHTML = "&#128274; " + noticeMsg;
    }

    loginForm.onsubmit = function (e) {
      e.preventDefault();
      var emailInput = document.getElementById("loginEmail");
      var val = (emailInput ? emailInput.value : "").trim();
      var studentId = "D26DCE156";
      var studentName = "Aryan Joshi";
      if (val && val.toUpperCase().indexOf("D26") !== -1) {
        studentId = val.toUpperCase();
      }

      localStorage.setItem("studentHubLoggedIn", "true");
      localStorage.setItem("studentHubUser", JSON.stringify({
        id: studentId,
        name: studentName,
        email: val.indexOf("@") !== -1 ? val : (studentId.toLowerCase() + "@charusat.edu.in"),
        role: "Student"
      }));

      // Redirect to dashboard
      window.location.href = "dashboard.html";
    };
  }

  // Personalize Dashboard greeting if logged in
  if (currentPage === "dashboard.html") {
    var welcomeHeading = document.querySelector("section h2");
    if (welcomeHeading && welcomeHeading.textContent.indexOf("Welcome") !== -1) {
      var dName = (currentUser && currentUser.name) ? currentUser.name : "Aryan Joshi";
      var dId = (currentUser && currentUser.id) ? currentUser.id : "D26DCE156";
      welcomeHeading.innerHTML = "Welcome, " + dName + ' <span style="font-size:0.95rem; font-weight:normal; opacity:0.85;">(' + dId + ")</span>";
    }
  }

  // FAQ Accordion
  document.querySelectorAll(".faq-question").forEach(function (btn) {
    btn.onclick = function () {
      var ans = btn.nextElementSibling;
      var open = btn.getAttribute("aria-expanded") === "true";
      btn.setAttribute("aria-expanded", !open);
      if (ans) ans.hidden = open;
    };
  });

  // Home Page Highlights Slider
  var slides = document.querySelectorAll(".slide");
  var slideIdx = 0;
  if (slides.length > 0) {
    function showSlide(i) { slides.forEach(function (s, idx) { s.hidden = idx !== i; }); }
    showSlide(0);
    var next = document.querySelector(".next-slide");
    var prev = document.querySelector(".previous-slide");
    if (next) next.onclick = function () { slideIdx = (slideIdx + 1) % slides.length; showSlide(slideIdx); };
    if (prev) prev.onclick = function () { slideIdx = (slideIdx - 1 + slides.length) % slides.length; showSlide(slideIdx); };
  }

  // Home Page Popup Modal
  var sModal = document.querySelector(".student-modal");
  var openSModal = document.querySelector(".open-modal");
  if (sModal && openSModal) {
    var closeSModal = sModal.querySelector(".close-modal");
    openSModal.onclick = function () { sModal.hidden = false; };
    if (closeSModal) closeSModal.onclick = function () { sModal.hidden = true; };
    sModal.onclick = function (e) { if (e.target === sModal) sModal.hidden = true; };
  }

  // Registration form validation and captcha
  var regForm = document.getElementById("registrationForm");
  if (regForm) {
    var canvas = document.getElementById("captchaCanvas");
    var captchaInp = document.getElementById("captcha");
    var captcha = "";

    function makeCaptcha(forcedCode) {
      if (!canvas) return;
      if (forcedCode) {
        captcha = forcedCode.trim();
      } else {
        var serverCaptcha = canvas.getAttribute("data-captcha");
        if (serverCaptcha && serverCaptcha.trim()) {
          captcha = serverCaptcha.trim();
        } else {
          var chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
          captcha = "";
          for (var i = 0; i < 5; i++) captcha += chars[Math.floor(Math.random() * chars.length)];
        }
      }
      var ctx = canvas.getContext("2d");
      ctx.fillStyle = "#e0f2fe";
      ctx.fillRect(0, 0, canvas.width, canvas.height);
      ctx.font = "bold 24px Arial";
      ctx.fillStyle = "#1e40af";
      ctx.fillText(captcha, 18, 30);
    }

    function setErr(input, msg) {
      if (!input) return false;
      var err = document.getElementById(input.id + "Error");
      if (err) err.textContent = msg;
      input.classList.toggle("input-error", msg !== "");
      input.setAttribute("aria-invalid", msg !== "");
      return msg === "";
    }

    function checkField(input) {
      if (!input) return false;
      var val = input.value.trim();
      var msg = "";
      if (input.id === "fullName" && !/^[A-Za-z ]{3,40}$/.test(val)) msg = "Name must be 3-40 letters.";
      if (input.id === "email" && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) msg = "Enter a valid email.";
      if (input.id === "mobile" && !/^[6-9][0-9]{9}$/.test(val)) msg = "Enter valid 10-digit mobile.";
      if ((input.id === "course" || input.id === "year") && val === "") msg = "Please make a selection.";
      if (input.id === "gender" && !regForm.querySelector('input[name="gender"]:checked')) msg = "Select your gender.";
      if (input.id === "terms" && !input.checked) msg = "Accept terms to continue.";
      if (input.id === "captcha" && val.toUpperCase() !== captcha) msg = "Captcha incorrect.";
      return setErr(input, msg);
    }

    function checkPass() {
      var pwd = document.getElementById("password");
      var strength = document.getElementById("passwordStrength");
      if (!pwd) return true;
      var val = pwd.value;
      var score = (val.length >= 4) + /[A-Z]/.test(val) + /[0-9]/.test(val) + /[^A-Za-z0-9]/.test(val);
      var msg = (val && val.length < 4) ? "Password must be at least 4 characters." : "";
      setErr(pwd, msg);
      if (strength) {
        strength.textContent = val ? ["", "Weak", "Weak", "Medium", "Strong", "Strong"][score] : "";
        strength.className = "strength-text strength-" + score;
      }
      return msg === "" && val !== "";
    }

    function checkConfirm() {
      var p = document.getElementById("password");
      var cp = document.getElementById("confirmPassword");
      return (!cp || !p) ? true : setErr(cp, cp.value !== p.value ? "Passwords do not match." : "");
    }

    ["fullName", "email", "mobile", "course", "year", "captcha"].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.oninput = el.onchange = function () { checkField(this); };
    });
    regForm.querySelectorAll('input[name="gender"]').forEach(function (r) {
      r.onchange = function () { checkField(document.getElementById("gender")); };
    });
    var terms = document.getElementById("terms");
    if (terms) terms.onchange = function () { checkField(this); };
    var pwd = document.getElementById("password");
    if (pwd) pwd.oninput = function () { checkPass(); checkConfirm(); };
    var cpwd = document.getElementById("confirmPassword");
    if (cpwd) cpwd.oninput = checkConfirm;
    var newCap = document.getElementById("newCaptcha");
    if (newCap) {
      newCap.onclick = function () {
        fetch("register.php?refresh_captcha=1")
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data && data.captcha) {
              canvas.setAttribute("data-captcha", data.captcha);
              makeCaptcha(data.captcha);
            }
          })
          .catch(function () {
            makeCaptcha();
          });
        if (captchaInp) { captchaInp.value = ""; setErr(captchaInp, ""); }
      };
    }

    regForm.onsubmit = function (e) {
      e.preventDefault();
      var ok = true;
      ["fullName", "email", "mobile", "course", "year", "gender", "terms", "captcha"].forEach(function (id) {
        var el = document.getElementById(id);
        if (el && !checkField(el)) ok = false;
      });
      if (!checkPass() || !checkConfirm()) ok = false;
      var res = document.getElementById("formResult");
      if (ok) {
        if (window.location.protocol.indexOf("http") !== -1 && regForm.getAttribute("action")) {
          regForm.submit();
          return;
        }
        if (res) { res.textContent = "Registration completed successfully!"; res.className = "form-result success"; }
        regForm.reset();
        makeCaptcha();
      } else if (res) {
        res.textContent = "Please correct highlighted fields.";
        res.className = "form-result";
      }
    };

    regForm.onreset = function () {
      setTimeout(function () {
        regForm.querySelectorAll(".error-message").forEach(function (e) { e.textContent = ""; });
        regForm.querySelectorAll("[aria-invalid]").forEach(function (i) { i.classList.remove("input-error"); i.setAttribute("aria-invalid", "false"); });
        makeCaptcha();
      }, 0);
    };
    makeCaptcha();
  }

  // Profile management
  var editP = document.getElementById("editProfile");
  var cancelP = document.getElementById("cancelEdit");
  var pForm = document.getElementById("profileForm");
  var pDetails = document.getElementById("profileDetails");

  if (pForm && editP && cancelP && pDetails) {
    editP.onclick = function () {
      pDetails.hidden = true;
      editP.hidden = true;
      pForm.hidden = false;
      var inp = document.getElementById("profileName");
      if (inp) inp.focus();
    };
    cancelP.onclick = function () {
      pForm.hidden = true;
      pDetails.hidden = false;
      editP.hidden = false;
    };
    pForm.onsubmit = function (e) {
      e.preventDefault();
      var msg = document.getElementById("profileMessage");
      if (msg) { msg.textContent = "Profile updated locally!"; msg.className = "form-result success"; }
    };
  }

  // Events explorer
  var grid = document.getElementById("eventsContainer");
  if (!grid) return; // Exit if not on events page

  var evSearch = document.getElementById("eventSearchInput");
  var evCat = document.getElementById("eventCategorySelect");
  var evSort = document.getElementById("eventSortSelect");
  var evSize = document.getElementById("eventPageSizeSelect");
  var evRef = document.getElementById("eventRefreshBtn");
  var evSummary = document.getElementById("eventRecordSummary");
  var evPg = document.getElementById("eventPagination");
  var evCountry = document.getElementById("eventCountrySelect");
  var evState = document.getElementById("eventStateSelect");
  var evCity = document.getElementById("eventCitySelect");
  var evResetLoc = document.getElementById("resetLocationBtn");
  var evPills = document.getElementById("categoryPillsBar");
  var evModal = document.getElementById("eventDetailsModal");
  var evModalBody = document.getElementById("eventModalContent");
  var evClose = document.getElementById("closeEventModalBtn");
  var evToast = document.getElementById("eventToast");

  var events = [];
  var page = 1;
  var perPage = 6;
  var bookmarks = JSON.parse(localStorage.getItem("sh_bookmarks") || "[]");
  var registered = JSON.parse(localStorage.getItem("sh_registrations") || "[]");
  var selCat = "all";
  var favOnly = false;

  var locs = {
    "India": { "Gujarat": ["Ahmedabad", "Surat", "Vadodara"], "Maharashtra": ["Mumbai", "Pune"], "Delhi": ["New Delhi"] },
    "USA": { "California": ["San Francisco", "Los Angeles"], "New York": ["New York City"], "Texas": ["Austin"] },
    "UK": { "England": ["London", "Manchester"], "Scotland": ["Edinburgh"] }
  };

  function toast(msg) {
    if (!evToast) return;
    evToast.textContent = msg;
    evToast.style.display = "block";
    evToast.classList.add("show");
    setTimeout(function () {
      evToast.classList.remove("show");
      setTimeout(function () { evToast.style.display = "none"; }, 300);
    }, 2800);
  }

  function fetchEvents() {
    grid.innerHTML = '<div class="events-status-box"><div class="spinner"></div><p>Loading events...</p></div>';
    fetch("json/events.json")
      .then(function (r) { return r.json(); })
      .then(function (data) {
        events = data;
        localStorage.setItem("cache_events", JSON.stringify(data));
        fillCats();
        render();
      })
      .catch(function () {
        var c = localStorage.getItem("cache_events");
        if (c) { events = JSON.parse(c); fillCats(); render(); }
        else { grid.innerHTML = '<div class="events-status-box events-error-box"><p>Unable to load events.</p></div>'; }
      });
  }

  function fillCats() {
    if (!evCat) return;
    var list = [];
    events.forEach(function (e) { if (list.indexOf(e.category) === -1) list.push(e.category); });
    evCat.innerHTML = '<option value="all">All Categories</option>' + list.sort().map(function (c) {
      return '<option value="' + c + '">' + c + '</option>';
    }).join("");
  }

  function render() {
    var data = events.slice();

    if (favOnly) data = data.filter(function (e) { return bookmarks.indexOf(e.id) !== -1; });
    else if (selCat !== "all") data = data.filter(function (e) { return e.category === selCat; });

    if (evCountry && evCountry.value) data = data.filter(function (e) { return e.country === evCountry.value; });
    if (evState && evState.value) data = data.filter(function (e) { return e.state === evState.value; });
    if (evCity && evCity.value) data = data.filter(function (e) { return e.city === evCity.value; });

    var q = evSearch ? evSearch.value.trim().toLowerCase() : "";
    if (q) {
      data = data.filter(function (e) {
        return (e.title + " " + e.description + " " + e.venue + " " + e.organizer + " " + e.city).toLowerCase().indexOf(q) !== -1;
      });
    }

    var sort = evSort ? evSort.value : "date-asc";
    data.sort(function (a, b) {
      if (sort === "title-asc") return a.title.localeCompare(b.title);
      if (sort === "title-desc") return b.title.localeCompare(a.title);
      if (sort === "date-desc") return new Date(b.date) - new Date(a.date);
      return new Date(a.date) - new Date(b.date);
    });

    var totalPages = Math.ceil(data.length / perPage) || 1;
    if (page > totalPages) page = totalPages;
    var start = (page - 1) * perPage;
    var items = data.slice(start, start + perPage);

    if (evSummary) evSummary.textContent = data.length ? "Showing " + (start + 1) + " - " + (start + items.length) + " of " + data.length + " events" : "Showing 0 events";
    var bCount = document.getElementById("bookmarkCount");
    if (bCount) bCount.textContent = bookmarks.length;

    if (items.length === 0) {
      grid.innerHTML = '<div class="events-status-box"><p>No events found matching your criteria.</p></div>';
      if (evPg) evPg.innerHTML = "";
      return;
    }

    grid.innerHTML = items.map(function (ev) {
      var fav = bookmarks.indexOf(ev.id) !== -1;
      var reg = registered.indexOf(ev.id) !== -1;
      var img = ev.image || "https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&auto=format&fit=crop&q=80";
      var catClass = "badge-cat-" + (ev.category || "").toLowerCase();

      return (
        '<article class="event-card-item" data-id="' + ev.id + '">' +
          '<div class="event-card-media">' +
            '<img src="' + img + '" alt="' + ev.title + '" loading="lazy" class="event-card-img">' +
            '<div class="event-media-gradient"></div>' +
            '<div class="event-media-top-bar">' +
              '<span class="badge badge-event ' + catClass + '">' + (ev.badge || ev.category) + '</span>' +
              '<div class="event-media-actions">' +
                '<span class="event-id-badge">#' + ev.id + '</span>' +
                '<button type="button" class="btn-event-bookmark ' + (fav ? 'active' : '') + '" data-id="' + ev.id + '">' + (fav ? 'â¤ï¸' : 'ðŸ¤') + '</button>' +
              '</div>' +
            '</div>' +
            '<div class="event-media-bottom-bar">' +
              (ev.featured ? '<span class="event-featured-tag">â­ Featured</span>' : '<span></span>') +
              (ev.capacity ? '<span class="event-capacity-tag">👥 ' + ev.capacity + '</span>' : '') +
            '</div>' +
          '</div>' +
          '<div class="event-card-content">' +
            '<h3 class="event-card-title">' + ev.title + '</h3>' +
            '<div class="event-meta-info">' +
              '<p class="meta-item"><span class="meta-icon">📅</span> ' + ev.date + (ev.time ? ' &bull; <small>' + ev.time + '</small>' : '') + '</p>' +
              '<p class="meta-item"><span class="meta-icon">ðŸ“</span> ' + (ev.venue || ev.location) + '</p>' +
              '<p class="meta-item"><span class="meta-icon">ðŸŒ</span> ' + ev.city + ', ' + ev.state + ', ' + ev.country + '</p>' +
            '</div>' +
            '<p class="event-desc">' + ev.description + '</p>' +
            '<div class="event-card-footer">' +
              '<div class="event-organizer-info"><span class="meta-icon">ðŸ¢</span> ' + ev.organizer + '</div>' +
              '<div class="event-card-actions">' +
                '<button type="button" class="btn-card-details" data-id="' + ev.id + '">Details</button>' +
                '<button type="button" class="btn-card-rsvp ' + (reg ? 'registered' : '') + '" data-id="' + ev.id + '">' + (reg ? '✓ Registered' : 'RSVP / Join') + '</button>' +
              '</div>' +
            '</div>' +
          '</div>' +
        '</article>'
      );
    }).join("");

    // Heart Bookmarks
    grid.querySelectorAll(".btn-event-bookmark").forEach(function (b) {
      b.onclick = function (e) {
        e.stopPropagation();
        var id = parseInt(b.dataset.id, 10);
        var idx = bookmarks.indexOf(id);
        if (idx === -1) { bookmarks.push(id); toast("â¤ï¸ Saved to bookmarks!"); }
        else { bookmarks.splice(idx, 1); toast("Removed from bookmarks."); }
        localStorage.setItem("sh_bookmarks", JSON.stringify(bookmarks));
        render();
      };
    });

    // Details Modal Open
    grid.querySelectorAll(".btn-card-details, .event-card-media").forEach(function (el) {
      el.onclick = function () {
        var id = parseInt(el.closest(".event-card-item").dataset.id, 10);
        var ev = events.find(function (x) { return x.id === id; });
        if (!ev || !evModal || !evModalBody) return;

        var reg = registered.indexOf(ev.id) !== -1;
        var fav = bookmarks.indexOf(ev.id) !== -1;
        var img = ev.image || "https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&auto=format&fit=crop&q=80";

        evModalBody.innerHTML = (
          '<div class="modal-event-hero">' +
            '<img src="' + img + '" alt="' + ev.title + '" class="modal-event-hero-img">' +
            '<div class="modal-event-hero-overlay">' +
              '<span class="badge badge-event badge-cat-' + (ev.category||'').toLowerCase() + '">' + (ev.badge || ev.category) + '</span>' +
              '<span class="modal-event-id">#' + ev.id + '</span>' +
            '</div>' +
          '</div>' +
          '<div class="modal-event-details">' +
            '<h2>' + ev.title + '</h2>' +
            '<div class="modal-meta-grid">' +
              '<div class="modal-meta-box"><span>📅</span><div><strong>Date & Time</strong><p>' + ev.date + '<br><small>' + (ev.time || "All Day") + '</small></p></div></div>' +
              '<div class="modal-meta-box"><span>ðŸ“</span><div><strong>Venue</strong><p>' + (ev.venue || ev.location) + '</p></div></div>' +
              '<div class="modal-meta-box"><span>ðŸŒ</span><div><strong>Location</strong><p>' + ev.city + ', ' + ev.state + '<br><small>' + ev.country + '</small></p></div></div>' +
              '<div class="modal-meta-box"><span>ðŸ¢</span><div><strong>Organizer</strong><p>' + ev.organizer + '</p></div></div>' +
            '</div>' +
            '<div class="modal-section"><h4>About Event</h4><p class="modal-desc-full">' + ev.description + '</p></div>' +
            (ev.capacity ? '<div class="modal-capacity-info">👥 Capacity: ' + ev.capacity + '</div>' : '') +
            '<div class="modal-actions-bar">' +
              '<button type="button" class="btn-modal-bookmark ' + (fav ? 'active' : '') + '" id="mFav">' + (fav ? 'â¤ï¸ Bookmarked' : 'ðŸ¤ Bookmark') + '</button>' +
              '<button type="button" class="btn-modal-rsvp ' + (reg ? 'registered' : '') + '" id="mReg">' + (reg ? '✓ Registered' : '🚀 RSVP Now') + '</button>' +
            '</div>' +
          '</div>'
        );

        document.getElementById("mFav").onclick = function () {
          var idx = bookmarks.indexOf(ev.id);
          if (idx === -1) bookmarks.push(ev.id); else bookmarks.splice(idx, 1);
          localStorage.setItem("sh_bookmarks", JSON.stringify(bookmarks));
          render();
          el.click();
        };

        document.getElementById("mReg").onclick = function () {
          var idx = registered.indexOf(ev.id);
          if (idx === -1) { registered.push(ev.id); toast("🎉 Registered for " + ev.title + "!"); }
          else { registered.splice(idx, 1); toast("Registration cancelled."); }
          localStorage.setItem("sh_registrations", JSON.stringify(registered));
          render();
          el.click();
        };

        evModal.style.display = "flex";
        evModal.classList.add("active");
        evModal.removeAttribute("hidden");
        document.body.style.overflow = "hidden";
      };
    });

    // Card RSVP
    grid.querySelectorAll(".btn-card-rsvp").forEach(function (b) {
      b.onclick = function (e) {
        e.stopPropagation();
        var id = parseInt(b.dataset.id, 10);
        var idx = registered.indexOf(id);
        if (idx === -1) { registered.push(id); toast("🎉 Registered successfully!"); }
        else { registered.splice(idx, 1); toast("Registration cancelled."); }
        localStorage.setItem("sh_registrations", JSON.stringify(registered));
        render();
      };
    });

    // Pagination
    if (evPg) {
      if (totalPages <= 1) {
        evPg.innerHTML = "";
      } else {
        var html = '<button type="button" class="pg-btn" ' + (page === 1 ? 'disabled' : '') + ' id="pgPrev">&laquo; Prev</button>';
        for (var p = 1; p <= totalPages; p++) html += '<button type="button" class="pg-btn ' + (p === page ? 'active' : '') + '" data-p="' + p + '">' + p + '</button>';
        html += '<button type="button" class="pg-btn " ' + (page === totalPages ? 'disabled' : '') + ' id="pgNext">Next &raquo;</button>';
        evPg.innerHTML = html;

        var prevBtn = document.getElementById("pgPrev");
        var nextBtn = document.getElementById("pgNext");
        if (prevBtn) prevBtn.onclick = function () { if (page > 1) { page--; render(); } };
        if (nextBtn) nextBtn.onclick = function () { if (page < totalPages) { page++; render(); } };
        evPg.querySelectorAll("[data-p]").forEach(function (btn) {
          btn.onclick = function () { page = parseInt(btn.dataset.p, 10); render(); };
        });
      }
    }
  }

  // Modal Close Helper
  function hideModal() {
    if (!evModal) return;
    evModal.style.display = "none";
    evModal.classList.remove("active");
    evModal.setAttribute("hidden", "true");
    document.body.style.overflow = "";
  }
  if (evClose) evClose.onclick = hideModal;
  if (evModal) evModal.onclick = function (e) { if (e.target === evModal) hideModal(); };
  document.addEventListener("keydown", function (e) { if (e.key === "Escape") hideModal(); });

  // Filter Listeners
  if (evSearch) evSearch.oninput = function () { page = 1; render(); };
  if (evCat) evCat.onchange = function () { selCat = evCat.value; favOnly = false; page = 1; updatePills(); render(); };
  if (evSort) evSort.onchange = function () { render(); };
  if (evSize) evSize.onchange = function () { perPage = parseInt(evSize.value, 10); page = 1; render(); };
  if (evRef) evRef.onclick = fetchEvents;

  function updatePills() {
    if (!evPills) return;
    evPills.querySelectorAll(".cat-pill").forEach(function (pill) {
      var c = pill.dataset.category;
      pill.classList.toggle("active", favOnly ? c === "bookmarked" : c === selCat);
    });
  }

  if (evPills) {
    evPills.onclick = function (e) {
      var pill = e.target.closest(".cat-pill");
      if (!pill) return;
      var c = pill.dataset.category;
      if (c === "bookmarked") { favOnly = true; }
      else { favOnly = false; selCat = c; if (evCat) evCat.value = c; }
      page = 1;
      updatePills();
      render();
    };
  }

  // Location Hierarchy Dropdowns
  if (evCountry && evState && evCity) {
    evCountry.innerHTML = '<option value="">All Countries</option>' + Object.keys(locs).map(function (c) {
      return '<option value="' + c + '">' + c + '</option>';
    }).join("");

    evCountry.onchange = function () {
      var c = evCountry.value;
      evState.innerHTML = '<option value="">All States / Provinces</option>';
      evCity.innerHTML = '<option value="">All Cities</option>';
      evState.disabled = !c;
      evCity.disabled = true;
      if (c && locs[c]) Object.keys(locs[c]).forEach(function (s) { evState.innerHTML += '<option value="' + s + '">' + s + '</option>'; });
      page = 1;
      render();
    };

    evState.onchange = function () {
      var c = evCountry.value, s = evState.value;
      evCity.innerHTML = '<option value="">All Cities</option>';
      evCity.disabled = !s;
      if (c && s && locs[c] && locs[c][s]) locs[c][s].forEach(function (city) { evCity.innerHTML += '<option value="' + city + '">' + city + '</option>'; });
      page = 1;
      render();
    };

    evCity.onchange = function () { page = 1; render(); };

    if (evResetLoc) {
      evResetLoc.onclick = function () {
        evCountry.value = "";
        evState.innerHTML = '<option value="">All States / Provinces</option>';
        evCity.innerHTML = '<option value="">All Cities</option>';
        evState.disabled = true;
        evCity.disabled = true;
        page = 1;
        render();
      };
    }
  }

  fetchEvents();
});
