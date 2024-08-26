(function(m) {
  m.behaviors.neoColor = {
    init: !1,
    attach: function() {
      if (document.querySelector("[data-neo-pallet-init]") && !this.init) {
        this.init = !0;
        const t = document.querySelector(".neo-pallet-color");
        t && this.onColorChange(t.value, t);
      } else
        [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach((t) => {
          const c = document.querySelector(`.neo-color--${t}-color`);
          this.onColorChange(c.value, c);
        });
    },
    onColorContentChange: (l, t) => {
      var a, i;
      const c = t.getAttribute("data-neo-content-color");
      c === "dark" && ((a = document.querySelector("[data-neo-content-dark]")) == null || a.setAttribute("data-neo-content-dark", l)), c === "light" && ((i = document.querySelector("[data-neo-content-light]")) == null || i.setAttribute("data-neo-content-light", l)), m.behaviors.neoColor.attach();
    },
    onColorChange: (l, t) => {
      var y, g, S, q;
      const c = t.getAttribute("data-neo-shade-color"), a = (y = document.querySelector("[data-neo-pallet]")) == null ? void 0 : y.getAttribute("data-neo-pallet"), i = ((g = document.querySelector(".neo-color--specific")) == null ? void 0 : g.checked) || !1, b = ((S = document.querySelector("[data-neo-content-light]")) == null ? void 0 : S.getAttribute("data-neo-content-light")) || "#fff", k = ((q = document.querySelector("[data-neo-content-dark]")) == null ? void 0 : q.getAttribute("data-neo-content-dark")) || "#000", p = chroma(b), $ = chroma(k), A = {
        50: 15,
        100: 35,
        200: 80,
        300: 160,
        400: 325,
        500: 500,
        600: 600,
        700: 700,
        800: 800,
        900: 900,
        950: 950
      }, f = (o, e) => {
        let r = chroma.deltaE(o, p) <= 35;
        const s = document.querySelector(`.neo-color--${e}-dark`);
        s && i && (r = s.checked);
        const d = r ? $ : p, C = document.querySelectorAll(`.neo-pallet-preview--${e}-bg`), E = document.querySelectorAll(`.neo-pallet-preview--${e}-content`), v = document.querySelectorAll(`.neo-pallet-preview--${e}-hex`), x = document.querySelectorAll(`.neo-pallet-preview--${e}-rgb`), H = document.querySelectorAll(`.neo-pallet-preview--${e}-hsl`), M = document.querySelector(`.neo-color--${e}-color`);
        M.value = String(o.hex());
        const w = document.querySelector(`.neo-color--${e}-dark`);
        w.checked = r, C.forEach((n) => {
          n.style.backgroundColor = o.hex();
        }), E.forEach((n) => {
          n.style.color = d.hex();
        }), v.forEach((n) => {
          n.innerHTML = o.hex();
        }), x.forEach((n) => {
          n.innerHTML = o.rgb().join(", ");
        }), H.forEach((n) => {
          const h = o.hsl();
          n.innerHTML = "hsl(" + Math.round(h[0]) + ", " + Math.round(h[1] * 100) + "%, " + Math.round(h[2] * 100) + "%)";
        });
      };
      if (c)
        f(chroma(l), String(c));
      else {
        const o = chroma.scale(["#fff", l, "#000"]);
        [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach((e) => {
          const u = A[e] / 1e3, r = o(u);
          f(r, String(e));
        });
      }
      a && (() => {
        let e = document.querySelector("style[neo-color-temp]");
        e || (e = document.createElement("style"), e.setAttribute("neo-color-temp", ""), document.body.append(e));
        const u = [];
        [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach((r) => {
          const s = document.querySelector(`.neo-color--${r}-color`), d = chroma(s.value);
          u.push(`--color-${a}-${r}: ${d.rgb().join(" ")};`);
        }), e.innerHTML = `:root { ${u.join(" ")} }`;
      })();
    }
  };
})(Drupal);
//# sourceMappingURL=neo-color.js.map
