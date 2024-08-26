(function (Drupal) {

  Drupal.behaviors.neoColor = {
    init: false,

    attach: function () {
      const init = document.querySelector('[data-neo-pallet-init]');
      if (init && !this.init) {
        this.init = true;
        const colorInput = document.querySelector('.neo-pallet-color') as HTMLInputElement;
        if (colorInput) {
          this.onColorChange(colorInput.value, colorInput);
        }
      }
      else {
        [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach((shade) => {
          const colorInput = document.querySelector(`.neo-color--${shade}-color`) as HTMLInputElement;
          this.onColorChange(colorInput.value, colorInput);
        });
      }
    },

    onColorContentChange: (originalColor:string, el:HTMLInputElement) => {
      const color = el.getAttribute("data-neo-content-color");
      if (color === 'dark') {
        document.querySelector<HTMLElement>('[data-neo-content-dark]')?.setAttribute('data-neo-content-dark', originalColor);
      }
      if (color === 'light') {
        document.querySelector<HTMLElement>('[data-neo-content-light]')?.setAttribute('data-neo-content-light', originalColor);
      }
      Drupal.behaviors.neoColor.attach();
    },

    onColorChange: (originalColor:string, el:HTMLInputElement) => {
      const shadeColor = el.getAttribute("data-neo-shade-color");
      const pallet = document.querySelector('[data-neo-pallet]')?.getAttribute('data-neo-pallet');
      const specific = document.querySelector<HTMLInputElement>('.neo-color--specific')?.checked || false;
      const lightHex = document.querySelector<HTMLElement>('[data-neo-content-light]')?.getAttribute('data-neo-content-light') || '#fff';
      const darkHex = document.querySelector<HTMLElement>('[data-neo-content-dark]')?.getAttribute('data-neo-content-dark') || '#000';
      const light = chroma(lightHex);
      const dark = chroma(darkHex);

      interface Rule {
        [key:number]: number;
      }
      const rules:Rule = {
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
        950: 950,
      };

      const updateColor = (color:chroma.Color, shade:string) => {
        const deltaE = chroma.deltaE(color, light);
        let isDark = deltaE <= 35;
        const darkInput = document.querySelector(`.neo-color--${shade}-dark`) as HTMLInputElement;
        if (darkInput && specific) {
          isDark = darkInput.checked;
        }
        const colorContent = isDark ? dark : light;
        const bgs = document.querySelectorAll<HTMLElement>(`.neo-pallet-preview--${shade}-bg`);
        const contents = document.querySelectorAll<HTMLElement>(`.neo-pallet-preview--${shade}-content`);
        const hexs = document.querySelectorAll<HTMLElement>(`.neo-pallet-preview--${shade}-hex`);
        const rgbs = document.querySelectorAll<HTMLElement>(`.neo-pallet-preview--${shade}-rgb`);
        const hsls = document.querySelectorAll<HTMLElement>(`.neo-pallet-preview--${shade}-hsl`);

        const colorInput = document.querySelector(`.neo-color--${shade}-color`) as HTMLInputElement;
        colorInput.value = String(color.hex());
        const contentDark = document.querySelector(`.neo-color--${shade}-dark`) as HTMLInputElement;
        contentDark.checked = isDark;
        // if () {
        //   contentDark.checked = isDark;
        // }
        // else {
        //   contentDark.value = isDark ? '1' : '0';
        // }

        bgs.forEach((el) => {
          el.style.backgroundColor = color.hex();
        });
        contents.forEach((el) => {
          el.style.color = colorContent.hex();
        });
        hexs.forEach((el) => {
          el.innerHTML = color.hex();
        });
        rgbs.forEach((el) => {
          el.innerHTML = color.rgb().join(', ');
        });
        hsls.forEach((el) => {
          const hsl = color.hsl();
          el.innerHTML = 'hsl(' + Math.round(hsl[0]) + ', ' + Math.round(hsl[1] * 100) + '%, ' + Math.round(hsl[2] * 100) + '%)';
        });
      }

      if (shadeColor) {
        updateColor(chroma(originalColor), String(shadeColor));
      }
      else {
        const scale = chroma.scale(['#fff', originalColor, '#000']);
        [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach((shade) => {
          const amount = rules[shade] / 1000;
          const color = scale(amount);
          updateColor(color, String(shade));
        });
      }

      if (pallet) {
        const updateHead = () => {
          let style:HTMLStyleElement|null = document.querySelector('style[neo-color-temp]');
          if (!style) {
            style = document.createElement('style');
            style.setAttribute('neo-color-temp', '');
            document.body.append(style);
          }
          const html:Array<string> = [];
          [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950].forEach((shade) => {
            const colorInput = document.querySelector(`.neo-color--${shade}-color`) as HTMLInputElement;
            const color = chroma(colorInput.value);
            html.push(`--color-${pallet}-${shade}: ${color.rgb().join(' ')};`);
          });
          style.innerHTML = `:root { ${html.join(' ')} }`;
        }
        updateHead();
      }
    }
  };

})(Drupal);

export {};
