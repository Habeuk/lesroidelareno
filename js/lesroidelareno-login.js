function loadScript(src) {
  return new Promise((resolv) => {
    var s = document.createElement("script");
    s.setAttribute("src", src);
    s.onload = function () {
      console.log(" Chargement du script ok : ", src);
      resolv(true);
    };
    document.head.appendChild(s);
  });
}
setTimeout(() => {
  loadScript(
    "/modules/custom/login_rx_vuejs/files/loginRxVuejs.umd.min.js?" +
      new Date().getTime()
  );
}, 1000);
// listerner if user is login
document.addEventListener(
  "login_rx_vuejs__user_is_login",
  () => {
    console.log(" Utilisateur connecter ");
    // document.querySelector("[data-trigger=run]").click();
    window.location.reload();
  },
  false
);
