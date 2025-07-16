document.getElementById("theme-toggle").addEventListener("click", function () {
  const htmlTag = document.documentElement;
  const currentTheme = htmlTag.getAttribute("data-bs-theme");
  const newTheme = currentTheme === "light" ? "dark" : "light";
  htmlTag.setAttribute("data-bs-theme", newTheme);
});
