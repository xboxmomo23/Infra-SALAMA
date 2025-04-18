document.addEventListener("DOMContentLoaded", function () {
  // Initialize AOS
  AOS.init({
    duration: 800,
    easing: "ease-in-out",
    once: true,
  });

  // Progress bar
  window.addEventListener("scroll", function () {
    const winScroll =
      document.body.scrollTop || document.documentElement.scrollTop;
    const height =
      document.documentElement.scrollHeight -
      document.documentElement.clientHeight;
    const scrolled = (winScroll / height) * 100;
    document.getElementById("progressBar").style.width = scrolled + "%";
  });

  // Table of contents active link
  const sections = document.querySelectorAll("h2[id]");
  const tocLinks = document.querySelectorAll("#tableOfContents a");

  window.addEventListener("scroll", function () {
    let current = "";

    sections.forEach((section) => {
      const sectionTop = section.offsetTop - 200;
      const sectionHeight = section.clientHeight;
      if (pageYOffset >= sectionTop) {
        current = section.getAttribute("id");
      }
    });

    tocLinks.forEach((link) => {
      link.classList.remove("active");
      if (link.getAttribute("href").substring(1) === current) {
        link.classList.add("active");
      }
    });
  });

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      if (this.getAttribute("href") !== "#") {
        e.preventDefault();

        const targetId = this.getAttribute("href");
        const targetElement = document.querySelector(targetId);

        if (targetElement) {
          window.scrollTo({
            top: targetElement.offsetTop - 100,
            behavior: "smooth",
          });
        }
      }
    });
  });

  // Share buttons functionality
  const shareButtons = document.querySelectorAll(".share-button");
  const pageUrl = encodeURIComponent(window.location.href);
  const pageTitle = encodeURIComponent(document.title);

  shareButtons.forEach((button) => {
    button.addEventListener("click", function (e) {
      e.preventDefault();

      let shareUrl = "";

      if (this.classList.contains("facebook")) {
        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${pageUrl}`;
      } else if (this.classList.contains("twitter")) {
        shareUrl = `https://twitter.com/intent/tweet?url=${pageUrl}&text=${pageTitle}`;
      } else if (this.classList.contains("linkedin")) {
        shareUrl = `https://www.linkedin.com/shareArticle?mini=true&url=${pageUrl}&title=${pageTitle}`;
      } else if (this.classList.contains("whatsapp")) {
        shareUrl = `https://wa.me/?text=${pageTitle} ${pageUrl}`;
      }

      window.open(shareUrl, "_blank", "width=600,height=400");
    });
  });
});
